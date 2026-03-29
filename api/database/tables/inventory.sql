-- 库存信息表
CREATE TABLE IF NOT EXISTS `snack_inventory` (
    `id`                    INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '库存ID，批次ID',
    `product_id`            INT(11) UNSIGNED      NOT NULL COMMENT '商品ID',
    `quantity`              INT(11) UNSIGNED      NOT NULL COMMENT '当前库存数量',
    `remaining_quantity`    INT(11) UNSIGNED      NOT NULL COMMENT '当前批次剩余数量',
    `platform_name`         ENUM('pdd','jd','tb') NOT NULL COMMENT '进货平台名称，pdd-拼多多，jd-京东，tb-淘宝',
    `platform_order_number` VARCHAR(50)           NOT NULL COMMENT '进货平台订单号',
    `tracking_number`       VARCHAR(50)           NOT NULL COMMENT '快递单号',
    `wholesale_price`       DECIMAL(10,2)         NOT NULL COMMENT '批发价',
    `merchant_name`         VARCHAR(50)           NOT NULL COMMENT '商家名（供应商）',
    `remark`                VARCHAR(255)          NOT NULL COMMENT '备注',
    `create_time`           INT(11) UNSIGNED      NOT NULL COMMENT '创建时间戳(进货信息写入时间)',
    `update_time`           INT(11) UNSIGNED      NOT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库存信息表';
