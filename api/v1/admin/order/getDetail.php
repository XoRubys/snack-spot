<?php

/**
 * 管理端获取订单详情 API
 * 用于管理端获取订单详细信息
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 根据订单 ID 获取订单详情
 * - 包含订单信息、商品信息、用户信息、收货信息
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

// 检查请求方法，只允许 GET 请求
Method::check('GET');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 获取订单 ID
$orderId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (empty($orderId) || $orderId <= 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单 ID 不能为空');
    exit;
}

// 连接数据库
$db = Database::connect();

// 获取表名
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');
$userTable = Database::table('user');

try {
    // 查询订单详情
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.order_no,
            o.user_id,
            o.total_amount,
            o.pay_amount,
            o.status,
            o.receiver_name,
            o.receiver_phone,
            o.receiver_address,
            o.remark_user,
            o.remark_admin,
            o.create_time,
            o.payment_time,
            o.complete_time,
            u.username
        FROM {$orderTable} o
        LEFT JOIN {$userTable} u ON o.user_id = u.id
        WHERE o.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo JsonResponse::send(404, '订单不存在');
        exit;
    }

    // 查询订单商品信息（按商品名称和价格分组统计数量）
    $itemStmt = $db->prepare("
        SELECT 
            product_name,
            product_price,
            COUNT(*) as quantity
        FROM {$orderItemTable}
        WHERE order_id = :order_id
        GROUP BY product_name, product_price
    ");
    $itemStmt->execute([':order_id' => $orderId]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    // 构建商品列表字符串
    $productNames = [];
    $productSpecs = [];
    $totalQuantity = 0;
    foreach ($items as $item) {
        $productNames[] = $item['product_name'];
        $productSpecs[] = $item['product_name'] . ' x' . $item['quantity'] . ' (¥' . PriceFormatter::format($item['product_price']) . ')';
        $totalQuantity += intval($item['quantity']);
    }

    // 格式化订单信息（时间戳转换为可读格式）
    $orderDetail = [
        'id' => intval($order['id']),
        'orderNo' => $order['order_no'],
        'status' => $order['status'],
        'createTime' => $order['create_time'] ? date('Y-m-d H:i:s', $order['create_time']) : '',
        'payTime' => $order['payment_time'] ? date('Y-m-d H:i:s', $order['payment_time']) : '',
        'completeTime' => $order['complete_time'] ? date('Y-m-d H:i:s', $order['complete_time']) : '',
        'productName' => !empty($productNames) ? implode(', ', $productNames) : '未知商品',
        'productSpec' => !empty($productSpecs) ? implode('; ', $productSpecs) : '',
        'quantity' => $totalQuantity,
        'items' => $items,
        'totalAmount' => PriceFormatter::format($order['total_amount']),
        'payAmount' => PriceFormatter::format($order['pay_amount']),
        'receiverName' => $order['receiver_name'],
        'receiverPhone' => $order['receiver_phone'],
        'receiverAddress' => $order['receiver_address'],
        'remark' => $order['remark_user'] ?? '',
        'remarkAdmin' => $order['remark_admin'] ?? '',
        'userId' => intval($order['user_id']),
        'username' => $order['username'] ?? ''
    ];

    // 返回成功响应
    echo JsonResponse::send(200, '获取成功', $orderDetail);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取订单详情失败：' . $e->getMessage());
}
