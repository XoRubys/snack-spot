<?php

/**
 * 商品编辑/添加API
 * 用于管理员添加或编辑商品信息
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持添加新商品和编辑现有商品
 * - 验证商品名称、描述、分类、价格、图片等必填项
 * - 支持多张商品图片（用|分隔）
 * - 商品状态：上线(online)、下线(offline)
 */

/**
 * 商品编辑/添加API
 * 用于管理员添加或编辑商品信息
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
$name = trim($input['name'] ?? '');
$remark = trim($input['remark'] ?? '');
$description = trim($input['description'] ?? '');
$category_value = trim($input['category_value'] ?? '');
$price = $input['price'] ?? '';
$image = trim($input['image'] ?? '');
$images = trim($input['images'] ?? '');
$status = trim($input['status'] ?? 'online');

// 连接数据库
$db = Database::connect();
$productTable = Database::table('product');

// 判断是编辑还是添加模式
$isEdit = $id > 0;

if ($isEdit) {
    // 编辑模式：检查商品是否存在
    $stmt = $db->prepare("SELECT id FROM {$productTable} WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo JsonResponse::send(400, '商品不存在');
        exit;
    }
}

// 验证商品名称
if (empty($name)) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品名称不能为空');
    exit;
}

if (mb_strlen($name) > 25) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品名称不能超过25个字符');
    exit;
}

// 验证商品备注
if (empty($remark)) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品备注不能为空');
    exit;
}

if (mb_strlen($remark) > 50) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品备注不能超过50个字符');
    exit;
}

// 验证商品描述
if (empty($description)) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品描述不能为空');
    exit;
}

if (mb_strlen($description) > 255) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品描述不能超过255个字符');
    exit;
}

// 验证商品分类
if (empty($category_value)) {
    http_response_code(400);
    echo JsonResponse::send(400, '请选择商品分类');
    exit;
}

$categoryTable = Database::table('category');
$stmt = $db->prepare("SELECT value FROM {$categoryTable} WHERE value = ?");
$stmt->execute([$category_value]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品分类无效');
    exit;
}

// 验证商品价格
if (empty($price) || !is_numeric($price) || floatval($price) <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '请输入正确的商品售价');
    exit;
}

$price = number_format(floatval($price), 2, '.', '');

// 验证商品主图
if (empty($image)) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品主图不能为空');
    exit;
}

if (!filter_var($image, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品主图链接格式错误');
    exit;
}

// 验证商品多图
if (!empty($images)) {
    $imageArray = explode('|', $images);
    foreach ($imageArray as $img) {
        if (!empty($img) && !filter_var($img, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo JsonResponse::send(400, '商品图片链接格式错误');
            exit;
        }
    }
}

// 验证商品状态
if (!in_array($status, ['online', 'offline'])) {
    $status = 'online';
}

try {
    if ($isEdit) {
        // 编辑商品信息
        $stmt = $db->prepare("
            UPDATE {$productTable} SET
                name = ?,
                remark = ?,
                description = ?,
                category_value = ?,
                price = ?,
                image = ?,
                images = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $remark,
            $description,
            $category_value,
            $price,
            $image,
            $images,
            $status,
            $id
        ]);

        http_response_code(200);
        echo JsonResponse::send(200, '商品修改成功', [
            'id' => $id
        ]);
    } else {
        // 添加新商品
        $stmt = $db->prepare("
            INSERT INTO {$productTable} (
                name,
                remark,
                description,
                category_value,
                price,
                image,
                images,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $remark,
            $description,
            $category_value,
            $price,
            $image,
            $images,
            $status
        ]);

        $productId = $db->lastInsertId();

        http_response_code(200);
        echo JsonResponse::send(200, '商品添加成功', [
            'id' => intval($productId)
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, $isEdit ? '商品修改失败' : '商品添加失败', [
        'error' => $e->getMessage()
    ]);
}
