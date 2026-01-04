-- =====================================================
-- Database Schema: Sistem Pengajuan Cuti Mahasiswa
-- File: database.sql
-- =====================================================


CREATE DATABASE IF NOT EXISTS `uts_web` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `uts_web`;

-- Tabel Users (Updated dengan role)
DROP TABLE IF EXISTS `pengajuan`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `mahasiswa`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `namalengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'user', 'mahasiswa', 'dosen') NOT NULL DEFAULT 'user',
  `nim` VARCHAR(20) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_nim_unique` (`nim`),
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Mahasiswa
CREATE TABLE IF NOT EXISTS `mahasiswa` (
  `nim` VARCHAR(20) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `jurusan` VARCHAR(100) NOT NULL,
  `angkatan` VARCHAR(4) NOT NULL,
  `email` VARCHAR(100),
  `telepon` VARCHAR(20),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`nim`),
  INDEX `idx_nama` (`nama`),
  INDEX `idx_jurusan` (`jurusan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Registrations (existing)
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `nim` VARCHAR(20) NOT NULL,
  `nama_mk` VARCHAR(100) NOT NULL,
  `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`),
  CONSTRAINT `fk_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pengajuan Cuti
CREATE TABLE IF NOT EXISTS `pengajuan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `nim` VARCHAR(20) NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `jenis_cuti` ENUM('Sakit', 'Izin', 'Cuti Bersama', 'Lainnya') NOT NULL,
  `alasan` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `approved_by` INT(11) NULL,
  `approved_at` TIMESTAMP NULL,
  `catatan_approval` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_nim` (`nim`),
  INDEX `idx_user_id` (`user_id`),
  CONSTRAINT `fk_pengajuan_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pengajuan_mahasiswa` FOREIGN KEY (`nim`) REFERENCES `mahasiswa`(`nim`) ON DELETE CASCADE,
  CONSTRAINT `fk_pengajuan_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `namalengkap`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('mhs', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mahasiswa Demo', 'mahasiswa');

-- Insert sample mahasiswa data
INSERT INTO `mahasiswa` (`nim`, `nama`, `jurusan`, `angkatan`, `email`, `telepon`) VALUES
('2021001', 'Budi Santoso', 'Teknik Informatika', '2021', 'budi@example.com', '081234567890'),
('2021002', 'Siti Nurhaliza', 'Sistem Informasi', '2021', 'siti@example.com', '081234567891'),
('2022001', 'Ahmad Fauzi', 'Teknik Informatika', '2022', 'ahmad@example.com', '081234567892');
