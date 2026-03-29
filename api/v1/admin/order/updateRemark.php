<?php

/**
 * 管理端更新订单管理员备注 API
 * 用于管理员添加或修改订单备注
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

Method::check('POST');

$accessToken = Auth::validateAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$orderId = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
$remarkAdmin = trim($input['remarkAdmin'] ?? '');

if (empty($orderId) || $orderId <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单 ID 不能为空');
    exit;
}

$db = Database::connect();
$orderTable = Database::table('order');

try {
    $stmt = $db->prepare("SELECT id FROM {$orderTable} WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $orderId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo JsonResponse::send(404, '订单不存在');
        exit;
    }

    $updateStmt = $db->prepare("UPDATE {$orderTable} SET remark_admin = :remarkAdmin WHERE id = :id");
    $updateStmt->execute([
        ':remarkAdmin' => $remarkAdmin,
        ':id' => $orderId
    ]);

    echo JsonResponse::send(200, '更新成功');

} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '更新失败：' . $e->getMessage());
}