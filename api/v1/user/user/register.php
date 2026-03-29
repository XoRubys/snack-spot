<?php

/**
 * 用户注册API
 * 用于处理用户注册请求，验证参数并创建新用户
 * 
 * 功能说明：
 * - 验证用户输入参数的完整性和格式
 * - 检查手机号是否已注册
 * - 使用密码哈希加密
 * - 生成访问令牌
 * - 记录用户创建时间和IP
 */

// 引入公共文件
require_once __DIR__ . '/../../common.php';

// 引入命名空间
use SnackSpot\Core\Database;        // 引入数据库类
use SnackSpot\Utils\JsonResponse;   // 引入JSON响应类
use SnackSpot\Utils\RandomCode;     // 引入随机码类
use SnackSpot\Utils\Ip;             // 引入IP地址类
use SnackSpot\Utils\Method;          // 引入请求方法检查类

// 检查请求方法，只允许POST请求
Method::check('POST');

// 获取请求数据并解析为JSON
$input = JsonResponse::getInput();

// 获取注册参数
$username = trim($input['username'] ?? '');   // 用户名
$password = $input['password'] ?? '';         // 密码
$phone = $input['phone'] ?? '';               // 手机号
$dormitory = $input['dormitory'] ?? '';       // 寝室号

// 验证参数完整性
if (empty($username) || empty($password) || empty($phone) || empty($dormitory)) {
   http_response_code(400);
   echo JsonResponse::send(400, '参数不完整');
   exit;
}

// 验证用户名长度
if (mb_strlen($username) > 25) {
   http_response_code(400);
   echo JsonResponse::send(400, '用户名长度不能超过25个字符');
   exit;
}

// 验证密码长度
if (strlen($password) < 8) {
   http_response_code(400);
   echo JsonResponse::send(400, '密码长度不能小于8个字符');
   exit;
}

if (strlen($password) > 20) {
   http_response_code(400);
   echo JsonResponse::send(400, '密码长度不能超过20个字符');
   exit;
}

// 验证密码格式（仅允许字母大小写和数字）
if (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
   http_response_code(400);
   echo JsonResponse::send(400, '密码只能包含字母和数字');
   exit;
}

// 验证手机号格式（中国大陆手机号）
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
   http_response_code(400);
   echo JsonResponse::send(400, '手机号格式错误，必须为11位中国手机号');
   exit;
}

// 验证寝室号格式（1-3位数字）
if (!preg_match('/^\d{1,3}$/', $dormitory)) {
   http_response_code(400);
   echo JsonResponse::send(400, '寝室号格式错误，只能为1-3位数字');
   exit;
}

// 连接数据库
$db = Database::connect();
// 获取用户表名
$table = Database::table('user');

try {
   // 检查手机号是否已注册
   $stmt = $db->prepare("SELECT id FROM {$table} WHERE phone = ? -- 查询手机号是否存在");
   $stmt->execute([$phone]);
   if ($stmt->fetch()) {
      http_response_code(400);
      echo JsonResponse::send(400, '该手机号已注册');
      exit;
   }

   // 生成访问令牌
   $accessToken = RandomCode::create();
   // 密码哈希加密
   $passwordHash = password_hash($password, PASSWORD_DEFAULT);
   // 获取当前时间和IP
   $createTime = time();
   $clientIp = Ip::getClientIp();
   $createIp = $clientIp;
   $lastLoginIp = $clientIp;
   $lastLoginTime = time();

   // 插入用户数据
   $stmt = $db->prepare("
      INSERT INTO {$table} (
         access_token,      -- 访问令牌
         username,          -- 用户名
         password_hash,     -- 密码哈希
         phone,             -- 手机号
         dormitory,         -- 寝室号
         last_login_ip,     -- 最后登录IP
         last_login_time,   -- 最后登录时间
         create_time,       -- 创建时间
         create_ip          -- 创建IP
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
   ");
   $stmt->execute([
      $accessToken,
      $username,
      $passwordHash,
      $phone,
      $dormitory,
      $lastLoginIp,
      $lastLoginTime,
      $createTime,
      $createIp
   ]);

   // 获取用户ID
   $userId = $db->lastInsertId();

   // 返回注册成功信息（包含登录所需的所有信息）
   http_response_code(200);
   echo JsonResponse::send(200, '注册成功', [
      'accessToken' => $accessToken
   ]);
} catch (PDOException $e) {
   // 数据库错误处理
   http_response_code(500);
   echo JsonResponse::send(500, '注册失败', [
      'error' => $e->getMessage()
   ]);
}
