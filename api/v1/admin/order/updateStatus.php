<?php

/**
 * 管理端修改订单状态 API
 * 用于管理员修改订单状态
 *
 * 功能说明：
 * - 验证管理员权限
 * - 修改订单状态
 * - 只允许修改待支付和配送中的订单
 * - 取消订单时根据批次ID回退商品库存
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许 POST 请求
Method::check('POST');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$orderId = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
$newStatus = trim($input['status'] ?? '');

if (empty($orderId) || $orderId <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单 ID 不能为空');
    exit;
}

// 验证状态值（管理员只能将订单改为：取消、完成）
$validStatuses = ['cancelled', 'completed'];
if (empty($newStatus) || !in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单状态无效');
    exit;
}

// 连接数据库
$db = Database::connect();

// 获取表名
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');
$inventoryTable = Database::table('inventory');

try {
    $db->beginTransaction();

    // 查询订单当前状态
    $stmt = $db->prepare("
        SELECT id, status, order_no
        FROM {$orderTable}
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo JsonResponse::send(404, '订单不存在');
        exit;
    }

    // 只允许修改待支付和配送中的订单
    if (!in_array($order['status'], ['pending', 'paid'])) {
        http_response_code(400);
        echo JsonResponse::send(400, '该订单状态不允许修改');
        exit;
    }

    $currentTime = time();

    // 如果订单被取消，回退库存
    if ($newStatus === 'cancelled') {
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
            $stmt->execute([$count, $currentTime, $inventoryId]);
        }
    }

    // 更新订单状态
    $updateStmt = $db->prepare("
        UPDATE {$orderTable}
        SET status = :status,
            complete_time = :completeTime
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':status' => $newStatus,
        ':completeTime' => $currentTime,
        ':id' => $orderId
    ]);

    $db->commit();

    // 返回成功响应
    echo JsonResponse::send(200, '修改成功', [
        'id' => intval($order['id']),
        'orderNo' => $order['order_no'],
        'status' => $newStatus
    ]);
} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo JsonResponse::send(500, '修改失败：' . $e->getMessage());
}
