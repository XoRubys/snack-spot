<?php

namespace SnackSpot\Api;

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\ShopStatus;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

Method::check('GET');
ShopStatus::check();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品ID无效');
    exit;
}

try {
    $db = Database::connect();
    $productTable = Database::table('product');
    $categoryTable = Database::table('category');
    $inventoryTable = Database::table('inventory');

    $sql = "
        SELECT
            p.id,
            p.name,
            p.remark,
            p.description,
            p.category_value,
            p.price,
            p.images,
            p.status,
            c.name as category_name
        FROM {$productTable} p
        LEFT JOIN {$categoryTable} c ON p.category_value = c.value
        WHERE p.id = ?
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo JsonResponse::send(404, '商品不存在');
        exit;
    }

    $stockSql = "
        SELECT COALESCE(SUM(remaining_quantity), 0) as stock
        FROM {$inventoryTable}
        WHERE product_id = ?
    ";
    $stockStmt = $db->prepare($stockSql);
    $stockStmt->execute([$id]);
    $stockResult = $stockStmt->fetch(\PDO::FETCH_ASSOC);
    $stock = intval($stockResult['stock']);

    // 查询本月销售数据
    $orderItemTable = Database::table('order_item');
    $orderTable = Database::table('order');
    $monthStart = strtotime(date('Y-m-01'));

    $monthlySalesSql = "
        SELECT COUNT(*) as sales_count
        FROM {$orderItemTable} oi
        INNER JOIN {$orderTable} o ON oi.order_id = o.id
        WHERE oi.product_id = ?
          AND o.status IN ('paid', 'completed')
          AND o.create_time >= ?
    ";
    $monthlySalesStmt = $db->prepare($monthlySalesSql);
    $monthlySalesStmt->execute([$id, $monthStart]);
    $monthlySalesResult = $monthlySalesStmt->fetch(\PDO::FETCH_ASSOC);
    $monthlySales = intval($monthlySalesResult['sales_count'] ?? 0);

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'id' => intval($product['id']),
        'name' => $product['name'],
        'remark' => $product['remark'],
        'description' => $product['description'],
        'categoryValue' => $product['category_value'],
        'categoryName' => $product['category_name'],
        'price' => PriceFormatter::format($product['price']),
        'images' => $product['images'],
        'stock' => $stock,
        'monthlySales' => $monthlySales,
        'status' => $product['status']
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取商品详情失败', [
        'error' => $e->getMessage()
    ]);
}
