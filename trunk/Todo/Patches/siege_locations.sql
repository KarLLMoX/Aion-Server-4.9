ALTER TABLE `siege_locations`
ADD COLUMN `occupy_count` int(1) NOT NULL DEFAULT 0 AFTER `legion_id`;