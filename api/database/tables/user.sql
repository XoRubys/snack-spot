-- 用户表
CREATE TABLE IF NOT EXISTS `snack_user` (
    `id`              INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `access_token`    VARCHAR(32)           NOT NULL COMMENT '身份令牌',
    `username`        VARCHAR(25)           NOT NULL COMMENT '用户名',
    `password_hash`   VARCHAR(60)           NOT NULL COMMENT '密码哈希值',    
    `phone`           BIGINT(11) UNSIGNED   NOT NULL COMMENT '手机号',
    `level`           ENUM('user','admin')  NOT NULL DEFAULT 'user' COMMENT '等级：user-普通用户 admin-管理员',
    `status`          ENUM('active','inactive')  NOT NULL DEFAULT 'active' COMMENT '状态：active-正常 inactive-封禁',
    `dormitory`       INT(3) UNSIGNED       NOT NULL COMMENT '寝室号',
    `last_login_ip`   VARCHAR(46)           NOT NULL COMMENT '最后登录IP',
    `last_login_time` INT(11) UNSIGNED      NOT NULL COMMENT '最后登录时间戳',
    `create_ip`       VARCHAR(46)           NOT NULL COMMENT '注册IP',  
    `create_time`     INT(11) UNSIGNED      NOT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';