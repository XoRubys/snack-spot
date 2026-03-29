<?php

namespace SnackSpot\Api;

require_once __DIR__ . '/../../common.php';

// 引入数据库连接类
use SnackSpot\Core\Database;
use SnackSpot\Core\ShopStatus;
// 引入JSON响应工具类
use SnackSpot\Utils\JsonResponse;
// 引入请求方法检查类
use SnackSpot\Utils\Method;
use SnackSpot\Utils\PriceFormatter;
use PDO;

Method::check('GET');
ShopStatus::check();

try {
   $db = Database::connect();                                  // 连接数据库
   $categoryTable = Database::table('category');    // 分类表
   $productTable = Database::table('product');      // 商品表
   $inventoryTable = Database::table('inventory');  // 库存表
   $configTable = Database::table('config');        // 配置表

   // 查询分类数据
   $categoryStmt = $db->prepare("
      SELECT
         name,    -- 分类名称
         value,   -- 分类值
         remark   -- 分类备注
      FROM {$categoryTable}
   ");
   $categoryStmt->execute();
   // 获取所有分类
   $categories = $categoryStmt->fetchAll();

   // 在分类列表开头添加"全部"选项
   array_unshift($categories, [
      'name' => '全部',
      'value' => '',
      'remark' => '所有商品'
   ]);

   // 查询商品数据，关联库存表计算库存
   $productStmt = $db->prepare("
      SELECT
         p.id,               -- 商品ID
         p.name,             -- 商品名称
         p.remark,           -- 商品备注
         p.description,      -- 商品描述
         p.category_value,   -- 商品分类值
         p.price,            -- 商品价格
         p.image,            -- 商品图片
         COALESCE(SUM(i.remaining_quantity), 0) as stock  -- 计算库存总量
      FROM {$productTable} p
      LEFT JOIN {$inventoryTable} i ON p.id = i.product_id
      WHERE p.status = 'online'  -- 只查询上架的商品
      GROUP BY p.id
      HAVING stock > 0  -- 只显示库存大于0的商品
      ORDER BY p.category_value, p.id  -- 按分类和ID排序
   ");
   $productStmt->execute();
   $products = $productStmt->fetchAll();        // 获取所有商品

   // 查询本月销售数据（已完成的订单）
   $orderItemTable = Database::table('order_item');
   $orderTable = Database::table('order');
   $monthStart = strtotime(date('Y-m-01'));

   $monthlySalesStmt = $db->prepare("
      SELECT
         oi.product_id,
         COUNT(*) as sales_count
      FROM {$orderItemTable} oi
      INNER JOIN {$orderTable} o ON oi.order_id = o.id
      WHERE o.status = 'completed'
        AND o.create_time >= :monthStart
      GROUP BY oi.product_id
   ");
   $monthlySalesStmt->execute([':monthStart' => $monthStart]);
   $monthlySalesRows = $monthlySalesStmt->fetchAll(PDO::FETCH_ASSOC);
   $monthlySalesData = [];
   foreach ($monthlySalesRows as $row) {
      $monthlySalesData[$row['product_id']] = $row['sales_count'];
   }

   // 处理商品数据，添加默认字段并转换为驼峰命名
   foreach ($products as &$product) {

      $product['stock'] = (int)$product['stock'];                  // 转换库存为整数
      $product['price'] = PriceFormatter::format($product['price']); // 格式化价格为两位小数
      $product['monthlySales'] = (int)($monthlySalesData[$product['id']] ?? 0);  // 设置真实月销量
      $product['categoryValue'] = $product['category_value'];      // 商品分类值
      // 移除数据库原始字段和前端不需要的字段
      unset($product['category_value'], $product['status'], $product['images'], $product['specs'], $product['quantity'], $product['description']);
   }

   // 查询配置数据
   $configStmt = $db->prepare("
      SELECT 
         name,    -- 配置名称
         value    -- 配置值
      FROM {$configTable} 
      WHERE name IN ('online', 'delivery_fee_min', 'delivery_fee_percent', 'address', 'notice', 'start_price') -- 查询指定的配置项
   ");
   $configStmt->execute();
   // 获取所有配置
   $configs = $configStmt->fetchAll();

   // 转换配置数据为键值对数组
   $configData = [];
   foreach ($configs as $config) {
      $configData[$config['name']] = $config['value'];
   }

   // 返回JSON响应，包含商品列表、分类列表和配置信息
   echo JsonResponse::send(200, 'success', [
      'products' => $products,                        // 商品列表
      'categories' => $categories,                    // 分类列表
      'config' => [                                   // 系统配置
         'deliveryFeeMin' => PriceFormatter::format($configData['delivery_fee_min'] ?? 0),         // 最低配送费
         'deliveryFeePercent' => PriceFormatter::format($configData['delivery_fee_percent'] ?? 0), // 配送费百分比
         'startPrice' => PriceFormatter::format($configData['start_price'] ?? 0),                  // 起送价
         'address' => $configData['address'] ?? '',                        // 宿舍地址
         'notice' => $configData['notice'] ?? ''                                              // 系统公告
      ]
   ]);
} catch (\Exception $e) {
   // 捕获异常并返回错误信息
   http_response_code(500);                        // 设置HTTP响应码为500
   echo JsonResponse::send(500, 'Server error', [  // 返回JSON格式的错误信息
      'error' => $e->getMessage()
   ]);
}
