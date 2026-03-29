<?php
/*
 * IP工具
 */
namespace SnackSpot\Utils;

class Ip
{
   // 获取客户端IP
   public static function getClientIp()
   {
      $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      return $ip;
   }
}
