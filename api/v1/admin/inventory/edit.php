<?php

/**
 * 库存编辑/添加API
 * 用于管理员添加或编辑库存信息
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持添加新库存和编辑现有库存
 * - 验证商品、平台、价格等必填项
 * - 支持的平台：拼多多(pdd)、京东(jd)、淘宝(tb)
 * - 自动记录创建和更新时间
 */

/**
 * 库存编辑/添加API
 * 用于管理员添加或编辑库存信息
 * 有id且id>0时为编辑模式，否则为添加模式
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

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$product_id = intval($input['product_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 0);
$remaining_quantity = intval($input['remaining_quantity'] ?? 0);
$platform_name = trim($input['platform_name'] ?? '');
$platform_order_number = trim($input['platform_order_number'] ?? '');
$tracking_number = trim($input['tracking_number'] ?? '');
$wholesale_price = $input['wholesale_price'] ?? '';
$merchant_name = trim($input['merchant_name'] ?? '');
$remark = trim($input['remark'] ?? '');

// 连接数据库
$db = Database::connect();
$inventoryTable = Database::table('inventory');
$productTable = Database::table('product');

// 判断是编辑还是添加模式
$isEdit = $id > 0;

if ($isEdit) {
    // 编辑模式：检查库存记录是否存在
    $stmt = $db->prepare("SELECT id FROM {$inventoryTable} WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo JsonResponse::send(400, '库存记录不存在');
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

// 验证库存数量
if ($quantity <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '库存数量必须大于0');
    exit;
}

// 验证剩余数量
if ($remaining_quantity < 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '剩余数量不能小于0');
    exit;
}

if ($remaining_quantity > $quantity) {
    http_response_code(400);
    echo JsonResponse::send(400, '剩余数量不能大于库存数量');
    exit;
}

// 验证进货平台
if (empty($platform_name)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请选择进货平台');
    exit;
}

if (!in_array($platform_name, ['pdd', 'jd', 'tb'])) {
    http_response_code(400);
    echo JsonResponse::send(400, '进货平台无效');
    exit;
}

// 验证平台订单号
if (empty($platform_order_number)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请输入平台订单号');
    exit;
}

if (mb_strlen($platform_order_number) > 50) {
    http_response_code(400);
    echo JsonResponse::send(400, '平台订单号不能超过50个字符');
    exit;
}

// 验证快递单号
if (empty($tracking_number)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请输入快递单号');
    exit;
}

if (mb_strlen($tracking_number) > 50) {
    http_response_code(400);
    echo JsonResponse::send(400, '快递单号不能超过50个字符');
    exit;
}

// 验证批发价
if (empty($wholesale_price) || !is_numeric($wholesale_price) || floatval($wholesale_price) <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '请输入正确的批发价');
    exit;
}

$wholesale_price = number_format(floatval($wholesale_price), 2, '.', '');

// 验证商家名称
if (empty($merchant_name)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请输入商家名称');
    exit;
}

if (mb_strlen($merchant_name) > 50) {
    http_response_code(400);
    echo JsonResponse::send(400, '商家名称不能超过50个字符');
    exit;
}

// 验证备注
if (mb_strlen($remark) > 255) {
    http_response_code(400);
    echo JsonResponse::send(400, '备注不能超过255个字符');
    exit;
}

// 获取当前时间
$now = time();

try {
    if ($isEdit) {
        // 编辑库存记录
        $stmt = $db->prepare("
            UPDATE {$inventoryTable} SET
                product_id = ?,
                quantity = ?,
                remaining_quantity = ?,
                platform_name = ?,
                platform_order_number = ?,
                tracking_number = ?,
                wholesale_price = ?,
                merchant_name = ?,
                remark = ?,
                update_time = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $product_id,
            $quantity,
            $remaining_quantity,
            $platform_name,
            $platform_order_number,
            $tracking_number,
            $wholesale_price,
            $merchant_name,
            $remark,
            $now,
            $id
        ]);

        http_response_code(200);
        echo JsonResponse::send(200, '库存修改成功', [
            'id' => $id
        ]);
    } else {
        // 添加新库存记录
        $stmt = $db->prepare("
            INSERT INTO {$inventoryTable} (
                product_id,
                quantity,
                remaining_quantity,
                platform_name,
                platform_order_number,
                tracking_number,
                wholesale_price,
                merchant_name,
                remark,
                create_time,
                update_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $product_id,
            $quantity,
            $remaining_quantity,
            $platform_name,
            $platform_order_number,
            $tracking_number,
            $wholesale_price,
            $merchant_name,
            $remark,
            $now,
            $now
        ]);

        $inventoryId = $db->lastInsertId();

        http_response_code(200);
        echo JsonResponse::send(200, '库存添加成功', [
            'id' => intval($inventoryId)
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, $isEdit ? '库存修改失败' : '库存添加失败', [
        'error' => $e->getMessage()
    ]);
}
