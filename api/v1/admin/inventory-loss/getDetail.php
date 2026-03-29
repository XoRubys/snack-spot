<?php

/**
 * 获取库损详情API
 * 用于管理端获取单条库损记录详情
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 根据ID获取库损记录详情
 * - 关联查询商品名称和库存批次信息
 * - 自动计算损失金额
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

// 检查请求方法，只允许GET请求
Method::check('GET');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 获取库损ID
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '库损ID不能为空');
    exit;
}

// 连接数据库
$db = Database::connect();

// 获取表名
$lossTable = Database::table('inventory_loss');
$productTable = Database::table('product');
$inventoryTable = Database::table('inventory');

// 查询库损详情（关联库存表获取批发价）
$sql = "
    SELECT 
        l.id,
        l.product_id,
        p.name as product_name,
        l.batch_id,
        i.wholesale_price,
        l.quantity,
        l.loss_type,
        l.reason,
        l.operator_id,
        l.operator_name,
        l.remark,
        l.create_time,
        l.update_time
    FROM {$lossTable} l
    LEFT JOIN {$productTable} p ON l.product_id = p.id
    LEFT JOIN {$inventoryTable} i ON l.batch_id = i.id
    WHERE l.id = ?
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $loss = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loss) {
        http_response_code(404);
        echo JsonResponse::send(404, '库损记录不存在');
        exit;
    }

    // 计算损失金额
    $quantity = intval($loss['quantity']);
    $wholesalePrice = floatval($loss['wholesale_price'] ?? 0);
    $lossAmount = $quantity * $wholesalePrice;

    // 格式化库损详情
    $detail = [
        'id' => intval($loss['id']),
        'product_id' => intval($loss['product_id']),
        'product_name' => $loss['product_name'],
        'batch_id' => intval($loss['batch_id']),
        'quantity' => $quantity,
        'wholesale_price' => PriceFormatter::format($wholesalePrice),
        'loss_amount' => PriceFormatter::format($lossAmount),
        'loss_type' => $loss['loss_type'],
        'reason' => $loss['reason'],
        'operator_id' => intval($loss['operator_id']),
        'operator_name' => $loss['operator_name'],
        'remark' => $loss['remark'],
        'create_time' => intval($loss['create_time']),
        'update_time' => intval($loss['update_time']),
    ];

    echo JsonResponse::send(200, '获取成功', $detail);
} catch (PDOException $e) {
    error_log('获取库损详情失败: ' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '获取库损详情失败');
}
