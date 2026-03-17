-- ============================================================
--  KowloonJudo 数据库初始化脚本
--  字符集: utf8mb4 / 排序规则: utf8mb4_unicode_ci
--  适用数据库: MySQL 5.7+ / MariaDB 10.3+
--  生成时间: 2026-03-15
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+08:00';

-- ------------------------------------------------------------
-- 创建数据库（如果不存在）
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `kowloon_judo`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kowloon_judo`;

-- ============================================================
--  1. migrations  （Laravel/Lumen 迁移记录表）
-- ============================================================
CREATE TABLE IF NOT EXISTS `migrations` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch`     INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  2. users  （小程序用户表）
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT   COMMENT '主键',
  `openid`     VARCHAR(64)     NOT NULL                  COMMENT '微信 openid',
  `unionid`    VARCHAR(64)     NOT NULL DEFAULT ''       COMMENT '微信 unionid（开放平台）',
  `nickname`   VARCHAR(50)     NOT NULL DEFAULT ''       COMMENT '昵称',
  `avatar`     VARCHAR(500)    NOT NULL DEFAULT ''       COMMENT '头像 URL',
  `phone`      VARCHAR(20)     NOT NULL DEFAULT ''       COMMENT '手机号',
  `gender`     TINYINT         NOT NULL DEFAULT 0        COMMENT '性别：0未设置 1男 2女',
  `birthday`   DATE            NULL     DEFAULT NULL     COMMENT '出生日期',
  `status`     TINYINT         NOT NULL DEFAULT 1        COMMENT '状态：1正常 0禁用',
  `created_at` TIMESTAMP       NULL     DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_openid_unique` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序用户表';

-- ============================================================
--  3. admins  （后台管理员表）
-- ============================================================
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT   COMMENT '主键',
  `name`       VARCHAR(50)     NOT NULL                  COMMENT '姓名',
  `email`      VARCHAR(100)    NOT NULL                  COMMENT '邮箱（登录账号）',
  `password`   VARCHAR(255)    NOT NULL                  COMMENT 'bcrypt 密码',
  `role`       VARCHAR(20)     NOT NULL DEFAULT 'admin'  COMMENT '角色：super_admin / admin',
  `avatar`     VARCHAR(500)    NOT NULL DEFAULT ''       COMMENT '头像',
  `status`     TINYINT         NOT NULL DEFAULT 1        COMMENT '状态：1正常 0禁用',
  `created_at` TIMESTAMP       NULL     DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员表';

-- ============================================================
--  4. competition_rules  （比赛规则表）
-- ============================================================
DROP TABLE IF EXISTS `competition_rules`;
CREATE TABLE `competition_rules` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT   COMMENT '主键',
  `title`      VARCHAR(255)    NOT NULL                  COMMENT '规则标题',
  `summary`    TEXT            NULL                      COMMENT '简介',
  `content`    LONGTEXT        NOT NULL                  COMMENT '富文本内容（HTML）',
  `rule_date`  DATE            NOT NULL                  COMMENT '规则生效日期',
  `status`     TINYINT         NOT NULL DEFAULT 1        COMMENT '状态：1启用 0禁用',
  `created_at` TIMESTAMP       NULL     DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='比赛规则表';

-- ============================================================
--  5. dict_types  （字典类型表）
-- ============================================================
DROP TABLE IF EXISTS `dict_types`;
CREATE TABLE `dict_types` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT   COMMENT '主键',
  `code`       VARCHAR(50)     NOT NULL                  COMMENT '字典编码（唯一标识）',
  `name`       VARCHAR(100)    NOT NULL                  COMMENT '字典名称',
  `status`     TINYINT         NOT NULL DEFAULT 1        COMMENT '状态: 1=启用 0=禁用',
  `remark`     VARCHAR(255)    NOT NULL DEFAULT ''       COMMENT '备注',
  `created_at` TIMESTAMP       NULL     DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dict_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';

-- ============================================================
--  6. dict_items  （字典数据项表）
-- ============================================================
DROP TABLE IF EXISTS `dict_items`;
CREATE TABLE `dict_items` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT   COMMENT '主键',
  `type_code`  VARCHAR(50)     NOT NULL                  COMMENT '所属字典类型编码',
  `label`      VARCHAR(100)    NOT NULL                  COMMENT '显示标签',
  `value`      VARCHAR(100)    NOT NULL                  COMMENT '存储值',
  `sort`       INT             NOT NULL DEFAULT 0        COMMENT '排序（越小越靠前）',
  `status`     TINYINT         NOT NULL DEFAULT 1        COMMENT '状态: 1=启用 0=禁用',
  `remark`     VARCHAR(255)    NOT NULL DEFAULT ''       COMMENT '备注',
  `created_at` TIMESTAMP       NULL     DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_code` (`type_code`),
  CONSTRAINT `fk_dict_items_type` FOREIGN KEY (`type_code`) REFERENCES `dict_types` (`code`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据项表';

-- ============================================================
--  7. registrations  （报名 & 订单表）
-- ============================================================
DROP TABLE IF EXISTS `registrations`;
CREATE TABLE `registrations` (
  `id`                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT  COMMENT '主键 / 订单号',
  `user_id`            BIGINT UNSIGNED  NOT NULL                 COMMENT '用户 ID',
  `site_id`            BIGINT UNSIGNED  NULL     DEFAULT NULL     COMMENT '赛事站点（字典项 ID）',
  `site_name`          VARCHAR(100)     NULL     DEFAULT NULL     COMMENT '赛事站点名称(冗余)',
  -- 选手信息
  `name_pinyin`        VARCHAR(100)     NOT NULL DEFAULT ''      COMMENT '姓名（拼音）',
  `name_cn`            VARCHAR(50)      NOT NULL DEFAULT ''      COMMENT '姓名（汉字）',
  `nationality`        VARCHAR(50)      NOT NULL                 COMMENT '国籍',
  `gender`             TINYINT          NOT NULL                 COMMENT '性别：1男 2女',
  `id_type`            VARCHAR(20)      NOT NULL DEFAULT 'id_card' COMMENT '证件类型: id_card身份证, passport护照',
  `id_card`            VARCHAR(30)      NOT NULL DEFAULT ''       COMMENT '身份证号码',
  `passport_no`        VARCHAR(30)      NULL     DEFAULT NULL     COMMENT '护照号码',
  `birthday`           DATE             NULL     DEFAULT NULL     COMMENT '出生年月日',
  `age_group`          VARCHAR(50)      NOT NULL                 COMMENT '年龄组别',
  `belt_color`         VARCHAR(20)      NOT NULL                 COMMENT '带色',
  `weight_gi`          VARCHAR(50)      NULL     DEFAULT NULL     COMMENT '体重组别（道服 GI）',
  `gi_open`            TINYINT(1)       NOT NULL DEFAULT 0        COMMENT '是否加报道服无差组别',
  `weight_nogi`        VARCHAR(50)      NULL     DEFAULT NULL     COMMENT '体重组别（无道服 NO-GI）',
  `nogi_open`          TINYINT(1)       NOT NULL DEFAULT 0        COMMENT '是否加报无道服无差组别',
  `team`               VARCHAR(100)     NOT NULL                 COMMENT '战队',
  `phone`              VARCHAR(20)      NOT NULL                 COMMENT '手机号',
  `email`              VARCHAR(100)     NOT NULL                 COMMENT '邮箱',
  -- 套餐 & 支付
  `order_no`           VARCHAR(20)      NOT NULL DEFAULT ''      COMMENT '13位订单号',
  `package_key`        VARCHAR(30)      NULL     DEFAULT NULL     COMMENT '套餐 key（兼容旧数据）',
  `package_label`      VARCHAR(80)      NULL     DEFAULT NULL     COMMENT '套餐名称',
  `amount`             DECIMAL(8,2)     NOT NULL DEFAULT 0.00    COMMENT '应付金额（元）',
  `pay_status`         ENUM('pending','paid','cancelled','refund_pending','refunded')
                                        NOT NULL DEFAULT 'pending' COMMENT '支付状态',
  `confirm_status`     VARCHAR(20)      NOT NULL DEFAULT 'pending' COMMENT '确认状态: pending待确认, confirmed已确认',
  `wx_prepay_id`       VARCHAR(255)     NOT NULL DEFAULT ''      COMMENT '微信预支付 ID',
  `wx_transaction_id`  VARCHAR(64)      NOT NULL DEFAULT ''      COMMENT '微信交易流水号',
  `paid_at`            TIMESTAMP        NULL                     COMMENT '支付成功时间',
  `created_at`         TIMESTAMP        NULL     DEFAULT NULL,
  `updated_at`         TIMESTAMP        NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registrations_order_no_unique` (`order_no`),
  KEY `idx_user_pay` (`user_id`, `pay_status`),
  CONSTRAINT `fk_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报名订单表';

-- ============================================================
--  初始数据
-- ============================================================

-- 记录迁移历史
INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2024_01_01_000001_create_users_table',            1),
  ('2024_01_01_000002_create_admins_table',           1),
  ('2024_01_01_000003_create_competition_rules_table',1),
  ('2024_01_01_000004_create_registrations_table',    1),
  ('2024_01_01_000010_add_birthday_to_registrations', 2),
  ('2024_01_01_000011_add_confirm_status_and_refunded', 2),
  ('2024_01_01_000012_add_order_no_to_registrations', 2),
  ('2024_01_01_000013_add_refund_pending_to_pay_status', 3),
  ('2024_01_01_000014_create_competition_sites_table', 4),
  ('2024_01_01_000015_add_site_id_to_registrations', 4),
  ('2024_01_01_000016_create_dict_tables', 5),
  ('2024_01_01_000017_add_id_type_and_passport_to_registrations', 6);

-- 超级管理员（密码：Admin@123456）
INSERT INTO `admins` (`name`, `email`, `password`, `role`, `avatar`, `status`, `created_at`, `updated_at`)
VALUES (
  '超级管理员',
  'admin@kowloonjudo.com',
  '$2y$10$5EHg4VKyoJ4psZ.ub2KGTewG4/0mahwzqvvIyQWJcIipICuS7VYnC',
  'super_admin',
  '',
  1,
  NOW(),
  NOW()
);

-- 示例比赛规则（可在后台替换）
INSERT INTO `competition_rules` (`title`, `summary`, `content`, `rule_date`, `status`, `created_at`, `updated_at`)
VALUES (
  '九龙柔道公开赛 2026 参赛规则',
  '本次比赛设 GI 道服组、NO-GI 无道服组及少儿组，欢迎各界选手报名参赛。',
  '<h3>GI 道服组</h3>
<p>带色：白带、蓝带、紫带、棕带、黑带</p>
<p>年龄组别：儿童组（4-17岁）按年龄分组，体重级别见各组详细规定。</p>
<h3>NO-GI 无道服组</h3>
<p>带色：白带、蓝带、紫带、棕带、黑带</p>
<p>年龄：成人（18岁或以上）、大师1（30岁以上）</p>
<h3>少儿组</h3>
<p>带色：白带、灰带、黄带、橙带、绿带</p>
<p>年龄组别：儿童组（4-17岁）</p>
<h3>报名费用</h3>
<ul>
  <li>道服 ¥420</li>
  <li>道服 + 无差别 ¥500</li>
  <li>无道服 ¥420</li>
  <li>无道服 + 无差别 ¥500</li>
  <li>道服 + 无道服 ¥840</li>
  <li>道服 + 无道服 + 无差别 ¥920</li>
  <li>道服+无差别 + 无道服+无差别 ¥1000</li>
</ul>',
  '2026-03-01',
  1,
  NOW(),
  NOW()
);

-- 字典类型：赛事站点
INSERT INTO `dict_types` (`code`, `name`, `status`, `remark`, `created_at`, `updated_at`)
VALUES ('competition_site', '赛事站点', 1, '比赛报名可选的赛事站点', NOW(), NOW());

-- 字典值示例：赛事站点
INSERT INTO `dict_items` (`type_code`, `label`, `value`, `sort`, `status`, `remark`, `created_at`, `updated_at`)
VALUES ('competition_site', 'copa de chn南宁站', 'copa de chn南宁站', 0, 1, '', NOW(), NOW());

-- ------------------------------------------------------------
-- 费用设置表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(50)     NOT NULL                COMMENT '设置项键名',
  `value`      VARCHAR(255)    NOT NULL DEFAULT ''      COMMENT '设置项值',
  `label`      VARCHAR(100)    NOT NULL DEFAULT ''      COMMENT '设置项名称',
  `created_at` TIMESTAMP       NULL DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

INSERT IGNORE INTO `settings` (`key`, `value`, `label`, `created_at`, `updated_at`) VALUES
  ('category_fee', '360', '组别费用（元）', NOW(), NOW()),
  ('open_weight_fee', '80', '无差别组别费用（元）', NOW(), NOW());

-- ------------------------------------------------------------
-- 公告表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(100)    NOT NULL                COMMENT '公告标题',
  `content`    TEXT            NOT NULL                COMMENT '公告内容',
  `status`     TINYINT         NOT NULL DEFAULT 1      COMMENT '状态：1显示 0隐藏',
  `sort`       INT             NOT NULL DEFAULT 0      COMMENT '排序（越大越靠前）',
  `created_at` TIMESTAMP       NULL DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

-- 插入一条默认公告
INSERT IGNORE INTO `announcements` (`title`, `content`, `status`, `sort`, `created_at`, `updated_at`) VALUES (
  '九龙柔道报名通知',
  '九龙柔道报名通道开启！欢迎参加本次比赛，如有疑问请联系组委会。',
  1,
  100,
  NOW(),
  NOW()
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  完成提示
-- ============================================================
SELECT '✅ kowloon_judo 数据库初始化完成！' AS message;
SELECT '   管理员账号: admin@kowloonjudo.com' AS info
UNION ALL
SELECT '   管理员密码: Admin@123456';
