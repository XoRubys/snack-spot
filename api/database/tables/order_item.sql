-- 订单商品表
CREATE TABLE IF NOT EXISTS `snack_order_item` (
    `id`              INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '订单商品ID',
    `order_id`        INT(11) UNSIGNED      NOT NULL COMMENT '订单ID',
    `product_id`      INT(11) UNSIGNED      NOT NULL COMMENT '商品ID',
    `inventory_id`    INT(11) UNSIGNED      NOT NULL COMMENT '库存批次ID',
    `product_name`    VARCHAR(25)           NOT NULL COMMENT '商品名称',
    `product_image`   VARCHAR(255)          NOT NULL COMMENT '商品图片',
    `product_price`   DECIMAL(10,2)         NOT NULL COMMENT '商品单价',
    `create_time`     INT(11) UNSIGNED      NOT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单商品表';
