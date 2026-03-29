-- 系统配置表
CREATE TABLE IF NOT EXISTS `snack_config` (
    `id`              INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `name`            VARCHAR(50)           NOT NULL DEFAULT '' COMMENT '配置名称',
    `value`           VARCHAR(50)           NOT NULL DEFAULT '' COMMENT '配置值',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

INSERT INTO `snack_config` (`name`, `value`) VALUES
-- 宿舍地址
('address', 'your address'),
-- 店铺是否在营业
('online', 'true'),
-- 打烊时的提示信息
('online_notice', 'default notice'),
-- 最低配送费
('delivery_fee_min', '0.2'),
-- 配送费比例
('delivery_fee_percent', '1.5'),
-- 起送价
('start_price', '3.00'),
-- 系统通知
('notice', '积极响应用户需提供优质服务！');