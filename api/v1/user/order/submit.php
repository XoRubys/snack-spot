<?php

/**
 * 订单提交API - PDO版本
 * 处理用户提交订单，按批次扣减库存，每个商品数量单独记录一行
 */

require_once __DIR__ . '/../../common.php';

use SnackSpot\Core\Database;
use SnackSpot\Core\Auth;
use SnackSpot\Core\ShopStatus;
use SnackSpot\Core\Pay;
use SnackSpot\Utils\JsonResponse;
use SnackSpot\Utils\RandomCode;
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;
use SnackSpot\Utils\Ip;

Method::check('POST');

$accessToken = Auth::validateToken();
ShopStatus::check();

$input = JsonResponse::getInput();

// 验证输入数据
$goods = $input['goods'] ?? null;
$totalAmount = isset($input['totalAmount']) ? floatval($input['totalAmount']) : null; // 商品总价
$deliveryFee = isset($input['deliveryFee']) ? floatval($input['deliveryFee']) : null; // 配送费
$finalAmount = isset($input['finalAmount']) ? floatval($input['finalAmount']) : null; // 实付金额
$remark = isset($input['remark']) ? trim($input['remark']) : ''; // 订单备注


// 验证必填字段是否存在
if ($goods === null) {
    http_response_code(400);
    echo JsonResponse::send(400, '缺少商品列表');
    exit;
}

if ($totalAmount === null) {
    http_response_code(400);
    echo JsonResponse::send(400, '缺少商品总价');
    exit;
}

if ($deliveryFee === null) {
    http_response_code(400);
    echo JsonResponse::send(400, '缺少配送费');
    exit;
}

if ($finalAmount === null) {
    http_response_code(400);
    echo JsonResponse::send(400, '缺少实付金额');
    exit;
}

// 验证金额必须为非负数
if ($totalAmount < 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品总价不能为负数');
    exit;
}

if ($deliveryFee < 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '配送费不能为负数');
    exit;
}

if ($finalAmount < 0) {
    http_response_code(400);
    echo JsonResponse::send(400, '实付金额不能为负数');
    exit;
}

// 验证商品列表
if (empty($goods) || !is_array($goods)) {
    http_response_code(400);
    echo JsonResponse::send(400, '商品列表不能为空');
    exit;
}

// 连接数据库并获取用户信息
$db = Database::connect();
$userTable = Database::table('user');
$stmt = $db->prepare("SELECT id, username, phone, dormitory FROM {$userTable} WHERE access_token = ?");
$stmt->execute([$accessToken]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(401);
    echo JsonResponse::send(401, '用户不存在');
    exit;
}
$userId = $user['id'];
$productTable = Database::table('product');
$inventoryTable = Database::table('inventory');
$orderTable = Database::table('order');
$orderItemTable = Database::table('order_item');
$configTable = Database::table('config');

// 查询配置（配送费配置和基础地址）
$configStmt = $db->prepare("SELECT name, value FROM {$configTable} WHERE name IN ('delivery_fee_min', 'delivery_fee_percent', 'address', 'start_price')");
$configStmt->execute();
$configRows = $configStmt->fetchAll(PDO::FETCH_ASSOC);
$configData = [];
foreach ($configRows as $row) {
    $configData[$row['name']] = $row['value'];
}
$deliveryFeeMin = floatval($configData['delivery_fee_min'] ?? 0);
$deliveryFeePercent = floatval($configData['delivery_fee_percent'] ?? 0);
$baseAddress = $configData['address'] ?? '';
$startPrice = floatval($configData['start_price'] ?? 0);

// 拼接完整地址（基础地址 + 寝室号）
$fullAddress = "{$baseAddress} {$user['dormitory']} 寝室";

// 先验证所有商品并计算真实的商品总价（从数据库获取真实价格）
$validatedGoods = [];
$calculatedTotalAmount = 0;

foreach ($goods as $item) {
    // 验证商品字段完整性
    if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
        http_response_code(400);
        echo JsonResponse::send(400, '商品数据不完整');
        exit;
    }

    $productId = intval($item['id']);
    $quantity = intval($item['quantity']);
    $cartPrice = floatval($item['price']);

    if ($productId <= 0) {
        http_response_code(400);
        echo JsonResponse::send(400, '商品信息有误，请重新选择商品');
        exit;
    }

    if ($quantity <= 0) {
        http_response_code(400);
        echo JsonResponse::send(400, '商品数量不能小于1');
        exit;
    }

    if ($cartPrice < 0) {
        http_response_code(400);
        echo JsonResponse::send(400, '商品数据异常，请刷新页面重试');
        exit;
    }

    // 获取商品信息
    $stmt = $db->prepare("SELECT id, name, price, image, status FROM {$productTable} WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(400);
        echo JsonResponse::send(400, '部分商品已下架，请重新选择');
        exit;
    }

    if ($product['status'] !== 'online') {
        http_response_code(400);
        echo JsonResponse::send(400, "商品「{$product['name']}」已下架");
        exit;
    }

    // 验证价格是否匹配
    if (abs(floatval($product['price']) - $cartPrice) > 0.01) {
        http_response_code(400);
        echo JsonResponse::send(400, "商品「{$product['name']}」价格已变动，请重新下单");
        exit;
    }

    $validatedGoods[] = [
        'product_id' => $productId,
        'name' => $product['name'],
        'price' => floatval($product['price']),
        'image' => $product['image'],
        'quantity' => $quantity
    ];

    // 累加计算商品总价（使用数据库真实价格）
    $calculatedTotalAmount += floatval($product['price']) * $quantity;
}

