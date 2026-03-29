<?php

/**
 * 系统配置更新API
 * 管理员更新系统配置信息
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;

// 检查请求方法，只允许POST请求
Method::check('POST');

// 验证管理员权限
$accessToken = Auth::validateAdmin();

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

// 连接数据库
$db = Database::connect();
$configTable = Database::table('config');

try {
    // 定义允许更新的配置项
    $allowedConfigs = [
        'online' => 'bool',
        'delivery_fee_min' => 'float',
        'delivery_fee_percent' => 'float',
        'address' => 'string',
        'notice' => 'string',
        'online_notice' => 'string',
        'pay_pid' => 'string',
        'pay_key' => 'string'
    ];

    $updated = [];

    foreach ($allowedConfigs as $key => $type) {
        if (!isset($input[$key])) {
            continue;
        }

        $value = $input[$key];

        // 类型转换
        switch ($type) {
            case 'bool':
                $value = $value ? 'true' : 'false';
                break;
            case 'float':
                $value = number_format(floatval($value), 2, '.', '');
                break;
            case 'string':
                $value = trim(strval($value));
                break;
        }

        // 检查配置项是否存在
        $stmt = $db->prepare("SELECT id FROM {$configTable} WHERE name = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();

        if ($exists) {
            // 更新
            $stmt = $db->prepare("UPDATE {$configTable} SET value = ? WHERE name = ?");
            $stmt->execute([$value, $key]);
        } else {
            // 插入
            $stmt = $db->prepare("INSERT INTO {$configTable} (name, value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        $updated[] = $key;
    }

    if (empty($updated)) {
        http_response_code(400);
        echo JsonResponse::send(400, '没有要更新的配置项');
        exit;
    }

    http_response_code(200);
    echo JsonResponse::send(200, '配置保存成功', ['updated' => $updated]);

} catch (Exception $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '服务器内部错误');
    exit;
}
