<?php

namespace SnackSpot\Core;

class Pay
{
    private const PID = 1000;
    private const KEY = 'youkey';
    private const API_URL = 'https://pay.youdomain.com/mapi.php'; // 码支付的API地址
    private const TYPE = 'wxpay'; // 这里强制为微信支付
    private const DEVICE = 'jump';

    public static function createOrder($outTradeNo, $name, $money, $clientip, $param = '')
    {
        $params = [
            'pid' => self::PID,
            'type' => self::TYPE,
            'out_trade_no' => $outTradeNo,
            'notify_url' => 'https://youdomain.com/api/v1/user/pay/notify.php',
            'return_url' => 'https://youdomain.com/api/v1/user/pay/return.php',
            'name' => $name,
            'money' => $money,
            'clientip' => $clientip,
            'device' => self::DEVICE,
            'param' => $param ?: '',
            'sign_type' => 'MD5'
        ];

        $params['sign'] = self::generateSign($params);

        $response = self::httpPost(self::API_URL, $params);

        return self::parseResponse($response);
    }

    public static function verifyNotify($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }

        $sign = $data['sign'];

        // 复制数据并移除不参与签名的字段
        $signParams = $data;
        unset($signParams['sign'], $signParams['sign_type']);

        // 移除空值参数（根据文档，空值不参与签名）
        $signParams = array_filter($signParams, function ($value) {
            return $value !== '' && $value !== null;
        });

        $expectedSign = self::generateSign($signParams);

        return $sign === $expectedSign;
    }

    public static function parseNotify($data)
    {
        return [
            'pid' => $data['pid'] ?? 0,
            'trade_no' => $data['trade_no'] ?? '',
            'out_trade_no' => $data['out_trade_no'] ?? '',
            'type' => $data['type'] ?? '',
            'name' => $data['name'] ?? '',
            'money' => floatval($data['money'] ?? 0),
            'trade_status' => $data['trade_status'] ?? '',
            'param' => $data['param'] ?? ''
        ];
    }

    private static function generateSign($params)
    {
        ksort($params);

        $signData = [];
        foreach ($params as $key => $value) {
            if ($key === 'sign' || $key === 'sign_type' || $value === '') {
                continue;
            }
            $signData[] = "{$key}={$value}";
        }

        $signString = implode('&', $signData) . self::KEY;

        return md5($signString);
    }

    private static function parseResponse($response)
    {
        $result = json_decode($response, true);

        if (!$result) {
            return [
                'code' => -1,
                'msg' => '支付请求失败',
                'raw' => $response
            ];
        }

        return [
            'code' => $result['code'] ?? -1,
            'msg' => $result['msg'] ?? '',
            'trade_no' => $result['trade_no'] ?? '',
            'payurl' => $result['payurl'] ?? '',
            'qrcode' => $result['qrcode'] ?? '',
            'urlscheme' => $result['urlscheme'] ?? ''
        ];
    }

    private static function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response ?: '';
    }
}
