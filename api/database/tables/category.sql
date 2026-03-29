-- 商品分类表
CREATE TABLE IF NOT EXISTS `snack_category` (
    `id`              INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `name`            VARCHAR(20)           NOT NULL DEFAULT '' COMMENT '分类名称',
    `value`           VARCHAR(20)           NOT NULL COMMENT '分类值',
    `remark`          VARCHAR(50)           NOT NULL DEFAULT '' COMMENT '备注',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品分类表';



INSERT INTO `snack_category` (`name`, `value`, `remark`) VALUES
('面包', 'bread', '面包分类'),
('饮料', 'drink', '饮料分类'),
('方便面', 'noodle', '方便面分类')