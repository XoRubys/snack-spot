<?php

/**
 * 获取分类列表API
 * 用于管理端获取商品分类列表
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 获取所有商品分类
 * - 按ID升序排列
 */

/**
 * 获取分类列表API
 * 用于管理端获取商品分类列表
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许GET请求
Method::check('GET');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 连接数据库
$db = Database::connect();

// 获取分类表名
$categoryTable = Database::table('category');

// 查询所有分类
$sql = "
    SELECT 
        id,
        name,
        value,
        remark
    FROM {$categoryTable}
    ORDER BY id ASC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 格式化分类列表
    $categoryList = array_map(function ($item) {
        return [
            'id' => intval($item['id']),
            'name' => $item['name'],
            'value' => $item['value'],
            'remark' => $item['remark']
        ];
    }, $categories);

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'list' => $categoryList
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取分类列表失败', [
        'error' => $e->getMessage()
    ]);
}
