<?php

namespace SnackSpot\Api;

date_default_timezone_set('Asia/Shanghai');                              // 设置默认时区为上海

header('Content-Type: application/json; charset=utf-8');                 // 设置响应内容类型为JSON，字符编码为UTF-8
header('Access-Control-Allow-Origin: *');                                // 允许所有来源访问
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');              // 允许的请求方法
header('Access-Control-Allow-Headers: Content-Type, Authorization');     // 允许的请求头
header('X-Cache-Control: no-cache, no-store, must-revalidate');          // 禁用缓存

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {                          // 处理预检请求（OPTIONS方法）
   http_response_code(200);
   exit;
}
require_once __DIR__ . '/../core/Auth.php';              // 引入认证类
require_once __DIR__ . '/../core/Database.php';          // 引入数据库类
require_once __DIR__ . '/../core/Mail.php';              // 引入邮件类
require_once __DIR__ . '/../core/Pay.php';               // 引入支付类
require_once __DIR__ . '/../core/ShopStatus.php';        // 引入店铺状态类
require_once __DIR__ . '/../utils/Ip.php';               // 引入IP地址类
require_once __DIR__ . '/../utils/JsonResponse.php';     // 引入JSON响应类
require_once __DIR__ . '/../utils/Method.php';           // 引入请求方法检查类
require_once __DIR__ . '/../utils/PriceFormatter.php';   // 引入价格格式化类
require_once __DIR__ . '/../utils/RandomCode.php';       // 引入随机码类