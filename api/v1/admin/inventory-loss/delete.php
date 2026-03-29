<?php

/**
 * 删除库损记录API
 * 用于管理员删除库损记录
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 根据ID删除库损记录
 * - 删除后不可恢复
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
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '库损ID不能为空');
    exit;
}

// 连接数据库
$db = Database::connect();
$lossTable = Database::table('inventory_loss');
$inventoryTable = Database::table('inventory');

// 检查库损记录是否存在并获取相关信息
$stmt = $db->prepare("SELECT id, batch_id, quantity FROM {$lossTable} WHERE id = ?");
$stmt->execute([$id]);
$lossRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lossRecord) {
    http_response_code(404);
    echo JsonResponse::send(404, '库损记录不存在');
    exit;
}

// 删除库损记录并恢复库存数量
try {
    // 开始事务
    $db->beginTransaction();

    // 恢复库存批次的剩余数量
    $stmt = $db->prepare("UPDATE {$inventoryTable} SET remaining_quantity = remaining_quantity + ? WHERE id = ?");
    $stmt->execute([$lossRecord['quantity'], $lossRecord['batch_id']]);

    // 删除库损记录
    $stmt = $db->prepare("DELETE FROM {$lossTable} WHERE id = ?");
    $stmt->execute([$id]);

    $db->commit();
    echo JsonResponse::send(200, '删除成功');
} catch (PDOException $e) {
    $db->rollBack();
    error_log('删除库损记录失败: ' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '删除失败，请稍后重试');
}
