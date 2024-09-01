--SQL changes for upgrades from 1.71/1.72 to 1.73:

--Add Webhooks table
CREATE TABLE `sm_webhooks` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `url` varchar(2048) NOT NULL,
  `criteria` varchar(20) DEFAULT NULL,
  `qualifier` int(11) DEFAULT NULL,
  `jwt` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;