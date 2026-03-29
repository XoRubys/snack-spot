<?php

/**
 * 支付异步通知 API
 * 接收支付平台的异步通知，更新订单状态
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Pay;
use SnackSpot\Core\Mail;
use SnackSpot\Utils\PriceFormatter;

// 获取所有 GET 参数
$data = $_GET;

// 解析通知数据
$notifyData = Pay::parseNotify($data);

// 验证支付状态
if ($notifyData['trade_status'] !== 'TRADE_SUCCESS') {
    echo 'status error';
    exit;
}

// 连接数据库
$db = Database::connect();
$orderTable = Database::table('order');

try {
    // 查询订单（out_trade_no 是我们系统的订单号）
    $stmt = $db->prepare("
        SELECT id, status, pay_amount
        FROM {$orderTable}
        WHERE order_no = ?
        LIMIT 1
    ");
    $stmt->execute([$notifyData['out_trade_no']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo 'order not found';
        exit;
    }

    // 验证订单状态（已支付或已完成的订单不再处理）
    if (in_array($order['status'], ['paid', 'completed'], true)) {
        echo 'success';
        exit;
    }

    // 验证金额（使用 PriceFormatter 确保精度一致，允许最多 0.03 元差额）
    $payAmount = floatval(PriceFormatter::format($notifyData['money']));
    $orderAmount = floatval(PriceFormatter::format($order['pay_amount']));
    if ($payAmount < $orderAmount || ($payAmount - $orderAmount) > 0.03) {
        echo 'amount error';
        exit;
    }

    // 更新订单状态为已支付
    $updateStmt = $db->prepare("
        UPDATE {$orderTable}
        SET status = 'paid',
            payment_time = ?,
            complete_time = UNIX_TIMESTAMP()
        WHERE id = ?
    ");
    $updateStmt->execute([
        time(),
        $order['id']
    ]);

    // 返回 success 表示处理成功
    Mail::send($order['id']);
    echo 'success';
    exit;
} catch (\Exception $e) {
    echo 'server error';
    exit;
}
