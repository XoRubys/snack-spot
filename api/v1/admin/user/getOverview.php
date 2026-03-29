<?php

/**
 * 获取用户概览API
 * 用于管理端获取用户统计数据概览
 *
 * 功能说明：
 * - 验证管理员权限
 * - 统计总用户数
 * - 统计活跃用户数
 * - 统计新增用户数（本月）
 * - 统计本周消费总额
 * - 统计本月消费总额
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
$userTable = Database::table('user');
$orderTable = Database::table('order');

try {
    // 1. 统计总用户数
    $sql = "SELECT COUNT(*) as total_users FROM {$userTable}";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalUsers = intval($result['total_users']);

    // 2. 统计活跃用户数
    $sql = "SELECT COUNT(*) as active_users FROM {$userTable} WHERE status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $activeUsers = intval($result['active_users']);

    // 3. 统计新增用户数（本月）
    $currentYear = date('Y');
    $currentMonth = date('m');
    $monthStart = strtotime("{$currentYear}-{$currentMonth}-01 00:00:00");
    $monthEnd = strtotime("{$currentYear}-" . ($currentMonth + 1) . "-01 00:00:00") - 1;

    $sql = "SELECT COUNT(*) as new_users FROM {$userTable} WHERE create_time >= ? AND create_time <= ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newUsers = intval($result['new_users']);

    // 4. 统计本周消费总额
    $weekStart = strtotime('monday this week');
    $sql = "
        SELECT COALESCE(SUM(pay_amount), 0) as week_spend
        FROM {$orderTable}
        WHERE status = 'completed' AND create_time >= ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$weekStart]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $weekSpend = PriceFormatter::format(floatval($result['week_spend']));

    // 5. 统计本月消费总额
    $sql = "
        SELECT COALESCE(SUM(pay_amount), 0) as month_spend
        FROM {$orderTable}
        WHERE status = 'completed' AND create_time >= ? AND create_time <= ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthSpend = PriceFormatter::format(floatval($result['month_spend']));

    // 组装返回数据
    $data = [
        'totalUsers' => $totalUsers,           // 总用户数
        'activeUsers' => $activeUsers,        // 活跃用户数
        'newUsers' => $newUsers,              // 新增用户数（本月）
        'weekSpend' => $weekSpend,            // 本周消费总额
        'monthSpend' => $monthSpend           // 本月消费总额
    ];

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', $data);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取用户概览失败', [
        'error' => $e->getMessage()
    ]);
}
