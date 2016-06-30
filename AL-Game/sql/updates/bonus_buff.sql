ALTER TABLE `players` ADD `bonus_buff_time` timestamp NULL default NULL;
ALTER TABLE `players` ADD COLUMN `bonus_type`  enum('RETURN','NEW','NORMAL') NOT NULL DEFAULT 'NORMAL';
