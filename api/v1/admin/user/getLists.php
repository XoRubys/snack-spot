<?php

/**
 * 管理端获取用户列表API
 * 用于管理端获取用户列表，支持按状态、等级筛选和关键词搜索
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持按用户状态（active/inactive）筛选
 * - 支持按用户等级（user/admin）筛选
 * - 支持按用户名/手机号搜索
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许GET请求
Method::check('GET');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 获取筛选参数
$status = trim($_GET['status'] ?? '');
$level = trim($_GET['level'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

// 连接数据库
$db = Database::connect();

// 获取表名
$userTable = Database::table('user');

// 构建查询条件
$whereConditions = [];
$params = [];

// 状态筛选
if (!empty($status) && in_array($status, ['active', 'inactive'])) {
    $whereConditions[] = "status = :status";
    $params[':status'] = $status;
}

// 等级筛选
if (!empty($level) && in_array($level, ['user', 'admin'])) {
    $whereConditions[] = "level = :level";
    $params[':level'] = $level;
}

// 关键词搜索（用户名或手机号）
if (!empty($keyword)) {
    $whereConditions[] = "(username LIKE :keyword OR phone LIKE :keyword)";
    $params[':keyword'] = '%' . $keyword . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 查询用户列表
$sql = "
    SELECT
        id,
        username,
        phone,
        level,
        status,
        dormitory,
        last_login_time,
        create_time
    FROM {$userTable}
    {$whereClause}
    ORDER BY create_time DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取订单表名
    $orderTable = Database::table('order');

    // 计算本周和本月的起始时间戳
    $now = time();
    $weekStart = strtotime('monday this week');
    $monthStart = strtotime(date('Y-m-01'));

    // 格式化用户列表，添加消费额统计
    $userList = array_map(function ($item) use ($db, $orderTable, $weekStart, $monthStart) {
        $userId = intval($item['id']);

        // 查询本周消费额（已完成的订单）
        $weekStmt = $db->prepare("
            SELECT COALESCE(SUM(pay_amount), 0) as spend
            FROM {$orderTable}
            WHERE user_id = ? AND status = 'completed' AND create_time >= ?
        ");
        $weekStmt->execute([$userId, $weekStart]);
        $weekSpend = number_format(floatval($weekStmt->fetch(PDO::FETCH_ASSOC)['spend']), 2, '.', '');

        $monthStmt = $db->prepare("
            SELECT COALESCE(SUM(pay_amount), 0) as spend
            FROM {$orderTable}
            WHERE user_id = ? AND status = 'completed' AND create_time >= ?
        ");
        $monthStmt->execute([$userId, $monthStart]);
        $monthSpend = number_format(floatval($monthStmt->fetch(PDO::FETCH_ASSOC)['spend']), 2, '.', '');

        return [
            'id' => $userId,
            'username' => $item['username'],
            'phone' => $item['phone'],
            'level' => $item['level'],
            'status' => $item['status'],
            'dormitory' => intval($item['dormitory']),
            'lastLoginTime' => intval($item['last_login_time']),
            'createTime' => intval($item['create_time']),
            'weekSpend' => $weekSpend,
            'monthSpend' => $monthSpend
        ];
    }, $users);

    // 返回成功响应
    echo JsonResponse::send(200, '获取成功', [
        'list' => $userList,
        'total' => count($userList)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取用户列表失败: ' . $e->getMessage());
}
