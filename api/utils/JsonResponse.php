<?php

namespace SnackSpot\Utils;

class JsonResponse
{

   public static function send($code = 200, $message = '', $data = [])
   {

      return json_encode([
         'code' => $code,
         'message' => $message,
         'data' => $data
      ], JSON_UNESCAPED_UNICODE);
   }

   public static function getInput()
   {

      return json_decode(file_get_contents('php://input'), true);
   }
}
