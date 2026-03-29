<?php

/**
 * 获取商品列表API
 * 用于管理端获取商品列表，支持按状态、分类和关键词筛选
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持按商品状态（上线/下线）、分类、关键词搜索
 * - 关联查询分类名称
 * - 统计每个商品的总库存
 * - 统计上线商品数量
 */

/**
 * 获取商品列表API
 * 用于管理端获取商品列表，支持筛选和搜索
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
$status = trim($_GET['status'] ?? '');
$categoryValue = trim($_GET['category_value'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

// 连接数据库
$db = Database::connect();

// 获取表名
$productTable = Database::table('product');
$categoryTable = Database::table('category');
$inventoryTable = Database::table('inventory');

// 构建查询条件
$whereConditions = [];
$params = [];

if (!empty($status) && in_array($status, ['online', 'offline'])) {
    $whereConditions[] = "p.status = :status";
    $params[':status'] = $status;
}

if (!empty($categoryValue)) {
    $whereConditions[] = "p.category_value = :category_value";
    $params[':category_value'] = $categoryValue;
}

if (!empty($keyword)) {
    $whereConditions[] = "p.name LIKE :keyword";
    $params[':keyword'] = '%' . $keyword . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 查询商品列表（关联分类和库存）
$sql = "
    SELECT 
        p.id,
        p.name,
        p.category_value,
        c.name AS category_name,
        p.price,
        p.image,
        p.status,
        COALESCE(SUM(i.remaining_quantity), 0) AS stock
    FROM {$productTable} p
    LEFT JOIN {$categoryTable} c ON p.category_value = c.value
    LEFT JOIN {$inventoryTable} i ON p.id = i.product_id
    {$whereClause}
    GROUP BY p.id
    ORDER BY p.id DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 格式化商品列表
    $productList = array_map(function ($item) {
        return [
            'id' => intval($item['id']),
            'name' => $item['name'],
            'categoryValue' => $item['category_value'],
            'categoryName' => $item['category_name'] ?? '',
            'price' => PriceFormatter::format($item['price']),
            'image' => $item['image'],
            'status' => $item['status'],
            'stock' => intval($item['stock'])
        ];
    }, $products);

    // 统计上线商品数量
    $onlineCount = count(array_filter($productList, function ($item) {
        return $item['status'] === 'online';
    }));

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'list' => $productList,
        'total' => count($productList),
        'onlineCount' => $onlineCount
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取商品列表失败', [
        'error' => $e->getMessage()
    ]);
}
