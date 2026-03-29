<?php

/**
 * 分类编辑/添加API
 * 用于管理员添加或编辑商品分类
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持添加新分类和编辑现有分类
 * - 验证分类名称、值的格式和唯一性
 * - 分类值只能包含小写字母、数字和下划线
 */

/**
 * 分类编辑/添加API
 * 用于管理员添加或编辑商品分类
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
$value = trim($input['value'] ?? '');
$remark = trim($input['remark'] ?? '');

// 连接数据库
$db = Database::connect();
$categoryTable = Database::table('category');

// 判断是编辑还是添加模式
$isEdit = $id > 0;

if ($isEdit) {
    // 编辑模式：检查分类是否存在
    $stmt = $db->prepare("SELECT id FROM {$categoryTable} WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo JsonResponse::send(400, '分类不存在');
        exit;
    }
}

// 验证分类名称
if (empty($name)) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类名称不能为空');
    exit;
}

if (mb_strlen($name) > 20) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类名称不能超过20个字符');
    exit;
}

// 验证分类值
if (empty($value)) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类值不能为空');
    exit;
}

if (mb_strlen($value) > 20) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类值不能超过20个字符');
    exit;
}

if (!preg_match('/^[a-z0-9_]+$/', $value)) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类值只能包含小写字母、数字和下划线');
    exit;
}

// 验证备注
if (mb_strlen($remark) > 50) {
    http_response_code(400);
    echo JsonResponse::send(400, '备注不能超过50个字符');
    exit;
}

// 检查分类值是否已存在（编辑时排除当前分类）
$stmt = $db->prepare("SELECT id FROM {$categoryTable} WHERE value = ? AND id != ?");
$stmt->execute([$value, $id]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo JsonResponse::send(400, '分类值已存在');
    exit;
}

try {
    if ($isEdit) {
        // 编辑分类
        $stmt = $db->prepare("
            UPDATE {$categoryTable} SET
                name = ?,
                value = ?,
                remark = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $value,
            $remark,
            $id
        ]);

        http_response_code(200);
        echo JsonResponse::send(200, '分类修改成功', [
            'id' => $id
        ]);
    } else {
        // 添加新分类
        $stmt = $db->prepare("
            INSERT INTO {$categoryTable} (
                name,
                value,
                remark
            ) VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $value,
            $remark
        ]);

        $categoryId = $db->lastInsertId();

        http_response_code(200);
        echo JsonResponse::send(200, '分类添加成功', [
            'id' => intval($categoryId)
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '操作失败', [
        'error' => $e->getMessage()
    ]);
}
