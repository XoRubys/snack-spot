-- 商品表
CREATE TABLE IF NOT EXISTS `snack_product` (
    `id`              INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '商品ID',
    `name`            VARCHAR(25)           NOT NULL COMMENT '商品名称',
    `remark`          VARCHAR(50)           NOT NULL COMMENT '商品备注',
    `description`     VARCHAR(255)          NOT NULL COMMENT '商品描述',
    `category_value`  VARCHAR(20)           NOT NULL COMMENT '分类值',
    `price`           DECIMAL(10,2)         NOT NULL COMMENT '商品售价',
    `image`           VARCHAR(255)          NOT NULL COMMENT '商品主图',
    `images`          TEXT                  NOT NULL COMMENT '商品图片列表（|符号分割）',
    `status`          ENUM('online','offline') NOT NULL DEFAULT 'online' COMMENT '商品状态：online-上架 offline-下架',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品表';
