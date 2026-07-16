-- Criar Banco de Dados (opcional, o instalador pode criar ou usar existente)
CREATE DATABASE IF NOT EXISTS `backup_manager` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `backup_manager`;

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
  `status` VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Usuários FTP (Lida pelo Pure-FTPd)
CREATE TABLE IF NOT EXISTS `ftp_users` (
  `User` VARCHAR(64) NOT NULL PRIMARY KEY,
  `Password` VARCHAR(128) NOT NULL, -- pure-ftpd suporta texto plano, md5, etc.
  `Uid` INT NOT NULL DEFAULT 2000,
  `Gid` INT NOT NULL DEFAULT 2000,
  `Dir` VARCHAR(255) NOT NULL,
  `ULBandwidth` INT NOT NULL DEFAULT 0, -- limite de upload em KB/s (0 = ilimitado)
  `DLBandwidth` INT NOT NULL DEFAULT 0, -- limite de download em KB/s (0 = ilimitado)
  `Status` TINYINT NOT NULL DEFAULT 1, -- 1 = ativo, 0 = inativo
  `device_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ftp_users_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
