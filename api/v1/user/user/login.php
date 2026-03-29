<?php

require_once __DIR__ . '/../../common.php';

// 引入命名空间
use SnackSpot\Core\Database;          // 引入数据库类
use SnackSpot\Utils\JsonResponse;     // 引入JSON响应类
use SnackSpot\Utils\RandomCode;       // 引入随机码类
use SnackSpot\Utils\Ip;               // 引入IP地址类
use SnackSpot\Utils\Method;            // 引入请求方法检查类

// 检查请求方法，只允许POST请求
Method::check('POST');

// 获取请求数据并解析为JSON
$input = JsonResponse::getInput();

// 获取手机号和密码参数
$phone = $input['phone'] ?? '';
$password = $input['password'] ?? '';

// 验证参数完整性
if (empty($phone) || empty($password)) {
   http_response_code(400);
   echo JsonResponse::send(400, '手机号和密码不能为空');
   exit;
}

// 验证手机号格式（中国大陆手机号）
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
   http_response_code(400);
   echo JsonResponse::send(400, '手机号格式错误');
   exit;
}

// 验证密码长度（8-20位）
if (strlen($password) < 8 || strlen($password) > 20) {
   http_response_code(400);
   echo JsonResponse::send(400, '密码长度必须为8-20位');
   exit;
}

// 连接数据库
$db = Database::connect();
// 获取用户表名
$table = Database::table('user');

try {
   // 查询用户信息
   $stmt = $db->prepare("
      SELECT
         id,               -- 用户ID
         username,         -- 用户名
         password_hash,    -- 密码哈希
         phone,            -- 手机号
         status,           -- 账号状态
         dormitory,        -- 寝室号
         level             -- 等级
      FROM {$table} 
      WHERE phone = ? -- 根据手机号查询用户
   ");
   $stmt->execute([$phone]);
   // 获取查询结果
   $user = $stmt->fetch();

   // 用户不存在
   if (!$user) {
      http_response_code(401);
      echo JsonResponse::send(401, '用户不存在');
      exit;
   }

   // 账号被封禁
   if ($user['status'] === 'inactive') {
      http_response_code(403);
      echo JsonResponse::send(403, '账号已被封禁');
      exit;
   }

   // 验证密码
   if (!password_verify($password, $user['password_hash'])) {
      http_response_code(401);
      echo JsonResponse::send(401, '密码错误');
      exit;
   }

   // 生成新的访问令牌
   $newAccessToken = RandomCode::create();
   // 获取最后登录IP和时间
   $lastLoginIp = Ip::getClientIp();
   $lastLoginTime = time();

   // 更新用户登录信息
   $stmt = $db->prepare("
      UPDATE {$table} 
      SET 
         access_token = ?,      -- 新的访问令牌
         last_login_ip = ?,     -- 最后登录IP
         last_login_time = ?    -- 最后登录时间
      WHERE id = ?              -- 根据用户ID更新
   ");
   $stmt->execute([
      $newAccessToken,        // 新的访问令牌
      $lastLoginIp,           // 最后登录IP
      $lastLoginTime,         // 最后登录时间
      $user['id']             // 用户ID
   ]);

   // 返回登录成功信息
   http_response_code(200);
   echo JsonResponse::send(200, '登录成功', [
      'accessToken' => $newAccessToken,
   ]);
} catch (PDOException $e) {
   // 数据库错误处理
   http_response_code(500);
   echo JsonResponse::send(500, '登录失败', [
      'error' => $e->getMessage()
   ]);
}