$calculatedTotalAmount = round($calculatedTotalAmount, 2);

// 验证是否达到起送价
if ($calculatedTotalAmount < $startPrice) {
    http_response_code(400);
    echo JsonResponse::send(400, "商品总价需满¥" . PriceFormatter::format($startPrice) . "才能下单");
    exit;
}

// 验证商品总价是否匹配（严格相等）
if ($calculatedTotalAmount != $totalAmount) {
    http_response_code(400);
    echo JsonResponse::send(400, '订单金额有变动，请刷新页面后重试');
    exit;
}

// 使用后端计算的真实总价来验证配送费
$calculatedDeliveryFee = max($calculatedTotalAmount * ($deliveryFeePercent / 100), $deliveryFeeMin);
$calculatedDeliveryFee = round($calculatedDeliveryFee, 2);

// 验证配送费是否匹配（严格相等）
if ($calculatedDeliveryFee != $deliveryFee) {
    http_response_code(400);
    echo JsonResponse::send(400, '配送费计算错误，请刷新页面后重试');
    exit;
}

// 验证实付金额是否正确
$calculatedFinalAmount = round($calculatedTotalAmount + $calculatedDeliveryFee, 2);

// 验证实付金额是否匹配（严格相等）
if ($calculatedFinalAmount != $finalAmount) {
    http_response_code(400);
    echo JsonResponse::send(400, '实付金额计算错误，请刷新页面后重试');
    exit;
}

// 生成订单号和支付参数
$orderNo = RandomCode::generateOrderNo();
$clientip = Ip::getClientIp();
$now = time();

// 先调用支付平台创建支付订单（使用orderNo作为商户订单号，使用数据库计算的金额）
$payResult = Pay::createOrder($orderNo, '商品支付', $calculatedFinalAmount, $clientip, $orderNo);

if ($payResult['code'] !== 1) {
    http_response_code(400);
    echo JsonResponse::send(400, '支付订单创建失败: ' . ($payResult['msg'] ?? '未知错误'), $payResult);
    exit;
}

// 支付平台返回的真实交易号
$realTradeNo = $payResult['trade_no'];

try {
    $db->beginTransaction();

    // 1. 检查库存（商品已在事务外验证过）
    foreach ($validatedGoods as $goodsItem) {
        $productId = $goodsItem['product_id'];
        $quantity = $goodsItem['quantity'];

        // 检查总库存是否足够
        $stmt = $db->prepare("SELECT SUM(remaining_quantity) as total FROM {$inventoryTable} WHERE product_id = ? AND remaining_quantity > 0");
        $stmt->execute([$productId]);
        $stockResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalStock = intval($stockResult['total'] ?? 0);

        if ($totalStock < $quantity) {
            throw new Exception("商品「{$goodsItem['name']}」库存不足，仅剩 {$totalStock} 件");
        }
    }

    // 2. 创建订单（使用支付平台返回的真实trade_no）
    $stmt = $db->prepare("
        INSERT INTO {$orderTable} (
            order_no, trade_no, user_id, status, total_amount, pay_amount,
            remark_user, receiver_name, receiver_phone, receiver_address,
            payment_time, complete_time, create_time
        ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, 0, 0, ?)
    ");

    $stmt->execute([
        $orderNo,
        $realTradeNo,
        $userId,
        $calculatedTotalAmount,
        $calculatedFinalAmount,
        $remark,
        $user['username'] ?? '',
        intval($user['phone'] ?? 0),
        $fullAddress,
        $now
    ]);

    $orderId = $db->lastInsertId();

    // 2. 处理每个商品，按批次扣减库存，每个数量单独一行
    foreach ($validatedGoods as $goodsItem) {
        $remainingToDeduct = $goodsItem['quantity'];
        $productId = $goodsItem['product_id'];

        // 按时间顺序获取库存批次（先进先出）
        $stmt = $db->prepare("
            SELECT id, remaining_quantity 
            FROM {$inventoryTable} 
            WHERE product_id = ? AND remaining_quantity > 0 
            ORDER BY create_time ASC, id ASC
        ");
        $stmt->execute([$productId]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $batchId = $batch['id'];
            $batchStock = intval($batch['remaining_quantity']);
            $deductQty = min($remainingToDeduct, $batchStock);

            // 扣减库存
            $stmt = $db->prepare("
                UPDATE {$inventoryTable} 
                SET remaining_quantity = remaining_quantity - ?, 
                    update_time = ? 
                WHERE id = ?
            ");
            $stmt->execute([$deductQty, $now, $batchId]);

            // 为每个数量创建单独的订单商品记录
            for ($i = 0; $i < $deductQty; $i++) {
                $stmt = $db->prepare("
                    INSERT INTO {$orderItemTable} (
                        order_id, product_id, inventory_id, product_name, 
                        product_image, product_price, create_time
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $productId,
                    $batchId,
                    $goodsItem['name'],
                    $goodsItem['image'],
                    $goodsItem['price'],
                    $now
                ]);
            }


            $remainingToDeduct -= $deductQty;
        }

        if ($remainingToDeduct > 0) {
            throw new Exception('系统繁忙，请稍后重试');
        }
    }

    $db->commit();

    http_response_code(200);
    echo JsonResponse::send(200, '订单提交成功', [
        'payUrl' => $payResult['payurl'] ?? '',
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo JsonResponse::send(400, $e->getMessage());
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo JsonResponse::send(500, '订单提交失败', ['error' => $e->getMessage()]);
}
