<?php

namespace SnackSpot\Core;

use SnackSpot\Utils\JsonResponse;

class ShopStatus
{
    private const START_TIME = '07:00:00'; // 店铺开始营业时间
    private const END_TIME = '23:30:00'; // 店铺结束营业时间
    private const DEFAULT_NOTICE = '店铺已打烊'; // 默认通知

    public static function check()
    {
        $db = Database::connect();
        $configTable = Database::table('config');

        $stmt = $db->prepare("SELECT value FROM {$configTable} WHERE name = 'online'");
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result || $result['value'] !== 'true') {
            $notice = self::getOnlineNotice();
            self::response($notice);
        }

        $currentTime = date('H:i:s');
        if ($currentTime < self::START_TIME || $currentTime > self::END_TIME) {
            self::response(self::DEFAULT_NOTICE);
        }
    }

    private static function getOnlineNotice()
    {
        $db = Database::connect();
        $configTable = Database::table('config');

        $stmt = $db->prepare("SELECT value FROM {$configTable} WHERE name = 'online_notice'");
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['value'] ?: self::DEFAULT_NOTICE;
    }

    private static function response($notice)
    {
        http_response_code(200);
        echo JsonResponse::send(601, $notice);
        exit;
    }
}
