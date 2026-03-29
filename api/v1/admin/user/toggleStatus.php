<?php

/**
 * 管理端切换用户状态API
 * 用于管理员禁用/恢复用户账号
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 切换用户状态（active <-> inactive）
 * - 不能禁用其他管理员账号
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
$userId = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);

if (empty($userId) || $userId <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '用户ID不能为空');
    exit;
}

// 连接数据库
$db = Database::connect();

// 获取表名
$userTable = Database::table('user');

try {
    // 查询目标用户信息
    $stmt = $db->prepare("
        SELECT id, level, status, username 
        FROM {$userTable} 
        WHERE id = :id 
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo JsonResponse::send(404, '用户不存在');
        exit;
    }

    // 不能禁用其他管理员
    if ($user['level'] === 'admin') {
        http_response_code(403);
        echo JsonResponse::send(403, '不能操作管理员账号');
        exit;
    }

    // 切换状态
    $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

    $updateStmt = $db->prepare("
        UPDATE {$userTable} 
        SET status = :status 
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':status' => $newStatus,
        ':id' => $userId
    ]);

    $actionText = $newStatus === 'active' ? '恢复' : '禁用';

    // 返回成功响应
    echo JsonResponse::send(200, $actionText . '成功', [
        'id' => intval($user['id']),
        'username' => $user['username'],
        'status' => $newStatus
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '操作失败: ' . $e->getMessage());
}
