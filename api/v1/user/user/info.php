<?php

/**
 * 用户信息查询API
 * 获取当前登录用户的详细信息和消费统计
 * 
 * 功能说明：
 * - 获取用户基本信息（用户名、手机号、宿舍地址等）
 * - 计算今日、本周、本月的消费金额
 * - 获取管理员配置的地址信息
 */

require_once __DIR__ . '/../../common.php';     // 引入公共文件

use SnackSpot\Core\Auth;                           // 引入认证类
use SnackSpot\Core\Database;                       // 引入数据库类
use SnackSpot\Core\ShopStatus;
use SnackSpot\Utils\JsonResponse;                  // 引入JSON响应类
use SnackSpot\Utils\Method;                        // 引入请求方法检查类
use SnackSpot\Utils\PriceFormatter;                // 引入价格格式化类

// 检查请求方法，只允许GET请求
Method::check('GET');

// 验证用户访问令牌
$accessToken = Auth::validateToken();
ShopStatus::check();

// 连接数据库并获取表名
$db = Database::connect();          // 连接数据库
$table = Database::table('user');   // 用户表
$orderTable = Database::table('order'); // 订单表

try {
   // 1. 查询用户基本信息
   $stmt = $db->prepare("
      SELECT
         id,               -- 用户ID
         username,         -- 用户名
         phone,            -- 手机号
         dormitory,        -- 宿舍号
         level,            -- 等级
         create_time,      -- 创建时间
         last_login_time   -- 最后登录时间
      FROM {$table} 
      WHERE access_token = ?
   ");                                   // 准备查询语句
   $stmt->execute([$accessToken]);       // 执行查询
   $user = $stmt->fetch();               // 获取用户信息

   if (!$user) {
      http_response_code(404);                                  // 404 Not Found
      echo JsonResponse::send(404, '用户不存在');             // 响应404001 User Does Not Exist
      exit;
   }

   // 2. 获取管理员配置的地址信息
   $configTable = Database::table('config');             // 配置表
   $configStmt = $db->prepare("SELECT value FROM {$configTable} WHERE name = 'address' LIMIT 1");   // 准备查询语句
   $configStmt->execute();                               // 执行查询
   $addressConfig = $configStmt->fetch();                // 获取地址配置
   $address = $addressConfig['value'] ?? '';             // 获取地址值，若为空则设为空字符串
   $fullAddress = $address . $user['dormitory'];         // 拼接完整地址

   // 3. 计算时间范围
   $now = time();
   $todayStart = strtotime(date('Y-m-d 00:00:00', $now));
   $todayEnd = strtotime(date('Y-m-d 23:59:59', $now));
   $weekStart = strtotime('monday this week 00:00:00', $now);
   $weekEnd = strtotime('sunday this week 23:59:59', $now);
   $monthStart = strtotime(date('Y-m-01 00:00:00', $now));
   $monthEnd = strtotime(date('Y-m-t 23:59:59', $now));

   // 4. 查询用户消费统计（仅统计已完成的订单，按完成时间计算）
   $spendStmt = $db->prepare("
      SELECT 
         SUM(CASE WHEN complete_time >= ? AND complete_time <= ? THEN pay_amount ELSE 0 END) as today_spend,
         SUM(CASE WHEN complete_time >= ? AND complete_time <= ? THEN pay_amount ELSE 0 END) as week_spend,
         SUM(CASE WHEN complete_time >= ? AND complete_time <= ? THEN pay_amount ELSE 0 END) as month_spend
      FROM {$orderTable}
      WHERE user_id = ? AND status = 'completed'
   ");
   $spendStmt->execute([
      $todayStart,
      $todayEnd,
      $weekStart,
      $weekEnd,
      $monthStart,
      $monthEnd,
      $user['id']
   ]);
   $spendData = $spendStmt->fetch();

   // 5. 查询各状态订单数量
   $orderCountStmt = $db->prepare("
      SELECT 
         status,
         COUNT(*) as count
      FROM {$orderTable}
      WHERE user_id = ?
      GROUP BY status
   ");
   $orderCountStmt->execute([$user['id']]);
   $orderCounts = $orderCountStmt->fetchAll(PDO::FETCH_KEY_PAIR);

   // 6. 返回用户信息和消费统计
   echo JsonResponse::send(200, 'success', [             // 响应200 success
      'username' => $user['username'],                   // 用户名
      'phone' => $user['phone'],                         // 手机号
      'address' => $fullAddress,                         // 完整地址
      'dormitory' => $user['dormitory'],                 // 宿舍号
      'level' => $user['level'],                         // 等级
      'createTime' => $user['create_time'],              // 创建时间
      'lastLoginTime' => $user['last_login_time'],       // 最后登录时间
      'spend' => [
         'today' => PriceFormatter::format($spendData['today_spend'] ?? 0),
         'week' => PriceFormatter::format($spendData['week_spend'] ?? 0),
         'month' => PriceFormatter::format($spendData['month_spend'] ?? 0)
      ],
      'orderCount' => [
         'pending' => (int)($orderCounts['pending'] ?? 0),
         'paid' => (int)($orderCounts['paid'] ?? 0)
      ]
   ]);
} catch (Exception $e) {
   http_response_code(500);                                 // 500 internal Server Error
   echo JsonResponse::send(500, '服务器内部错误');            // 响应500 internal Server Error
   exit;
}
