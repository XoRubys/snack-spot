<?php

/**
 * 用户信息更新API
 * 用户修改个人资料（用户名、手机号、宿舍地址）
 * 
 * 功能说明：
 * - 验证用户身份
 * - 更新用户基本信息
 * - 支持选择性更新（手机号和宿舍地址可选）
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Auth;
use SnackSpot\Core\Database;
use SnackSpot\Core\ShopStatus;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许POST请求
Method::check('POST');

// 验证用户访问令牌
$accessToken = Auth::validateToken();
ShopStatus::check();

// 解析请求数据
$input = JsonResponse::getInput();

$username = $input['username'] ?? '';
$phone = $input['phone'] ?? '';
$dormitory = $input['dormitory'] ?? '';

// 验证参数完整性
if (empty($username) || empty($dormitory)) {
   http_response_code(400);
   echo JsonResponse::send(400, '参数不完整');
   exit;
}

// 验证用户名长度
if (mb_strlen($username) > 25) {
   http_response_code(400);
   echo JsonResponse::send(400, '姓名长度不能超过25个字符');
   exit;
}

// 验证手机号格式（如果提供了手机号）
if (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
   http_response_code(400);
   echo JsonResponse::send(400, '手机号格式错误');
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
$table = Database::table('user');

try {
   // 更新用户信息
   $stmt = $db->prepare("
      UPDATE {$table} 
      SET 
         username = ?,
         phone = ?,
         dormitory = ?
      WHERE access_token = ?
   ");

   $stmt->execute([
      $username,
      $phone,
      $dormitory,
      $accessToken
   ]);

   echo JsonResponse::send(200, '信息更新成功');
} catch (PDOException $e) {
   http_response_code(500);
   echo JsonResponse::send(500, '信息更新失败', [
      'error' => $e->getMessage()
   ]);
   exit;
}
