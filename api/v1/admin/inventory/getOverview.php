<?php

/**
 * 获取库存概览API
 * 用于管理端获取库存统计数据概览
 *
 * 功能说明：
 * - 验证管理员权限
 * - 统计当月库存记录数量（总进货量）
 * - 统计有剩余商品的库存记录条数（剩余库存）
 * - 统计当前库存总成本（剩余数量 * 批发价）
 * - 统计预计利润（剩余数量 * (售价 - 批发价)）
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
$inventoryTable = Database::table('inventory');
$productTable = Database::table('product');

try {
    // 获取当前月份的起始和结束时间戳
    $currentYear = date('Y');
    $currentMonth = date('m');
    $monthStart = strtotime("{$currentYear}-{$currentMonth}-01 00:00:00");
    $monthEnd = strtotime("{$currentYear}-" . ($currentMonth + 1) . "-01 00:00:00") - 1;

    // 1. 统计当月库存记录数量（总进货量）
    $sql = "
        SELECT COUNT(*) as total_purchase
        FROM {$inventoryTable}
        WHERE create_time >= ? AND create_time <= ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPurchase = intval($result['total_purchase']);

    // 1.2 统计当月进货的商品总数量
    $sql = "
        SELECT COALESCE(SUM(quantity), 0) as month_product_quantity
        FROM {$inventoryTable}
        WHERE create_time >= ? AND create_time <= ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$monthStart, $monthEnd]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthProductQuantity = intval($result['month_product_quantity']);

    // 2. 统计有剩余商品的库存记录条数（剩余库存）
    $sql = "
        SELECT COUNT(*) as total_remaining
        FROM {$inventoryTable}
        WHERE remaining_quantity > 0
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRemaining = intval($result['total_remaining'] ?? 0);

    // 2.2 统计总的商品剩余数量
    $sql = "
        SELECT COALESCE(SUM(remaining_quantity), 0) as total_product_remaining
        FROM {$inventoryTable}
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalProductRemaining = intval($result['total_product_remaining'] ?? 0);

    // 3. 统计库存成本和预计利润
    $sql = "
        SELECT
            i.remaining_quantity,
            i.wholesale_price,
            p.price as product_price
        FROM {$inventoryTable} i
        LEFT JOIN {$productTable} p ON i.product_id = p.id
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCost = 0;
    $totalProfit = 0;

    foreach ($inventoryItems as $item) {
        $remainingQuantity = intval($item['remaining_quantity']);
        $wholesalePrice = floatval($item['wholesale_price']);
        $productPrice = floatval($item['product_price']);

        // 库存成本 = 剩余数量 * 批发价
        $totalCost += $remainingQuantity * $wholesalePrice;

        // 预计利润 = 剩余数量 * (售价 - 批发价)
        $totalProfit += $remainingQuantity * ($productPrice - $wholesalePrice);
    }

    // 组装返回数据
    $data = [
        'totalPurchase' => $totalPurchase,           // 总进货量（当月库存记录条数）
        'monthProductQuantity' => $monthProductQuantity, // 当月进货商品总数量
        'totalRemaining' => $totalRemaining,        // 剩余存量（有剩余商品的记录条数）
        'totalProductRemaining' => $totalProductRemaining, // 总的商品剩余数量
        'totalCost' => PriceFormatter::format($totalCost),     // 库存成本
        'totalProfit' => PriceFormatter::format($totalProfit)  // 预计利润
    ];

    http_response_code(200);
    echo JsonResponse::send(200, '获取成功', $data);
} catch (PDOException $e) {
    http_response_code(500);
    echo JsonResponse::send(500, '获取库存概览失败', [
        'error' => $e->getMessage()
    ]);
}
