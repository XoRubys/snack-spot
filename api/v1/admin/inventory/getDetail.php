<?php

/**
 * 获取库存详情API
 * 用于管理端编辑库存时获取库存详情
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 根据库存ID查询详情
 * - 关联查询商品名称
 */

/**
 * 获取库存详情API
 * 用于管理端编辑库存时获取库存详情
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

// 获取库存ID
$id = intval($_GET['id'] ?? 0);

// 验证库存ID
if ($id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '库存ID无效');
    exit;
}

// 连接数据库
$db = Database::connect();

// 获取表名
$inventoryTable = Database::table('inventory');
$productTable = Database::table('product');

// 查询库存详情
$sql = "
    SELECT 
        i.id,
        i.product_id,
        p.name as product_name,
        i.quantity,
        i.remaining_quantity,
        i.platform_name,
        i.platform_order_number,
        i.tracking_number,
        i.wholesale_price,
        i.merchant_name,
        i.remark,
        i.create_time,
        i.update_time
    FROM {$inventoryTable} i
    LEFT JOIN {$productTable} p ON i.product_id = p.id
    WHERE i.id = ?
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory) {
        http_response_code(404);
        echo JsonResponse::send(404, '库存记录不存在');
        exit;
    }

    // 格式化库存数据
    $data = [
        'id' => intval($inventory['id']),
        'productId' => intval($inventory['product_id']),
        'productName' => $inventory['product_name'],
        'quantity' => intval($inventory['quantity']),
        'remainingQuantity' => intval($inventory['remaining_quantity']),
        'platformName' => $inventory['platform_name'],
        'platformOrderNumber' => $inventory['platform_order_number'],
        'trackingNumber' => $inventory['tracking_number'],
        'wholesalePrice' => PriceFormatter::format($inventory['wholesale_price']),
        'merchantName' => $inventory['merchant_name'],
        'remark' => $inventory['remark'],
        'createTime' => intval($inventory['create_time']),
        'updateTime' => intval($inventory['update_time'])
    ];

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', $data);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取库存详情失败', [
        'error' => $e->getMessage()
    ]);
}
