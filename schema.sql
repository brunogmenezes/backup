-- Criar Banco de Dados do Pure-FTPd se não existir
CREATE DATABASE IF NOT EXISTS `pureftpd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pureftpd`;

-- Tabela de Administradores do Painel
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Dispositivos (Equipamentos)
CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `model` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Usuários FTP (Lida pelo Pure-FTPd, com colunas idênticas às de produção)
CREATE TABLE IF NOT EXISTS `ftpd` (
  `User` VARCHAR(64) NOT NULL PRIMARY KEY,
  `status` TINYINT(1) NOT NULL DEFAULT 1, -- 1 = ativo, 0 = inativo
  `Password` VARCHAR(64) NOT NULL,
  `Uid` INT(11) NOT NULL DEFAULT 2001,
  `Gid` INT(11) NOT NULL DEFAULT 2001,
  `Dir` VARCHAR(255) NOT NULL,
  `ULBandwidth` INT(11) NOT NULL DEFAULT 0,
  `DLBandwidth` INT(11) NOT NULL DEFAULT 0,
  `comment` VARCHAR(255) DEFAULT '',
  `ipaccess` VARCHAR(255) DEFAULT '*',
  `QuotaSize` INT(11) NOT NULL DEFAULT 0,
  `QuotaFiles` INT(11) NOT NULL DEFAULT 0,
  `device_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ftpd_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
