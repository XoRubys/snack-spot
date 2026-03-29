<?php

/**
 * 系统配置获取API
 * 管理员获取系统配置信息（配送费、营业状态、通知等）
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 获取系统配置项（营业状态、配送费、地址、通知等）
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

// 检查请求方法，只允许GET请求
Method::check('GET');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 连接数据库
$db = Database::connect();
$configTable = Database::table('config');

try {
    // 查询指定的配置项
    $stmt = $db->prepare("
        SELECT 
            name,
            value
        FROM {$configTable}
        WHERE name IN ('online', 'delivery_fee_min', 'delivery_fee_percent', 'address', 'notice', 'online_notice')
    ");
    $stmt->execute();
    $configs = $stmt->fetchAll();

    // 将配置项转换为关联数组
    $configData = [];
    foreach ($configs as $config) {
        $configData[$config['name']] = $config['value'];
    }

    // 返回配置数据
    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', [
        'online' => $configData['online'] === 'true',           // 是否营业
        'deliveryFeeMin' => PriceFormatter::format($configData['delivery_fee_min'] ?? 0),  // 起送价
        'deliveryFeePercent' => PriceFormatter::format($configData['delivery_fee_percent'] ?? 0),  // 配送费百分比
        'address' => $configData['address'] ?? '',              // 配送地址
        'notice' => $configData['notice'] ?? '',                 // 系统通知
        'closedNotice' => $configData['online_notice'] ?? ''    // 打烊提示
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '服务器内部错误');
    exit;
}
