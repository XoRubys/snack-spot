<?php

/**
 * 获取库损概览API
 * 用于管理端获取库损统计数据概览
 *
 * 功能说明：
 * - 验证管理员权限
 * - 统计总库损记录数
 * - 统计本月库损记录数
 * - 统计总损耗数量
 * - 统计总损失金额
 * - 统计本月损失金额
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

// 获取表名
$lossTable = Database::table('inventory_loss');
$inventoryTable = Database::table('inventory');

try {
    // 1. 统计总库损记录数
    $sql = "SELECT COUNT(*) as total_loss_records FROM {$lossTable}";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalLossRecords = intval($result['total_loss_records']);

    // 2. 统计本月库损记录数
    $currentYear = date('Y');
    $currentMonth = date('m');
    $monthStart = strtotime("{$currentYear}-{$currentMonth}-01 00:00:00");
    $monthEnd = strtotime("{$currentYear}-" . ($currentMonth + 1) . "-01 00:00:00") - 1;

    $sql = "SELECT COUNT(*) as month_loss_records FROM {$lossTable} WHERE create_time >= ? AND create_time <= ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthLossRecords = intval($result['month_loss_records']);

    // 3. 统计总损耗数量
    $sql = "SELECT COALESCE(SUM(quantity), 0) as total_loss_quantity FROM {$lossTable}";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalLossQuantity = intval($result['total_loss_quantity']);

    // 4. 统计总损失金额（需要关联库存表获取批发价）
    $sql = "
        SELECT COALESCE(SUM(l.quantity * i.wholesale_price), 0) as total_loss_amount
        FROM {$lossTable} l
        LEFT JOIN {$inventoryTable} i ON l.batch_id = i.id
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalLossAmount = PriceFormatter::format(floatval($result['total_loss_amount']));

    // 5. 统计本月损失金额
    $sql = "
        SELECT COALESCE(SUM(l.quantity * i.wholesale_price), 0) as month_loss_amount
        FROM {$lossTable} l
        LEFT JOIN {$inventoryTable} i ON l.batch_id = i.id
        WHERE l.create_time >= ? AND l.create_time <= ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthLossAmount = PriceFormatter::format(floatval($result['month_loss_amount']));

    // 组装返回数据
    $data = [
        'totalLossRecords' => $totalLossRecords,      // 总库损记录数
        'monthLossRecords' => $monthLossRecords,    // 本月库损记录数
        'totalLossQuantity' => $totalLossQuantity,  // 总损耗数量
        'totalLossAmount' => $totalLossAmount,      // 总损失金额
        'monthLossAmount' => $monthLossAmount       // 本月损失金额
    ];

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', $data);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取库损概览失败', [
        'error' => $e->getMessage()
    ]);
}
