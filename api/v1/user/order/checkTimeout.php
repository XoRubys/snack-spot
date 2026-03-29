<?php

/**
 * 订单超时检查API
 * 将创建时间超过4分钟的待支付订单状态改为已超时，并回退库存
 *
 * 功能说明：
 * - 无需用户验证，公共接口供定时任务调用
 * - 只处理状态为 pending（待支付）的订单
 * - 超时时间：创建时间超过4分钟
 * - 回退库存：根据批次ID恢复商品库存
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

Method::check('GET');

$db = Database::connect();
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');
$inventoryTable = Database::table('inventory');

$now = time();
$timeoutSeconds = 4 * 60;
$timeoutThreshold = $now - $timeoutSeconds;

try {
    $db->beginTransaction();

    // 1. 查询所有超时的待支付订单
    $stmt = $db->prepare("
        SELECT id
        FROM {$orderTable}
        WHERE status = 'pending'
          AND create_time < :timeoutThreshold
    ");
    $stmt->execute([
        ':timeoutThreshold' => $timeoutThreshold
    ]);
    $timeoutOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $affectedRows = 0;

    foreach ($timeoutOrders as $order) {
        $orderId = $order['id'];

        // 2. 恢复库存 - 根据批次ID回退
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

        // 3. 更新订单状态为已超时
        $stmt = $db->prepare("
            UPDATE {$orderTable}
            SET status = 'timeout',
                complete_time = ?
            WHERE id = ?
        ");
        $stmt->execute([$now, $orderId]);

        $affectedRows++;
    }

    $db->commit();

    echo JsonResponse::send(200, '订单超时检查完成', [
        'timeout_orders' => $affectedRows,
        'timeout_threshold' => date('Y-m-d H:i:s', $timeoutThreshold)
    ]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo JsonResponse::send(500, '处理失败', ['error' => $e->getMessage()]);
}
