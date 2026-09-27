<?php

return array (
  'createTable' => 
  array (
    0 => 'CREATE TABLE `users` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `age` INT UNSIGNED NULL, `price` DECIMAL(10,2) DEFAULT 1.5, `bio` TEXT DEFAULT \'it\'\'s\', `active` TINYINT(1) DEFAULT 1, `status` ENUM(\'a\',\'b\') DEFAULT \'a\', `meta` JSON NULL, `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `date_modify` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `uid` VARCHAR(36) DEFAULT NULL, PRIMARY KEY (`id`))',
    1 => 'CREATE TRIGGER `trigger_audou_492d036c8ee9e48c889263283e1697be` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN SET NEW.`date_modify` = CURRENT_TIMESTAMP; END;',
  ),
  'createTableBigintAutoincrement' => 
  array (
    0 => 'CREATE TABLE `logs` (`id` BIGINT AUTO_INCREMENT, `code` CHAR(3) DEFAULT \'abc\', PRIMARY KEY (`id`))',
  ),
  'insert' => 
  array (
    0 => 'INSERT INTO `users` (`name`, `age`) VALUES (:name, :age)',
    1 => 'bind: {":name":"john",":age":3}',
  ),
  'update' => 
  array (
    0 => 'UPDATE `users` SET `name` = :name, `age` = :age WHERE id=5',
    1 => 'bind: {":name":"jane",":age":4}',
  ),
  'updateValue' => 
  array (
    0 => 'UPDATE `users` SET `name` = :name WHERE id=5',
    1 => 'bind: {":name":"jim"}',
  ),
  'delete' => 
  array (
    0 => 'DELETE FROM `users` WHERE id=5',
  ),
  'deleteByConditions' => 
  array (
    0 => 'DELETE FROM `users` WHERE (`name` = :bind_0 AND `users`.`age` > :bind_1)',
    1 => 'bind: {":bind_0":"x",":bind_1":3}',
  ),
  'find' => 
  array (
    0 => 'DESCRIBE `users`;',
    1 => 'SELECT `users`.`id` as `users.id`, `users`.`name` as `users.name`, `users`.`status` as `users.status` FROM `users`  WHERE (`users`.`age` >= :bind_0 AND `status` IN (:bind_1, :bind_2) AND `created` BETWEEN :bind_3 AND :bind_4 AND (`name` = :bind_5 OR `bio` = :bind_6) AND `users`.`bio` IS NOT NULL AND `age` NOT IN (:bind_7, :bind_8)) ORDER BY name DESC LIMIT 1',
    2 => 'bind: {":bind_0":18,":bind_1":"a",":bind_2":"b",":bind_3":"2020-01-01",":bind_4":"2021-01-01",":bind_5":"john",":bind_6":"x",":bind_7":1,":bind_8":2}',
  ),
  'findCustomColumns' => 
  array (
    0 => 'SELECT `users`.`id` as `users.id`, `users`.`name` as `users.name` FROM `users`  WHERE (`id` = :bind_0) LIMIT 1',
    1 => 'bind: {":bind_0":1}',
  ),
  'findAll' => 
  array (
    0 => 'DESCRIBE `users`;',
    1 => 'SELECT `users`.`id` as `users.id`, `users`.`name` as `users.name`, `users`.`status` as `users.status` FROM `users`  WHERE (`active` = :bind_0) GROUP BY `status` ORDER BY `users`.`name` DESC, `age` ASC LIMIT 10 OFFSET 5',
    2 => 'bind: {":bind_0":1}',
  ),
  'findAllRawOrderWithFunction' => 
  array (
    0 => 'SELECT `users`.`id` as `users.id` FROM `users`  ORDER BY COALESCE(`users`.`age`) ASC, `name` LIMIT 3',
  ),
  'findAllJoin' => 
  array (
    0 => 'DESCRIBE `users`;',
    1 => 'DESCRIBE `posts`;',
    2 => 'DESCRIBE `roles`;',
    3 => 'SELECT `users`.`id` as `users.id`, `users`.`name` as `users.name`, `users`.`status` as `users.status`, `posts`.`id` as `posts.id`, `posts`.`name` as `posts.name`, `posts`.`status` as `posts.status`, `r`.`id` as `r.id`, `r`.`name` as `r.name`, `r`.`status` as `r.status` FROM `users` LEFT JOIN `posts` ON `users`.`id` = `posts`.`users_id` AND (`published` = :bind_1) INNER JOIN `roles` as `r` ON `users`.`role_id` = `r`.`id` AND `r`.`active` = :bind_2 WHERE (`users`.`id` = :bind_0)',
    4 => 'bind: {":bind_0":1,":bind_1":1,":bind_2":1}',
  ),
  'findCount' => 
  array (
    0 => 'SELECT COUNT(*) as `count` FROM `users`  WHERE (`active` = :bind_0)',
    1 => 'bind: {":bind_0":1}',
  ),
  'findCountGrouped' => 
  array (
    0 => 'SELECT COUNT(*) as `count` FROM (SELECT 1 FROM `users`  WHERE (`active` = :bind_0) GROUP BY `status`) as `grouped_count`',
    1 => 'bind: {":bind_0":1}',
  ),
  'findIsset' => 
  array (
    0 => 'SELECT COUNT(*) as `count` FROM `users`  WHERE (`name` = :bind_0)',
    1 => 'bind: {":bind_0":"john"}',
  ),
  'columnList' => 
  array (
    0 => 'DESCRIBE `users`;',
  ),
  'exists' => 
  array (
    0 => 'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1;',
    1 => 'bind: ["users"]',
  ),
  'getTables' => 
  array (
    0 => 'SHOW TABLES',
  ),
  'createIndex' => 
  array (
    0 => 'CREATE INDEX `index_users_name_age` ON `users`(`name`, `age`)',
  ),
  'alterTable' => 
  array (
    0 => 'ALTER TABLE `users` ADD `nick` VARCHAR(50) NULL AFTER `name`;
ALTER TABLE `users` DROP COLUMN `bio`;
ALTER TABLE `users` MODIFY `age` int(11) NOT NULL;
ALTER TABLE `users` RENAME COLUMN `nick` TO `nickname`;',
  ),
  'isColumnUnique' => 
  array (
    0 => 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND COLUMN_KEY = \'UNI\'',
    1 => 'bind: ["users","name"]',
  ),
);
