-- SHGM Exam System — Create Database
CREATE DATABASE IF NOT EXISTS `shgm_exam_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `shgm_exam_system`;

-- İsteğe bağlı: bağlantı/oturum için karakter seti ve saat dilimi
SET NAMES utf8mb4;
SET time_zone = '+03:00';
