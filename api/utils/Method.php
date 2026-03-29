<?php

namespace SnackSpot\Utils;

class Method
{
    public static function check($method)
    {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            http_response_code(405);
            echo JsonResponse::send(405, "请求方法错误，仅允许{$method}方法");
            exit;
        }
    }
}
