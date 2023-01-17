--SQL changes for upgrades from 1.71/1.72 to 1.73:

--Add Webhooks table
CREATE TABLE `sm_webhooks` ( `id` INT NOT NULL AUTO_INCREMENT , `type` INT NOT NULL , `url` VARCHAR(2048) NOT NULL , `criteria` VARCHAR(20) NULL DEFAULT NULL , `qualifier` INT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;