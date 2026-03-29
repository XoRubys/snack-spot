<?php

namespace SnackSpot\Core;

use PDO;
use PDOException;
use SnackSpot\Utils\JsonResponse;

class Database
{
   // 单例模式：保存数据库连接实例
   private static ?PDO $connection = null;
   // 保存数据库配置信息
   private static array $config = [];

   /**
    * 加载数据库配置文件
    * 使用单例模式确保配置只加载一次
    */
   private static function loadConfig()
   {
      if (empty(self::$config)) {
         self::$config = require __DIR__ . '/../config/database.php';
      }
   }

   /**
    * 获取数据库连接
    * 使用单例模式确保全局只有一个数据库连接实例
    * @return PDO 返回PDO数据库连接对象
    */
   public static function connect()
   {
      if (self::$connection === null) {
         self::loadConfig();
         try {
            $host = self::$config['host'];
            $dbname = self::$config['database'];
            $username = self::$config['username'];
            $password = self::$config['password'];
            $charset = self::$config['charset'];
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            self::$connection = new PDO($dsn, $username, $password, [
               PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES => false,
            ]);
         } catch (PDOException $e) {
            http_response_code(500);
            echo JsonResponse::send(500, 'database Connection Failed', [
               'error' => $e->getMessage()
            ]);
            exit;
         }
      }
      return self::$connection;
   }

   /**
    * 获取带前缀的表名
    * @param string $tableName 表名（不包含前缀）
    * @return string 返回带前缀的完整表名
    */
   public static function table(string $tableName)
   {
      self::loadConfig();
      return self::$config['prefix'] . $tableName;
   }
}
