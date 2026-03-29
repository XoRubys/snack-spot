<?php

/**
 * 获取订单概览API
 * 用于管理端获取订单统计数据概览
 *
 * 功能说明：
 * - 验证管理员权限
 * - 统计总订单数
 * - 统计已完成订单数
 * - 统计待处理订单数
 * - 统计本月订单数
 * - 统计本月销售总额
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

// 连接数据库
$db = Database::connect();

// 获取表名
$orderTable = Database::table('order');

try {
    // 1. 统计总订单数
    $sql = "SELECT COUNT(*) as total_orders FROM {$orderTable}";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalOrders = intval($result['total_orders']);

    // 2. 统计已完成订单数
    $sql = "SELECT COUNT(*) as completed_orders FROM {$orderTable} WHERE status = 'completed'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completedOrders = intval($result['completed_orders']);

    // 3. 统计待处理订单数（pending + paid）
    $sql = "SELECT COUNT(*) as pending_orders FROM {$orderTable} WHERE status IN ('pending', 'paid')";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pendingOrders = intval($result['pending_orders']);

    // 4. 统计本月订单数
    $currentYear = date('Y');
    $currentMonth = date('m');
    $monthStart = strtotime("{$currentYear}-{$currentMonth}-01 00:00:00");
    $monthEnd = strtotime("{$currentYear}-" . ($currentMonth + 1) . "-01 00:00:00") - 1;

    $sql = "SELECT COUNT(*) as month_orders FROM {$orderTable} WHERE create_time >= ? AND create_time <= ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthOrders = intval($result['month_orders']);

    // 5. 统计本月销售总额（已完成的订单）
    $sql = "
        SELECT COALESCE(SUM(pay_amount), 0) as month_sales
        FROM {$orderTable}
        WHERE status = 'completed' AND create_time >= ? AND create_time <= ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthSales = PriceFormatter::format(floatval($result['month_sales']));

    // 组装返回数据
    $data = [
        'totalOrders' => $totalOrders,           // 总订单数
        'completedOrders' => $completedOrders,   // 已完成订单数
        'pendingOrders' => $pendingOrders,      // 待处理订单数
        'monthOrders' => $monthOrders,          // 本月订单数
        'monthSales' => $monthSales             // 本月销售总额
    ];

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', $data);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取订单概览失败', [
        'error' => $e->getMessage()
    ]);
}
