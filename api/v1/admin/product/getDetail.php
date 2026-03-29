<?php

/**
 * 获取商品详情API
 * 用于管理端编辑商品时获取商品详情
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 根据商品ID查询详情
 * - 包含商品的所有信息（名称、描述、分类、价格、图片等）
 */

/**
 * 获取商品详情API
 * 用于管理端编辑商品时获取商品详情
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

// 获取商品ID
$id = intval($_GET['id'] ?? 0);

// 验证商品ID
if ($id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品ID无效');
    exit;
}

// 连接数据库
$db = Database::connect();

// 获取商品表名
$productTable = Database::table('product');

// 查询商品详情
$sql = "
    SELECT 
        id,
        name,
        remark,
        description,
        category_value,
        price,
        image,
        images,
        status
    FROM {$productTable}
    WHERE id = ?
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo JsonResponse::send(404, '商品不存在');
        exit;
    }

    // 返回商品详情
    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'id' => intval($product['id']),
        'name' => $product['name'],
        'remark' => $product['remark'],
        'description' => $product['description'],
        'categoryValue' => $product['category_value'],
        'price' => PriceFormatter::format($product['price']),
        'image' => $product['image'],
        'images' => $product['images'],
        'status' => $product['status']
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取商品详情失败', [
        'error' => $e->getMessage()
    ]);
}
