<?php

/**
 * 获取库存列表API
 * 用于管理端获取库存列表，支持按商品、平台和关键词筛选
 * 仅显示30天内的库存记录
 *
 * 功能说明：
 * - 验证管理员权限
 * - 支持按商品ID、平台名称、关键词筛选
 * - 关键词支持搜索平台订单号、快递单号、商家名称
 * - 仅查询30天内的库存
 * - 关联查询商品名称
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

// 获取筛选参数
$product_id = intval($_GET['product_id'] ?? 0);
$platform_name = trim($_GET['platform_name'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

// 连接数据库
$db = Database::connect();

// 获取表名
$inventoryTable = Database::table('inventory');
$productTable = Database::table('product');

// 构建查询条件 - 仅查询30天内的库存
$where = ["i.create_time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))"];
$params = [];

if ($product_id > 0) {
    $where[] = "i.product_id = ?";
    $params[] = $product_id;
}

if (!empty($platform_name) && in_array($platform_name, ['pdd', 'jd', 'tb'])) {
    $where[] = "i.platform_name = ?";
    $params[] = $platform_name;
}

if (!empty($keyword)) {
    $where[] = "(i.platform_order_number LIKE ? OR i.tracking_number LIKE ? OR i.merchant_name LIKE ?)";
    $keywordLike = "%{$keyword}%";
    $params[] = $keywordLike;
    $params[] = $keywordLike;
    $params[] = $keywordLike;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// 查询库存列表
$sql = "
    SELECT 
        i.id,
        i.product_id,
        p.name as product_name,
        p.price as product_price,
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
    {$whereSql}
    ORDER BY i.create_time DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $inventoryList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 格式化库存列表
    $list = array_map(function ($item) {
        $wholesalePrice = floatval($item['wholesale_price']);
        $productPrice = floatval($item['product_price']);
        $remainingQuantity = intval($item['remaining_quantity']);
        $cost = $remainingQuantity * $wholesalePrice;
        $revenue = $remainingQuantity * $productPrice;
        $profit = $revenue - $cost;

        return [
            'id' => intval($item['id']),
            'productId' => intval($item['product_id']),
            'productName' => $item['product_name'],
            'quantity' => intval($item['quantity']),
            'remainingQuantity' => $remainingQuantity,
            'platformName' => $item['platform_name'],
            'platformOrderNumber' => $item['platform_order_number'],
            'trackingNumber' => $item['tracking_number'],
            'wholesalePrice' => PriceFormatter::format($wholesalePrice),
            'productPrice' => PriceFormatter::format($productPrice),
            'cost' => PriceFormatter::format($cost),
            'profit' => PriceFormatter::format($profit),
            'merchantName' => $item['merchant_name'],
            'remark' => $item['remark'],
            'createTime' => intval($item['create_time']),
            'updateTime' => intval($item['update_time'])
        ];
    }, $inventoryList);

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'list' => $list,
        'total' => count($list)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取库存列表失败', [
        'error' => $e->getMessage()
    ]);
}
