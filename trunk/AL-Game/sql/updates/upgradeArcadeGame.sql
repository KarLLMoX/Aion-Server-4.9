ALTER TABLE `players`
ADD COLUMN `frenzy_points`  int(4) NOT NULL DEFAULT 0 COMMENT 'Upgrade Arcade FrenzyPoints' AFTER `joinRequestState`,
ADD COLUMN `frenzy_count`  int(1) NOT NULL DEFAULT 0 AFTER `frenzy_points`;