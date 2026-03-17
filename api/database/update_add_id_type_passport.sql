-- ============================================================
--  增量更新脚本：为 registrations 表新增 id_type、passport_no 字段
--  适用：已有数据库，直接导入执行
--  执行前请先备份数据库！
-- ============================================================

SET NAMES utf8mb4;

-- 1. 新增 id_type 字段（证件类型），放在 gender 之后
ALTER TABLE `registrations`
  ADD COLUMN `id_type` VARCHAR(20) NOT NULL DEFAULT 'id_card' COMMENT '证件类型: id_card=身份证, passport=护照'
  AFTER `gender`;

-- 2. 新增 passport_no 字段（护照号码），放在 id_card 之后
ALTER TABLE `registrations`
  ADD COLUMN `passport_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '护照号码'
  AFTER `id_card`;

-- 3. 记录迁移历史（可选，防止 artisan migrate 重复执行）
INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2024_01_01_000017_add_id_type_and_passport_to_registrations', 11);

SELECT '✅ 已成功新增 id_type 和 passport_no 字段' AS result;
