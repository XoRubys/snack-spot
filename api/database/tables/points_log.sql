-- 积分日志表
CREATE TABLE IF NOT EXISTS `snack_points_log` (
    `id`                INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `user_id`           INT(11) UNSIGNED      NOT NULL COMMENT '日志所属用户ID',
    `change`            VARCHAR(44)           NOT NULL COMMENT '积分变化值',
    `remark`            VARCHAR(25)           NOT NULL COMMENT '备注',
    `create_time`       BIGINT(11) UNSIGNED      NOT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分日志表';
