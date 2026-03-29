<?php

namespace SnackSpot\Utils;

class RandomCode
{
   public static function create()
   {
      return bin2hex(random_bytes(16));
   }

   public static function generateOrderNo()
   {
      $timestamp = date('ymdHis');
      $random = mt_rand(1000, 9999);
      return "$timestamp$random";
   }
}
