-- 订单信息表
CREATE TABLE IF NOT EXISTS `snack_order` (
    `id`                INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '订单ID',
    `order_no`          BIGINT(16) UNSIGNED   NOT NULL COMMENT '订单号',
    `trade_no`          VARCHAR(30)           NOT NULL COMMENT '商户订单号（用于支付）',
    `user_id`           INT(11) UNSIGNED      NOT NULL COMMENT '用户ID',
    `status`            ENUM('pending','paid','timeout','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '订单状态：pending-待支付 paid-已支付 timeout-超时 completed-已完成 cancelled-已取消',
    `total_amount`      DECIMAL(10,2)         NOT NULL COMMENT '订单总金额',
    `pay_amount`        DECIMAL(10,2)         NOT NULL COMMENT '实付金额',
    `remark_user`       VARCHAR(50)           NOT NULL DEFAULT '' COMMENT '用户备注',
    `remark_admin`      VARCHAR(50)           NOT NULL DEFAULT '' COMMENT '管理员备注',
    `receiver_name`     VARCHAR(25)           NOT NULL COMMENT '收货人姓名',
    `receiver_phone`    BIGINT(11) UNSIGNED   NOT NULL COMMENT '收货人电话',
    `receiver_address`  VARCHAR(50)           NOT NULL COMMENT '收货地址',
    `payment_time`      INT(11) UNSIGNED      NOT NULL COMMENT '支付时间戳',
    `complete_time`     INT(11) UNSIGNED      NOT NULL COMMENT '完成或取消时间戳',
    `create_time`       INT(11) UNSIGNED      NOT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单信息表';
