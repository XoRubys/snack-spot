<?php

/**
 * 分类删除API
 * 用于管理员删除商品分类
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 检查分类是否存在
 * - 检查分类下是否有商品（有商品则不允许删除）
 * - 删除分类
 */

/**
 * 分类删除API
 * 用于管理员删除商品分类
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

// 验证分类ID
if ($id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类ID不能为空');
    exit;
}

// 连接数据库
$db = Database::connect();
$categoryTable = Database::table('category');
$productTable = Database::table('product');

// 1. 检查分类是否存在
$stmt = $db->prepare("SELECT id, value FROM {$categoryTable} WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类不存在');
    exit;
}

// 2. 检查是否有商品使用该分类
$stmt = $db->prepare("SELECT COUNT(*) as count FROM {$productTable} WHERE category_value = ?");
$stmt->execute([$category['value']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '该分类下存在商品，无法删除');
    exit;
}

// 3. 删除分类
try {
    $stmt = $db->prepare("DELETE FROM {$categoryTable} WHERE id = ?");
    $stmt->execute([$id]);

    http_response_code(200);
    echo JsonResponse::send(200, '分类删除成功');
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '删除失败', [
        'error' => $e->getMessage()
    ]);
}
