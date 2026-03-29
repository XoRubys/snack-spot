<?php

/**
 * 订单取消API
 * 用户取消待支付订单
 * 
 * 功能说明：
 * - 验证用户身份和订单归属
 * - 只允许取消状态为"待支付"的订单
 * - 取消订单时自动恢复库存
 * - 使用事务确保数据一致性
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Core\ShopStatus;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许POST请求
Method::check('POST');

// 验证用户访问令牌
$accessToken = Auth::validateToken();
ShopStatus::check();

// 获取请求数据
$input = JsonResponse::getInput();
$orderId = intval($input['id'] ?? 0);

// 验证订单ID有效性
if ($orderId <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单ID无效');
    exit;
}

// 连接数据库并获取表名
$db = Database::connect();
$userTable = Database::table('user');
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');
$inventoryTable = Database::table('inventory');

try {
    // 开始事务
    $db->beginTransaction();
    $now = time();

    // 1. 获取用户ID
    $stmt = $db->prepare("SELECT id FROM {$userTable} WHERE access_token = ?");
    $stmt->execute([$accessToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo JsonResponse::send(401, '用户不存在');
        exit;
    }
    $userId = $user['id'];

    // 2. 查询订单信息
    $stmt = $db->prepare("
        SELECT id, status, user_id 
        FROM {$orderTable} 
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo JsonResponse::send(404, '订单不存在');
        exit;
    }

    // 3. 验证订单归属
    if ($order['user_id'] != $userId) {
        http_response_code(403);
        echo JsonResponse::send(403, '无权操作该订单');
        exit;
    }

    // 4. 只有待支付订单可以取消
    if ($order['status'] !== 'pending') {
        http_response_code(400);
        echo JsonResponse::send(400, '该订单状态不允许取消');
        exit;
    }

    // 5. 恢复库存
    $stmt = $db->prepare("
        SELECT inventory_id, COUNT(*) as count
        FROM {$orderItemTable}
        WHERE order_id = ?
        GROUP BY inventory_id
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $inventoryId = $item['inventory_id'];
        $count = intval($item['count']);

        $stmt = $db->prepare("
            UPDATE {$inventoryTable}
            SET remaining_quantity = remaining_quantity + ?,
                update_time = ?
            WHERE id = ?
        ");
        $stmt->execute([$count, $now, $inventoryId]);
    }

    // 6. 更新订单状态为已取消，并记录取消时间
    $stmt = $db->prepare("
        UPDATE {$orderTable}
        SET status = 'cancelled',
            complete_time = ?
        WHERE id = ?
    ");
    $stmt->execute([$now, $orderId]);

    // 提交事务
    $db->commit();

    http_response_code(200);
    echo JsonResponse::send(200, '订单取消成功');
} catch (Exception $e) {
    // 回滚事务
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo JsonResponse::send(500, '取消订单失败', ['error' => $e->getMessage()]);
}
