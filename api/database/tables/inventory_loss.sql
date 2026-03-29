-- 库损信息表
CREATE TABLE IF NOT EXISTS `snack_inventory_loss` (
    `id`              INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '库损ID',
    `product_id`      INT(11) UNSIGNED      NOT NULL COMMENT '商品ID',
    `batch_id`        INT(11) UNSIGNED      NOT NULL COMMENT '库存批次ID',
    `quantity`        INT(11) UNSIGNED      NOT NULL COMMENT '损耗数量',
    `loss_type`       ENUM('damage','expired','theft','error','other') NOT NULL DEFAULT 'other' COMMENT '损耗类型：damage-损坏 expired-过期 theft-盗窃 error-盘点错误 other-其他',
    `reason`          VARCHAR(255)          NOT NULL COMMENT '损耗原因',
    `operator_id`     INT(11) UNSIGNED      NOT NULL COMMENT '操作人ID',
    `operator_name`   VARCHAR(50)           NOT NULL COMMENT '操作人姓名',
    `remark`          VARCHAR(255)          NOT NULL COMMENT '备注',
    `create_time`     INT(11) UNSIGNED      NOT NULL COMMENT '创建时间戳',
    `update_time`     INT(11) UNSIGNED      NOT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库损信息表';
