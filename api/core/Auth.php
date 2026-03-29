<?php

// 认证类
namespace SnackSpot\Core;

// 引入类
use SnackSpot\Utils\RandomCode;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Core\Database;

class Auth
{
   // 创建随机码令牌
   public static function create()
   {
      return RandomCode::create();
   }

   // 检查令牌是否有效
   // 返回值：valid、not_exist、disabled、expired
   public static function check($accessToken)
   {
      $db = Database::connect();
      $tableName = Database::table('user');

      $stmt = $db->prepare("
         SELECT id, status, last_login_time
         FROM {$tableName}
         WHERE access_token = ?
      ");
      $stmt->execute([$accessToken]);
      $user = $stmt->fetch();

      if (!$user) {
         return 'not_exist';
      }

      if ($user['status'] === 'inactive') {
         return 'disabled';
      }

      $ninetyDays = 90 * 24 * 60 * 60;
      if (time() - $user['last_login_time'] > $ninetyDays) {
         return 'expired';
      }

      return 'valid';
   }

   // 获取令牌
   public static function getToken()
   {
      $authHeader = '';

      if (function_exists('getallheaders')) {
         $headers = getallheaders();
         $authHeader = $headers['Authorization'] ?? '';
      }

      if (empty($authHeader)) {
         $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
      }

      if (empty($authHeader)) {
         $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
      }

      if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
         return null;
      }

      return $matches[1];
   }

   // 验证令牌是否有效
   public static function validateToken()
   {
      $accessToken = self::getToken();
      if (!$accessToken) {
         http_response_code(401);
         echo JsonResponse::send(401, '需要访问令牌，请登录');
         exit;
      }
      $checkResult = self::check($accessToken);
      switch ($checkResult) {
         case 'not_exist':
            http_response_code(401);
            echo JsonResponse::send(401, '令牌不存在，请重新登录');
            exit;
         case 'disabled':
            http_response_code(403);
            echo JsonResponse::send(403, '账号已被禁用');
            exit;
         case 'expired':
            http_response_code(401);
            echo JsonResponse::send(401, '令牌已过期，请重新登录');
            exit;
      }
      return $accessToken;
   }

   // 验证管理员权限
   public static function validateAdmin()
   {
      $accessToken = self::validateToken();
      $db = Database::connect();
      $tableName = Database::table('user');
      $stmt = $db->prepare("
         SELECT level
         FROM {$tableName}
         WHERE access_token = ?
      ");
      $stmt->execute([$accessToken]);
      $user = $stmt->fetch();
      if (!$user || $user['level'] !== 'admin') {
         http_response_code(403);
         echo JsonResponse::send(403, '需要管理员权限');
         exit;
      }
      return $accessToken;
   }
}
