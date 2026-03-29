<?php

/**
 * 管理端获取订单列表API
 * 用于管理端获取订单列表，支持按状态筛选和关键词搜索
 * 仅显示30天内的订单
 *
 * 功能说明：
 * - 验证管理员权限
 * - 支持按订单状态筛选
 * - 支持按订单号/商品名称/收货人搜索
 * - 仅查询30天内的订单
 * - 关联查询订单商品信息
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

// 检查请求方法，只允许GET请求
Method::check('GET');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 获取筛选参数
$status = trim($_GET['status'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

// 连接数据库
$db = Database::connect();

// 获取表名
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');

// 构建查询条件 - 仅查询30天内的订单
$whereConditions = [
    'o.create_time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))'
];
$params = [];

// 状态筛选
$validStatuses = ['pending', 'paid', 'timeout', 'completed', 'cancelled'];
if (!empty($status) && in_array($status, $validStatuses)) {
    $whereConditions[] = "o.status = :status";
    $params[':status'] = $status;
}

// 关键词搜索
if (!empty($keyword)) {
    $whereConditions[] = "(
        o.order_no LIKE :keyword 
        OR o.receiver_name LIKE :keyword 
        OR EXISTS (
            SELECT 1 FROM {$orderItemTable} oi 
            WHERE oi.order_id = o.id 
            AND oi.product_name LIKE :keyword
        )
    )";
    $params[':keyword'] = '%' . $keyword . '%';
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// 查询订单列表
$sql = "
    SELECT 
        o.id,
        o.order_no,
        o.trade_no,
        o.status,
        o.total_amount,
        o.pay_amount,
        o.remark_user,
        o.remark_admin,
        o.receiver_name,
        o.receiver_phone,
        o.receiver_address,
        o.payment_time,
        o.complete_time,
        o.create_time
    FROM {$orderTable} o
    {$whereClause}
    ORDER BY o.create_time DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orderList = [];
    if (!empty($orders)) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        // 查询订单商品
        $itemStmt = $db->prepare("
            SELECT 
                oi.id,
                oi.order_id,
                oi.product_id,
                oi.product_name,
                oi.product_image,
                oi.product_price,
                COUNT(*) as quantity
            FROM {$orderItemTable} oi
            WHERE oi.order_id IN ({$placeholders})
            GROUP BY oi.order_id, oi.product_id, oi.product_name, oi.product_image, oi.product_price
            ORDER BY oi.id ASC
        ");
        $itemStmt->execute($orderIds);
        $allItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // 按订单ID分组商品
        $itemsByOrder = [];
        foreach ($allItems as $item) {
            $itemsByOrder[$item['order_id']][] = [
                'id' => intval($item['id']),
                'productId' => intval($item['product_id']),
                'productName' => $item['product_name'],
                'productImage' => $item['product_image'],
                'productPrice' => PriceFormatter::format($item['product_price']),
                'quantity' => intval($item['quantity'])
            ];
        }

        // 格式化订单数据
        foreach ($orders as $order) {
            $orderId = intval($order['id']);
            $items = $itemsByOrder[$orderId] ?? [];
            
            // 获取第一个商品作为展示
            $firstItem = $items[0] ?? null;
            
            $orderList[] = [
                'id' => $orderId,
                'orderNo' => $order['order_no'],
                'tradeNo' => $order['trade_no'],
                'status' => $order['status'],
                'totalAmount' => PriceFormatter::format($order['total_amount']),
                'payAmount' => PriceFormatter::format($order['pay_amount']),
                'remarkUser' => $order['remark_user'],
                'remarkAdmin' => $order['remark_admin'],
                'receiverName' => $order['receiver_name'],
                'receiverPhone' => $order['receiver_phone'],
                'receiverAddress' => $order['receiver_address'],
                'paymentTime' => $order['payment_time'] ? date('Y-m-d H:i:s', $order['payment_time']) : '',
                'completeTime' => $order['complete_time'] ? date('Y-m-d H:i:s', $order['complete_time']) : '',
                'createTime' => $order['create_time'] ? date('Y-m-d H:i:s', $order['create_time']) : '',
                // 简化展示字段
                'productName' => $firstItem ? $firstItem['productName'] : '',
                'productImage' => $firstItem ? $firstItem['productImage'] : '',
                'quantity' => $firstItem ? $firstItem['quantity'] : 0,
                'itemCount' => count($items),
                'items' => $items
            ];
        }
    }

    // 返回成功响应
    echo JsonResponse::send(200, '获取成功', [
        'list' => $orderList,
        'total' => count($orderList)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取订单列表失败: ' . $e->getMessage());
}
