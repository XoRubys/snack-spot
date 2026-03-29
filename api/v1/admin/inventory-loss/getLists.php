<?php

/**
 * 获取库损列表API
 * 用于管理端获取库损记录列表，支持按商品和损耗类型筛选
 * 
 * 功能说明：
 * - 验证管理员权限
 * - 支持按商品ID、损耗类型筛选
 * - 关键词支持搜索商品名称
 * - 关联查询商品名称和库存批次信息
 * - 自动计算损失金额（批次批发价 * 损耗数量）
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

// 获取筛选参数
$product_id = intval($_GET['product_id'] ?? 0);
$loss_type = trim($_GET['loss_type'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

// 连接数据库
$db = Database::connect();

// 获取表名
$lossTable = Database::table('inventory_loss');
$productTable = Database::table('product');
$inventoryTable = Database::table('inventory');

// 构建查询条件
$where = [];
$params = [];

if ($product_id > 0) {
    $where[] = "l.product_id = ?";
    $params[] = $product_id;
}

if (!empty($loss_type) && in_array($loss_type, ['damage', 'expired', 'theft', 'error', 'other'])) {
    $where[] = "l.loss_type = ?";
    $params[] = $loss_type;
}

if (!empty($keyword)) {
    $where[] = "p.name LIKE ?";
    $params[] = "%{$keyword}%";
}

$whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// 查询库损列表（关联库存表获取批发价）
$sql = "
    SELECT 
        l.id,
        l.product_id,
        p.name as product_name,
        l.batch_id,
        i.wholesale_price,
        l.quantity,
        l.loss_type,
        l.reason,
        l.operator_id,
        l.operator_name,
        l.remark,
        l.create_time,
        l.update_time
    FROM {$lossTable} l
    LEFT JOIN {$productTable} p ON l.product_id = p.id
    LEFT JOIN {$inventoryTable} i ON l.batch_id = i.id
    {$whereSql}
    ORDER BY l.create_time DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $lossList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 格式化库损列表，计算损失金额
    $list = array_map(function ($item) {
        $quantity = intval($item['quantity']);
        $wholesalePrice = floatval($item['wholesale_price'] ?? 0);
        $lossAmount = $quantity * $wholesalePrice;

        return [
            'id' => intval($item['id']),
            'product_id' => intval($item['product_id']),
            'product_name' => $item['product_name'],
            'batch_id' => intval($item['batch_id']),
            'quantity' => $quantity,
            'wholesale_price' => PriceFormatter::format($wholesalePrice),
            'loss_amount' => PriceFormatter::format($lossAmount),
            'loss_type' => $item['loss_type'],
            'reason' => $item['reason'],
            'operator_id' => intval($item['operator_id']),
            'operator_name' => $item['operator_name'],
            'remark' => $item['remark'],
            'create_time' => intval($item['create_time']),
            'update_time' => intval($item['update_time']),
        ];
    }, $lossList);

    echo JsonResponse::send(200, '获取成功', [
        'list' => $list,
        'total' => count($list)
    ]);
} catch (PDOException $e) {
    error_log('获取库损列表失败: ' . $e->getMessage());
    http_response_code(500);
    echo JsonResponse::send(500, '获取库损列表失败');
}
