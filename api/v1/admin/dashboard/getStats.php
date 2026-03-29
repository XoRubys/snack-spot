<?php

/**
 * 管理端控制台统计数据API
 * 用于获取控制台页面的各项统计数据
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;

Method::check('GET');

$accessToken = Auth::validateAdmin();

$db = Database::connect();

$orderTable = Database::table('order');
$userTable = Database::table('user');
$productTable = Database::table('product');
$inventoryTable = Database::table('inventory');
$inventoryLossTable = Database::table('inventory_loss');

try {
    // ==================== 时间范围定义 ====================
    $monthStart = strtotime(date('Y-m-01'));  // 本月1日0点
    $todayStart = strtotime(date('Y-m-d'));   // 今日0点
    $weekStart = strtotime(date('Y-m-d', strtotime('this week')));  // 本周一0点

    // ==================== 订单统计 ====================
    // 本月订单量
    $monthOrdersStmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM {$orderTable} 
        WHERE create_time >= :monthStart
    ");
    $monthOrdersStmt->execute([':monthStart' => $monthStart]);
    $monthOrders = $monthOrdersStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 今日订单量
    $dayOrdersStmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM {$orderTable} 
        WHERE create_time >= :todayStart
    ");
    $dayOrdersStmt->execute([':todayStart' => $todayStart]);
    $dayOrders = $dayOrdersStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // ==================== 销售额统计 ====================
    // 本月销售额（已完成/已支付订单）
    $monthSalesStmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM {$orderTable} 
        WHERE create_time >= :monthStart 
        AND status IN ('completed', 'paid')
    ");
    $monthSalesStmt->execute([':monthStart' => $monthStart]);
    $monthSales = (float) $monthSalesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 今日销售额（已完成/已支付订单）
    $daySalesStmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM {$orderTable} 
        WHERE create_time >= :todayStart 
        AND status IN ('completed', 'paid')
    ");
    $daySalesStmt->execute([':todayStart' => $todayStart]);
    $daySales = (float) $daySalesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // ==================== 基础数据 ====================
    // 用户总数
    $totalUsersStmt = $db->query("SELECT COUNT(*) as count FROM {$userTable}");
    $totalUsers = $totalUsersStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 上架商品总数
    $totalProductsStmt = $db->query("SELECT COUNT(*) as count FROM {$productTable} WHERE status = 'online'");
    $totalProducts = $totalProductsStmt->fetch(PDO::FETCH_ASSOC)['count'];

    $orderItemTable = Database::table('order_item');

    // ==================== 月利润计算 ====================
    // 计算月销售成本（通过订单商品关联库存批次，每条记录代表一个商品）
    $monthCostStmt = $db->prepare("
        SELECT COALESCE(SUM(i.wholesale_price), 0) as total 
        FROM {$orderItemTable} oi
        LEFT JOIN {$inventoryTable} i ON oi.inventory_id = i.id
        LEFT JOIN {$orderTable} o ON oi.order_id = o.id
        WHERE o.create_time >= :monthStart 
        AND o.status IN ('completed', 'paid')
    ");
    $monthCostStmt->execute([':monthStart' => $monthStart]);
    $monthCost = (float) $monthCostStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 月利润 = 月销售额 - 月销售成本（注意：不包含库损，库损与利润计算无关）
    $monthProfit = $monthSales - $monthCost;

    // ==================== 日利润计算 ====================
    // 计算日销售成本（通过订单商品关联库存批次，每条记录代表一个商品）
    $dayCostStmt = $db->prepare("
        SELECT COALESCE(SUM(i.wholesale_price), 0) as total 
        FROM {$orderItemTable} oi
        LEFT JOIN {$inventoryTable} i ON oi.inventory_id = i.id
        LEFT JOIN {$orderTable} o ON oi.order_id = o.id
        WHERE o.create_time >= :todayStart 
        AND o.status IN ('completed', 'paid')
    ");
    $dayCostStmt->execute([':todayStart' => $todayStart]);
    $dayCost = (float) $dayCostStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 日利润 = 日销售额 - 日销售成本（注意：不包含库损，库损与利润计算无关）
    $dayProfit = $daySales - $dayCost;

    // ==================== 配送费统计 ====================
    // 本月配送费（pay_amount - total_amount = 配送费）
    $monthDeliveryFeeStmt = $db->prepare("
        SELECT COALESCE(SUM(pay_amount - total_amount), 0) as total 
        FROM {$orderTable} 
        WHERE create_time >= :monthStart 
        AND status IN ('completed', 'paid')
    ");
    $monthDeliveryFeeStmt->execute([':monthStart' => $monthStart]);
    $monthDeliveryFee = (float) $monthDeliveryFeeStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 本周配送费
    $weekDeliveryFeeStmt = $db->prepare("
        SELECT COALESCE(SUM(pay_amount - total_amount), 0) as total 
        FROM {$orderTable} 
        WHERE create_time >= :weekStart 
        AND status IN ('completed', 'paid')
    ");
    $weekDeliveryFeeStmt->execute([':weekStart' => $weekStart]);
    $weekDeliveryFee = (float) $weekDeliveryFeeStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo JsonResponse::send(200, '获取成功', [
        'monthOrders' => intval($monthOrders),
        'dayOrders' => intval($dayOrders),
        'monthSales' => PriceFormatter::format($monthSales),
        'daySales' => PriceFormatter::format($daySales),
        'totalUsers' => intval($totalUsers),
        'totalProducts' => intval($totalProducts),
        'monthProfit' => PriceFormatter::format($monthProfit),
        'dayProfit' => PriceFormatter::format($dayProfit),
        'monthDeliveryFee' => PriceFormatter::format($monthDeliveryFee),
        'weekDeliveryFee' => PriceFormatter::format($weekDeliveryFee)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取统计数据失败: ' . $e->getMessage());
}
