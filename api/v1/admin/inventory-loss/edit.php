<?php

/**
 * 库损编辑/添加API
 * 用于管理员添加或编辑库损记录
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持添加新库损和编辑现有库损
 * - 验证商品、批次、损耗类型等必填项
 * - 支持的损耗类型：损坏(damage)、过期(expired)、盗窃(theft)、盘点错误(error)、其他(other)
 * - 自动计算损失金额（根据批次批发价 * 损耗数量）
 * - 自动记录创建和更新时间
 * - 自动记录操作员信息
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许POST请求
Method::check('POST');

// 验证管理员权限
$accessToken = Auth::validateAdmin();
$adminId = $accessToken['admin_id'] ?? 0;
$adminName = $accessToken['admin_name'] ?? '管理员';

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$product_id = intval($input['product_id'] ?? 0);
$batch_id = intval($input['batch_id'] ?? 0);
$loss_type = trim($input['loss_type'] ?? '');
$quantity = intval($input['quantity'] ?? 0);
$reason = trim($input['reason'] ?? '');
$remark = trim($input['remark'] ?? '');

// 连接数据库
$db = Database::connect();
$lossTable = Database::table('inventory_loss');
$productTable = Database::table('product');
$inventoryTable = Database::table('inventory');

// 判断是编辑还是添加模式
$isEdit = $id > 0;

if ($isEdit) {
    // 编辑模式：检查库损记录是否存在
    $stmt = $db->prepare("SELECT id FROM {$lossTable} WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo JsonResponse::send(400, '库损记录不存在');
        exit;
    }
}

// 验证商品ID
if ($product_id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '请选择商品');
    exit;
}

$stmt = $db->prepare("SELECT id FROM {$productTable} WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品不存在');
    exit;
}

// 验证批次ID
if ($batch_id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '请选择库存批次');
    exit;
}

// 获取批次信息并验证
$stmt = $db->prepare("SELECT id, wholesale_price, remaining_quantity FROM {$inventoryTable} WHERE id = ? AND product_id = ?");
$stmt->execute([$batch_id, $product_id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    http_response_code(400);
    echo JsonResponse::send(400, '库存批次不存在或批次与商品不匹配');
    exit;
}

// 如果是编辑模式，获取原损耗记录的数量
$originalQuantity = 0;
if ($isEdit) {
    $stmt = $db->prepare("SELECT quantity, batch_id FROM {$lossTable} WHERE id = ?");
    $stmt->execute([$id]);
    $originalLoss = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($originalLoss) {
        $originalQuantity = intval($originalLoss['quantity']);
        // 如果更换了批次，原批次需要恢复数量
        if (intval($originalLoss['batch_id']) !== $batch_id) {
            $originalQuantity = 0;
        }
    }
}

// 验证损耗数量不能超过批次剩余数量（编辑时要考虑原数量）
$availableQuantity = intval($batch['remaining_quantity']) + $originalQuantity;
if ($quantity > $availableQuantity) {
    http_response_code(400);
    echo JsonResponse::send(400, '损耗数量不能大于批次剩余数量');
    exit;
}

// 计算损失金额
$loss_amount = floatval($batch['wholesale_price']) * $quantity;

// 验证损耗类型
$validTypes = ['damage', 'expired', 'theft', 'error', 'other'];
if (empty($loss_type) || !in_array($loss_type, $validTypes)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请选择有效的损耗类型');
    exit;
}

// 验证损耗数量
if ($quantity <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '损耗数量必须大于0');
    exit;
}

// 验证损耗原因
if (empty($reason)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请输入损耗原因');
    exit;
}

$now = time();

try {
    // 开始事务
    $db->beginTransaction();

    if ($isEdit) {
        // 编辑模式：如果更换了批次，需要恢复原批次的数量
        if ($originalQuantity > 0 && intval($originalLoss['batch_id']) !== $batch_id) {
            $stmt = $db->prepare("UPDATE {$inventoryTable} SET remaining_quantity = remaining_quantity + ? WHERE id = ?");
            $stmt->execute([$originalQuantity, $originalLoss['batch_id']]);
        }

        // 更新当前批次的剩余数量
        $quantityDiff = $quantity - $originalQuantity;
        $stmt = $db->prepare("UPDATE {$inventoryTable} SET remaining_quantity = remaining_quantity - ? WHERE id = ?");
        $stmt->execute([$quantityDiff, $batch_id]);

        // 编辑模式：更新库损记录
        $sql = "
            UPDATE {$lossTable} 
            SET 
                product_id = ?,
                batch_id = ?,
                loss_type = ?,
                quantity = ?,
                reason = ?,
                remark = ?,
                update_time = ?
            WHERE id = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $product_id,
            $batch_id,
            $loss_type,
            $quantity,
            $reason,
            $remark,
            $now,
            $id
        ]);

        $db->commit();
        echo JsonResponse::send(200, '修改成功', ['id' => $id, 'loss_amount' => $loss_amount]);
    } else {
        // 添加模式：减少库存批次的剩余数量
        $stmt = $db->prepare("UPDATE {$inventoryTable} SET remaining_quantity = remaining_quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $batch_id]);

        // 添加模式：插入新库损记录
        $sql = "
            INSERT INTO {$lossTable} 
            (product_id, batch_id, quantity, loss_type, reason, operator_id, operator_name, remark, create_time, update_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $product_id,
            $batch_id,
            $quantity,
            $loss_type,
            $reason,
            $adminId,
            $adminName,
            $remark,
            $now,
            $now
        ]);

        $newId = $db->lastInsertId();
        $db->commit();
        echo JsonResponse::send(200, '添加成功', ['id' => intval($newId), 'loss_amount' => $loss_amount]);
    }
} catch (PDOException $e) {
    $db->rollBack();
    error_log('库损操作失败: ' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '操作失败，请稍后重试');
}
