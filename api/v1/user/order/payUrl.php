<?php

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Core\ShopStatus;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

Method::check('GET');
ShopStatus::check();

$accessToken = Auth::validateToken();

// 获取订单ID
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单ID无效');
    exit;
}

// 连接数据库
$db = Database::connect();
$userTable = Database::table('user');
$orderTable = Database::table('order');

// 支付URL前缀配置，这里我自己部署了码支付 https://gitee.com/technical-laohu/mpay
const PAY_URL_PREFIX = 'https://pay.youdomain.com/Pay/console/';

try {
    // 获取用户ID
    $stmt = $db->prepare("
        SELECT id
        FROM {$userTable}
        WHERE access_token = ?
        LIMIT 1
    ");
    $stmt->execute([$accessToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = (int) $user['id'];

    // 查询订单信息（验证订单属于当前用户且状态为待支付）
    $stmt = $db->prepare("
        SELECT trade_no, status 
        FROM {$orderTable} 
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo JsonResponse::send(404, '订单不存在');
        exit;
    }

    if ($order['status'] !== 'pending') {
        http_response_code(400);
        echo JsonResponse::send(400, '订单状态错误，无法支付');
        exit;
    }

    // 拼接支付URL
    $payUrl = PAY_URL_PREFIX . $order['trade_no'];

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'payUrl' => $payUrl
    ]);
} catch (PDOException $e) {
    error_log('获取支付URL失败：' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '服务器繁忙，请稍后重试');
} catch (Exception $e) {
    error_log('获取支付URL未知错误：' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '系统错误，请稍后重试');
}
