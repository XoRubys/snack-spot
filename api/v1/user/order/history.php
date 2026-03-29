<?php

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Core\ShopStatus;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

Method::check('GET');

$accessToken = Auth::validateToken();
ShopStatus::check();

$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

$validStatuses = ['pending', 'paid', 'timeout', 'completed', 'cancelled'];
if ($status !== null && !in_array($status, $validStatuses, true)) {
    http_response_code(400);
    echo JsonResponse::send(400, '无效的状态参数');
    exit;
}

$db = Database::connect();
$userTable = Database::table('user');
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');

try {
    $stmt = $db->prepare("
        SELECT id
        FROM {$userTable}
        WHERE access_token = ?
        LIMIT 1
    ");
    $stmt->execute([$accessToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = (int) $user['id'];

    $conditions = ['user_id = ?', 'create_time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))'];
    $params = [$userId];

    if ($status !== null) {
        $conditions[] = 'status = ?';
        $params[] = $status;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $conditions);

    $orderSql = "
        SELECT 
            id, order_no, trade_no, status,
            total_amount, pay_amount,
            remark_user, remark_admin,
            receiver_name, receiver_phone, receiver_address,
            payment_time, complete_time, create_time
        FROM {$orderTable}
        {$whereClause}
        ORDER BY create_time DESC
    ";

    $orderStmt = $db->prepare($orderSql);
    $orderStmt->execute($params);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    $orderList = [];
    if (!empty($orders)) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        $itemStmt = $db->prepare("
            SELECT 
                MIN(id) as id,
                order_id,
                product_id,
                product_name as name,
                product_image as image,
                product_price as price,
                COUNT(*) as qty
            FROM {$orderItemTable}
            WHERE order_id IN ({$placeholders})
            GROUP BY order_id, product_id, product_name, product_image, product_price
            ORDER BY MIN(id) ASC
        ");
        $itemStmt->execute($orderIds);
        $allItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $itemsByOrder = [];
        foreach ($allItems as $item) {
            $unitPrice = floatval($item['price']);
            $quantity = (int) $item['qty'];
            $totalItemPrice = $unitPrice * $quantity;
            $itemsByOrder[$item['order_id']][] = [
                'id' => (int) $item['id'],
                'productId' => (int) $item['product_id'],
                'name' => $item['name'],
                'image' => $item['image'],
                'price' => PriceFormatter::format($totalItemPrice),
                'unitPrice' => PriceFormatter::format($unitPrice),
                'quantity' => $quantity,
                'totalPrice' => PriceFormatter::format($totalItemPrice)
            ];
        }

        foreach ($orders as $order) {
            $orderId = (int) $order['id'];
            $orderTotalAmount = floatval($order['total_amount']);
            $orderPayAmount = floatval($order['pay_amount']);

            // 配送费 = 实付金额 - 商品总价
            $deliveryFee = $orderPayAmount - $orderTotalAmount;

            $orderList[] = [
                'id' => $orderId,
                'orderNo' => $order['order_no'],
                'tradeNo' => $order['trade_no'],
                'status' => $order['status'],
                'payAmount' => PriceFormatter::format($orderPayAmount),
                'totalAmount' => PriceFormatter::format($orderTotalAmount),
                'deliveryFee' => PriceFormatter::format($deliveryFee),
                'createTime' => $order['create_time'] ? date('Y-m-d H:i:s', $order['create_time']) : '',
                'payTime' => $order['payment_time'] ? date('Y-m-d H:i:s', $order['payment_time']) : '',
                'completeTime' => $order['complete_time'] ? date('Y-m-d H:i:s', $order['complete_time']) : '',
                'userRemark' => $order['remark_user'] ?? '',
                'adminRemark' => $order['remark_admin'] ?? '',
                'goodsList' => $itemsByOrder[$orderId] ?? []
            ];
        }
    }

    http_response_code(200);
    echo JsonResponse::send(200, 'success', [
        'list' => $orderList,
        'total' => count($orderList)
    ]);
} catch (PDOException $e) {
    error_log('订单列表查询失败：' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '服务器繁忙，请稍后重试');
} catch (Exception $e) {
    error_log('订单列表未知错误：' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '系统错误，请稍后重试');
}
