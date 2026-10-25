-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 24, 2026 at 01:30 AM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medcare_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int NOT NULL DEFAULT '30',
  `appointment_type` enum('consultation','follow_up','treatment','vaccination','screening','other') DEFAULT 'consultation',
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `reason` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  KEY `idx_date` (`appointment_date`),
  KEY `idx_doctor_date` (`doctor_id`,`appointment_date`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `duration_minutes`, `appointment_type`, `status`, `reason`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2026-10-24', '09:00:00', 30, 'follow_up', 'confirmed', 'Blood pressure and blood glucose monitoring', 'Ask patient to bring home BP readings log', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(2, 2, 2, '2026-10-24', '10:30:00', 45, 'consultation', 'scheduled', 'Asthma follow-up and inhaler review', 'Check peak flow readings', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(3, 3, 2, '2026-10-24', '13:00:00', 30, 'screening', 'scheduled', 'Routine blood pressure screening', NULL, '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(4, 1, 2, '2026-10-25', '09:30:00', 30, 'consultation', 'scheduled', 'Diabetes management review', 'Review latest HbA1C result', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(5, 4, 3, '2026-10-25', '14:00:00', 30, 'follow_up', 'scheduled', 'Gastritis recurrence check', NULL, '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(6, 5, 2, '2026-10-26', '11:00:00', 30, 'treatment', 'confirmed', 'Nebulization session', 'Asthma maintenance therapy', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(7, 6, 2, '2026-10-27', '15:30:00', 30, 'follow_up', 'scheduled', 'Viral infection recovery follow-up', NULL, '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(8, 2, 2, '2026-10-28', '08:30:00', 30, 'consultation', 'scheduled', 'Asthma exacerbation assessment', 'Patient reports increased inhaler use', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(9, 3, 3, '2026-10-29', '10:00:00', 30, 'consultation', 'scheduled', 'Migraine frequency evaluation', NULL, '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(10, 1, 2, '2026-10-22', '11:00:00', 45, 'follow_up', 'completed', 'Asthma control review', 'Symptoms improving', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(11, 5, 2, '2026-10-19', '09:00:00', 30, 'consultation', 'completed', 'Hypertension monitoring', 'Continue current regimen', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(12, 6, 2, '2026-10-20', '14:00:00', 30, 'follow_up', 'completed', 'Treatment progress assessment', 'Medication well tolerated', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(13, 2, 2, '2026-10-23', '10:00:00', 30, 'follow_up', 'cancelled', 'Asthma check-up', 'Cancelled by patient due to conflict', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(14, 4, 3, '2026-10-21', '13:30:00', 30, 'screening', 'no_show', 'Routine check-up', 'Patient did not appear on scheduled date', '2026-10-24 00:41:22', '2026-10-24 00:41:22'),
(15, 1, 2, '2026-10-24', '09:00:00', 30, 'follow_up', 'confirmed', 'Blood pressure and blood glucose monitoring', 'Ask patient to bring home BP readings log', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(16, 2, 2, '2026-10-24', '10:30:00', 45, 'consultation', 'scheduled', 'Asthma follow-up and inhaler review', 'Check peak flow readings', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(17, 3, 2, '2026-10-24', '13:00:00', 30, 'screening', 'scheduled', 'Routine blood pressure screening', NULL, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(18, 1, 2, '2026-10-25', '09:30:00', 30, 'consultation', 'scheduled', 'Diabetes management review', 'Review latest HbA1C result', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(19, 4, 3, '2026-10-25', '14:00:00', 30, 'follow_up', 'scheduled', 'Gastritis recurrence check', NULL, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(20, 5, 2, '2026-10-26', '11:00:00', 30, 'treatment', 'confirmed', 'Nebulization session', 'Asthma maintenance therapy', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(21, 6, 2, '2026-10-27', '15:30:00', 30, 'follow_up', 'scheduled', 'Viral infection recovery follow-up', NULL, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(22, 2, 2, '2026-10-28', '08:30:00', 30, 'consultation', 'scheduled', 'Asthma exacerbation assessment', 'Patient reports increased inhaler use', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(23, 3, 3, '2026-10-29', '10:00:00', 30, 'consultation', 'scheduled', 'Migraine frequency evaluation', NULL, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(24, 1, 2, '2026-10-22', '11:00:00', 45, 'follow_up', 'completed', 'Asthma control review', 'Symptoms improving', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(25, 5, 2, '2026-10-19', '09:00:00', 30, 'consultation', 'completed', 'Hypertension monitoring', 'Continue current regimen', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(26, 6, 2, '2026-10-20', '14:00:00', 30, 'follow_up', 'completed', 'Treatment progress assessment', 'Medication well tolerated', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(27, 2, 2, '2026-10-23', '10:00:00', 30, 'follow_up', 'cancelled', 'Asthma check-up', 'Cancelled by patient due to conflict', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(28, 4, 3, '2026-10-21', '13:30:00', 30, 'screening', 'no_show', 'Routine check-up', 'Patient did not appear on scheduled date', '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
CREATE TABLE IF NOT EXISTS `consultations` (
  `consultation_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `consultation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `chief_complaint` text,
  `vital_signs` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`consultation_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_date` (`consultation_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `consultations`
--

INSERT INTO `consultations` (`consultation_id`, `patient_id`, `doctor_id`, `consultation_date`, `chief_complaint`, `vital_signs`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2024-09-20 01:30:00', 'Follow-up for hypertension and diabetes', 'BP: 130/85, HR: 78, Temp: 36.8°C', 'Patient reports good compliance with medication', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(2, 2, 2, '2024-09-21 02:15:00', 'Difficulty breathing and chest tightness', 'BP: 140/90, HR: 95, Temp: 37.2°C', 'Asthma exacerbation possibly triggered by recent weather change', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(3, 3, 3, '2024-09-22 06:00:00', 'Severe headache with nausea', 'BP: 120/80, HR: 72, Temp: 36.5°C', 'Migraine attack, stress-related', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(4, 4, 2, '2024-09-22 08:30:00', 'Routine follow-up after gastritis treatment', 'BP: 125/82, HR: 75, Temp: 36.7°C', 'No recurrence of symptoms', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(5, 6, 2, '2024-09-22 03:00:00', 'Fever and body weakness for 2 days', 'BP: 110/70, HR: 92, Temp: 38.5°C', 'Possible viral infection, needs further evaluation', '2026-10-24 00:42:02', '2026-10-24 00:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `diagnoses`
--

DROP TABLE IF EXISTS `diagnoses`;
CREATE TABLE IF NOT EXISTS `diagnoses` (
  `diagnosis_id` int NOT NULL AUTO_INCREMENT,
  `consultation_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `diagnosis_code` varchar(20) DEFAULT NULL,
  `diagnosis_name` varchar(255) NOT NULL,
  `description` text,
  `severity` enum('mild','moderate','severe') DEFAULT NULL,
  `diagnosed_by` int NOT NULL,
  `diagnosis_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`diagnosis_id`),
  KEY `diagnosed_by` (`diagnosed_by`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_consultation` (`consultation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `diagnoses`
--

INSERT INTO `diagnoses` (`diagnosis_id`, `consultation_id`, `patient_id`, `diagnosis_code`, `diagnosis_name`, `description`, `severity`, `diagnosed_by`, `diagnosis_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'I10', 'Essential (Primary) Hypertension', 'Chronic high blood pressure', 'moderate', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(2, 1, 1, 'E11', 'Type 2 Diabetes Mellitus', 'Chronic condition requiring management', 'moderate', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(3, 2, 2, 'J45', 'Asthma', 'Chronic inflammatory disease of airways', 'severe', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(4, 3, 3, 'G43', 'Migraine', 'Recurrent headaches with nausea', 'moderate', 3, '2026-10-24 00:42:02', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(5, 4, 4, 'K29', 'Gastritis', 'Inflammation of stomach lining', 'mild', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(6, 5, 6, 'J06', 'Acute Upper Respiratory Infection', 'Viral infection of upper respiratory tract', 'moderate', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02', '2026-10-24 00:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `healthcare_communication`
--

DROP TABLE IF EXISTS `healthcare_communication`;
CREATE TABLE IF NOT EXISTS `healthcare_communication` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `message_type` enum('general','patient_update','treatment_update','consultation_request','inventory_alert') DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `healthcare_communication`
--

INSERT INTO `healthcare_communication` (`message_id`, `sender_id`, `recipient_id`, `patient_id`, `subject`, `message`, `is_read`, `read_at`, `message_type`, `created_at`) VALUES
(1, 2, 4, 2, 'Patient Update: Asthma Exacerbation', 'Patient Robert Tan was seen today for asthma exacerbation. Prescribed bronchodilator therapy. Please monitor stock of salbutamol inhalers.', 0, NULL, 'patient_update', '2026-10-24 00:42:18'),
(2, 4, 2, 2, 'Inventory Status: Salbutamol', 'Salbutamol inhalers are in stock (25 units). Current stock level is adequate.', 0, NULL, 'inventory_alert', '2026-10-24 00:42:18'),
(3, 2, NULL, 1, 'General: Treatment Protocol Update', 'Updated hypertension treatment protocol. Please review new guidelines for patient management.', 0, NULL, 'general', '2026-10-24 00:42:18'),
(4, 3, 2, 3, 'Consultation Request: Migraine Patient', 'Patient Elena Flores with severe migraine may need neurological evaluation. Please review case.', 0, NULL, 'consultation_request', '2026-10-24 00:42:18'),
(5, 4, 2, 6, 'Treatment Update: Viral Infection', 'Patient Pedro Reyes showing improvement with symptomatic treatment. Fever reduced, no complications reported.', 0, NULL, 'treatment_update', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `health_education`
--

DROP TABLE IF EXISTS `health_education`;
CREATE TABLE IF NOT EXISTS `health_education` (
  `resource_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `summary` text,
  `author` varchar(100) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_date` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resource_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `health_education`
--

INSERT INTO `health_education` (`resource_id`, `title`, `category`, `content`, `summary`, `author`, `status`, `published_date`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Understanding Hypertension', 'Disease Prevention', 'Hypertension, or high blood pressure, is a common condition that affects the body\'s arteries. Learn about causes, symptoms, and management strategies including lifestyle changes and medication adherence.', 'Comprehensive guide to understanding and managing high blood pressure through lifestyle modifications and proper medication use.', 'Dr. Maria Santos', 'published', '2024-09-01', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(2, 'Diabetes Management Tips', 'Healthy Living', 'Effective diabetes management requires a combination of healthy eating, regular physical activity, and medication adherence. This guide provides practical tips for daily diabetes care.', 'Practical advice for maintaining healthy blood sugar levels through diet, exercise, and medication management.', 'Dr. Jose Cruz', 'published', '2024-09-05', 3, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(3, 'Asthma Action Plan', 'Disease Prevention', 'An asthma action plan helps patients recognize worsening symptoms and take appropriate action. Learn about early warning signs, trigger avoidance, and emergency response procedures.', 'Step-by-step guide for managing asthma attacks and knowing when to seek emergency care.', 'Dr. Maria Santos', 'published', '2024-09-10', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(4, 'Healthy Eating for Heart Health', 'Healthy Living', 'A heart-healthy diet can help lower your risk of heart disease. Learn about foods to include and avoid, portion control, and meal planning strategies for cardiovascular wellness.', 'Nutritional guidelines for maintaining heart health through proper diet and lifestyle choices.', 'Ana Garcia', 'published', '2024-09-12', 4, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(5, 'Stress Management Techniques', 'Healthy Living', 'Chronic stress can negatively impact both physical and mental health. Discover effective stress management techniques including relaxation exercises, mindfulness, and time management strategies.', 'Evidence-based approaches to reducing stress and improving overall well-being through practical techniques.', 'Dr. Jose Cruz', 'published', '2024-09-15', 3, '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `inventory_id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `item_type` enum('medication','medical_supply') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text,
  `quantity` int NOT NULL DEFAULT '0',
  `unit` varchar(50) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `minimum_stock_level` int DEFAULT '10',
  `location` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inventory_id`),
  KEY `idx_type` (`item_type`),
  KEY `idx_expiration` (`expiration_date`),
  KEY `idx_stock` (`quantity`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `item_name`, `item_type`, `category`, `description`, `quantity`, `unit`, `batch_number`, `expiration_date`, `minimum_stock_level`, `location`, `manufacturer`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Amlodipine 5mg', 'medication', 'Antihypertensive', 'Blood pressure medication', 100, 'tablets', 'BATCH001', '2027-12-31', 20, NULL, 'PharmaCorp', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(2, 'Metformin 500mg', 'medication', 'Antidiabetic', 'Blood sugar control medication', 60, 'tablets', 'BATCH002', '2027-06-30', 15, NULL, 'MediCare Labs', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(3, 'Sumatriptan 50mg', 'medication', 'Analgesic', 'Migraine relief medication', 75, 'tablets', 'BATCH003', '2027-09-15', 20, NULL, 'PharmaCorp', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(4, 'Salbutamol Inhaler', 'medication', 'Bronchodilator', 'Asthma relief inhaler', 25, 'units', 'BATCH007', '2028-06-30', 10, NULL, 'PharmaCorp', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(5, 'Omeprazole 20mg', 'medication', 'Antacid', 'Proton pump inhibitor', 60, 'capsules', 'BATCH008', '2028-12-31', 15, NULL, 'MediCare Labs', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(6, 'Paracetamol 500mg', 'medication', 'Analgesic', 'Pain reliever and fever reducer', 150, 'tablets', 'BATCH009', '2027-08-15', 20, NULL, 'PharmaCorp', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(7, 'Medical Gloves', 'medical_supply', 'PPE', 'Disposable latex gloves', 200, 'pairs', 'BATCH004', '2028-01-31', 50, NULL, 'MedSupply Co', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(8, 'Surgical Masks', 'medical_supply', 'PPE', 'Disposable surgical masks', 150, 'pieces', 'BATCH005', '2028-03-31', 30, NULL, 'MedSupply Co', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(9, 'Alcohol 70%', 'medical_supply', 'Antiseptic', 'Ethyl alcohol 70% solution', 30, 'bottles', 'BATCH006', '2027-11-30', 10, NULL, 'CleanLife Inc', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(10, 'Cetirizine 10mg', 'medication', 'Antihistamine', 'Allergy relief', 80, 'tablets', 'BATCH010', '2028-02-28', 30, NULL, 'HealthPlus Inc', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(11, 'Vitamin C 500mg', 'medication', 'Supplement', 'Immune system support', 120, 'tablets', 'BATCH011', '2028-06-30', 30, NULL, 'HealthPlus Inc', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(12, 'Antiseptic Solution', 'medical_supply', 'Antiseptic', 'Wound cleaning solution', 45, 'bottles', 'BATCH012', '2027-10-15', 15, NULL, 'CleanLife Inc', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(13, 'Thermometer Digital', 'medical_supply', 'Equipment', 'Digital thermometer for temperature measurement', 12, 'units', 'BATCH013', '2029-01-31', 5, NULL, 'MedEquip Co', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(14, 'Blood Pressure Monitor', 'medical_supply', 'Equipment', 'Digital BP monitoring device', 8, 'units', 'BATCH014', '2029-03-15', 3, NULL, 'MedEquip Co', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(15, 'Syringes 5ml', 'medical_supply', 'Consumable', 'Disposable syringes for injections', 200, 'pieces', 'BATCH015', '2028-06-30', 50, NULL, 'MedSupply Co', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(16, 'Bandages Assorted', 'medical_supply', 'First Aid', 'Various sizes of adhesive bandages', 150, 'pieces', 'BATCH016', '2029-01-15', 30, NULL, 'FirstAid Co', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(17, 'Expired Medicine Sample', 'medication', 'Antibiotic', 'Sample expired medication for testing', 5, 'tablets', 'BATCH999', '2023-01-01', 10, NULL, 'Test Labs', 1, '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `inventory_id` int NOT NULL,
  `transaction_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int NOT NULL,
  `reference_type` enum('purchase','dispensing','prescription','adjustment','return','expiry') DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text,
  `performed_by` int NOT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `performed_by` (`performed_by`),
  KEY `idx_inventory` (`inventory_id`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_type` (`transaction_type`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`transaction_id`, `inventory_id`, `transaction_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `performed_by`, `transaction_date`) VALUES
(1, 1, 'in', 100, 'purchase', NULL, 'Initial stock purchase', 1, '2026-10-24 00:42:18'),
(2, 2, 'in', 60, 'purchase', NULL, 'Initial stock purchase', 1, '2026-10-24 00:42:18'),
(3, 3, 'in', 75, 'purchase', NULL, 'Initial stock purchase', 1, '2026-10-24 00:42:18'),
(4, 4, 'in', 25, 'purchase', NULL, 'Initial stock purchase', 1, '2026-10-24 00:42:18'),
(5, 5, 'in', 60, 'purchase', NULL, 'Initial stock purchase', 1, '2026-10-24 00:42:18'),
(6, 6, 'in', 150, 'purchase', NULL, 'Initial stock purchase', 1, '2026-10-24 00:42:18'),
(7, 1, 'out', 30, 'dispensing', 1, 'Prescription #1 dispensed', 4, '2026-10-24 00:42:18'),
(8, 2, 'out', 60, 'dispensing', 1, 'Prescription #1 dispensed', 4, '2026-10-24 00:42:18'),
(9, 5, 'out', 30, 'dispensing', 4, 'Prescription #4 dispensed', 4, '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
CREATE TABLE IF NOT EXISTS `patients` (
  `patient_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_name` (`last_name`,`first_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `contact_number`, `address`, `emergency_contact_name`, `emergency_contact_number`, `blood_type`, `allergies`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Carmela', 'Mendoza', '1990-03-15', 'female', '09187654321', 'Barangay Poblacion, Tupi, South Cotabato', 'Jose Mendoza', '09187654322', 'A+', 'Penicillin', '2026-10-24 00:41:52', '2026-10-24 00:41:52'),
(2, NULL, 'Roberto', 'Tan', '1978-08-22', 'male', '09191234567', 'Barangay San Isidro, Tupi, South Cotabato', 'Luz Tan', '09191234568', 'B+', 'None', '2026-10-24 00:41:52', '2026-10-24 00:41:52'),
(3, NULL, 'Elena', 'Flores', '1985-11-30', 'female', '09201234567', 'Barangay Cane 1, Tupi, South Cotabato', 'Antonio Flores', '09201234568', 'O+', 'Sulfa drugs', '2026-10-24 00:41:52', '2026-10-24 00:41:52'),
(4, NULL, 'Miguel', 'Reyes', '1992-05-10', 'male', '09211234567', 'Barangay Linan, Tupi, South Cotabato', 'Teresa Reyes', '09211234568', 'AB+', 'None', '2026-10-24 00:41:52', '2026-10-24 00:41:52'),
(5, NULL, 'Sofia', 'Garcia', '1988-02-14', 'female', '09221234567', 'Barangay Kablon, Tupi, South Cotabato', 'Ricardo Garcia', '09221234568', 'A-', 'Aspirin', '2026-10-24 00:41:52', '2026-10-24 00:41:52'),
(6, 6, 'Pedro', 'Reyes', '1985-05-15', 'male', '09171234567', 'Poblacion, Tupi, South Cotabato', 'Maria Reyes', '09181234567', 'O+', 'None', '2026-10-24 00:41:52', '2026-10-24 00:41:52'),
(7, 7, 'Maria', 'Santos', '1992-08-24', 'female', '09229876543', 'Barangay Acmonan, Tupi, South Cotabato', 'Juan Santos', '09229876544', 'B+', 'None', '2026-10-24 00:41:52', '2026-10-24 00:41:52');

-- --------------------------------------------------------

--
-- Table structure for table `patient_medical_history`
--

DROP TABLE IF EXISTS `patient_medical_history`;
CREATE TABLE IF NOT EXISTS `patient_medical_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `condition_name` varchar(255) DEFAULT NULL,
  `diagnosis_date` date DEFAULT NULL,
  `status` enum('active','resolved','chronic') DEFAULT 'active',
  `notes` text,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_patient` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_medical_history`
--

INSERT INTO `patient_medical_history` (`history_id`, `patient_id`, `condition_name`, `diagnosis_date`, `status`, `notes`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Hypertension', '2020-01-15', 'chronic', 'Controlled with medication', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(2, 1, 'Type 2 Diabetes', '2021-03-20', 'chronic', 'Diet-controlled', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(3, 2, 'Asthma', '2019-06-10', 'active', 'Seasonal exacerbations', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(4, 3, 'Migraine', '2018-09-05', 'active', 'Stress-triggered', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(5, 4, 'Gastritis', '2022-02-18', 'resolved', 'Resolved with lifestyle changes', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `patient_priority`
--

DROP TABLE IF EXISTS `patient_priority`;
CREATE TABLE IF NOT EXISTS `patient_priority` (
  `priority_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `priority_level` enum('low','medium','high','critical') NOT NULL,
  `urgency_reason` text,
  `assigned_by` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`priority_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_priority` (`priority_level`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_priority`
--

INSERT INTO `patient_priority` (`priority_id`, `patient_id`, `priority_level`, `urgency_reason`, `assigned_by`, `assigned_at`, `updated_at`) VALUES
(1, 1, 'medium', 'Chronic conditions requiring regular monitoring', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(2, 2, 'high', 'Asthma with recent exacerbation', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(3, 3, 'low', 'Routine care for chronic migraine', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(4, 4, 'low', 'Resolved condition, follow-up only', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(5, 5, 'medium', 'Requires regular monitoring', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02'),
(6, 6, 'critical', 'New patient with acute symptoms', 2, '2026-10-24 00:42:02', '2026-10-24 00:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `prescription_id` int NOT NULL AUTO_INCREMENT,
  `consultation_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `prescription_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','dispensed','cancelled') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`prescription_id`),
  KEY `consultation_id` (`consultation_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`prescription_id`, `consultation_id`, `patient_id`, `doctor_id`, `prescription_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, '2024-09-20 01:45:00', 'dispensed', 'Monthly maintenance medications', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(2, 2, 2, 2, '2024-09-21 02:30:00', 'pending', 'Emergency asthma medications', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(3, 3, 3, 3, '2024-09-22 06:15:00', 'pending', 'Migraine relief medication', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(4, 4, 4, 2, '2024-09-22 08:45:00', 'dispensed', 'Acid suppression therapy', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(5, 5, 6, 2, '2024-09-22 03:30:00', 'pending', 'Supportive medications for viral infection', '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
CREATE TABLE IF NOT EXISTS `prescription_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `inventory_id` int NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `quantity_required` int NOT NULL,
  `quantity_dispensed` int DEFAULT '0',
  `instructions` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_prescription` (`prescription_id`),
  KEY `idx_inventory` (`inventory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`item_id`, `prescription_id`, `inventory_id`, `medication_name`, `dosage`, `frequency`, `duration`, `quantity_required`, `quantity_dispensed`, `instructions`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Amlodipine 5mg', '5mg', 'Once daily', '30 days', 30, 30, 'Take in the morning with or without food', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(2, 1, 2, 'Metformin 500mg', '500mg', 'Twice daily', '30 days', 60, 60, 'Take with meals', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(3, 2, 4, 'Salbutamol Inhaler', '100mcg/puff', 'As needed', '30 days', 1, 0, 'Use when experiencing asthma symptoms', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(4, 3, 3, 'Sumatriptan 50mg', '50mg', 'As needed', '10 days', 10, 0, 'Take at onset of migraine, max 200mg/day', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(5, 4, 5, 'Omeprazole 20mg', '20mg', 'Once daily', '30 days', 30, 30, 'Take before breakfast', '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(6, 5, 6, 'Paracetamol 500mg', '500mg', 'Every 6 hours as needed', '7 days', 28, 0, 'For fever and pain relief', '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `treatments`
--

DROP TABLE IF EXISTS `treatments`;
CREATE TABLE IF NOT EXISTS `treatments` (
  `treatment_id` int NOT NULL AUTO_INCREMENT,
  `consultation_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `diagnosis_id` int DEFAULT NULL,
  `treatment_name` varchar(255) NOT NULL,
  `description` text,
  `instructions` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','ongoing','completed','discontinued') DEFAULT 'pending',
  `prescribed_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`treatment_id`),
  KEY `consultation_id` (`consultation_id`),
  KEY `diagnosis_id` (`diagnosis_id`),
  KEY `prescribed_by` (`prescribed_by`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `treatments`
--

INSERT INTO `treatments` (`treatment_id`, `consultation_id`, `patient_id`, `diagnosis_id`, `treatment_name`, `description`, `instructions`, `start_date`, `end_date`, `status`, `prescribed_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'Antihypertensive Therapy', 'Blood pressure management with Amlodipine', 'Take 5mg once daily in the morning', '2024-09-20', '2025-09-20', 'ongoing', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(2, 1, 1, 2, 'Diabetes Management', 'Blood sugar control with Metformin', 'Take 500mg twice daily with meals', '2024-09-20', '2025-09-20', 'ongoing', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(3, 2, 2, 3, 'Asthma Management', 'Bronchodilator therapy for asthma control', 'Use inhaler as needed, maximum 4 times daily', '2024-09-21', '2024-12-21', 'ongoing', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(4, 3, 3, 4, 'Migraine Treatment', 'Pain management and prevention', 'Take sumatriptan at onset of symptoms', '2024-09-22', '2024-12-22', 'ongoing', 3, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(5, 4, 4, 5, 'Gastritis Treatment', 'Acid suppression therapy', 'Take omeprazole 20mg before breakfast', '2024-09-22', '2024-10-22', 'completed', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(6, 5, 6, 6, 'Symptomatic Treatment', 'Supportive care for viral infection', 'Rest, hydration, antipyretics as needed', '2024-09-22', '2024-09-29', 'ongoing', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_progress`
--

DROP TABLE IF EXISTS `treatment_progress`;
CREATE TABLE IF NOT EXISTS `treatment_progress` (
  `progress_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `treatment_id` int NOT NULL,
  `progress_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `progress_notes` text NOT NULL,
  `status` enum('improving','stable','worsening','recovered','complications') DEFAULT 'stable',
  `recorded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`progress_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_treatment` (`treatment_id`),
  KEY `idx_date` (`progress_date`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `treatment_progress`
--

INSERT INTO `treatment_progress` (`progress_id`, `patient_id`, `treatment_id`, `progress_date`, `progress_notes`, `status`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-10-24 00:42:18', 'Blood pressure well controlled at 130/85, patient reports good compliance', 'stable', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(2, 1, 2, '2026-10-24 00:42:18', 'Blood sugar levels within target range, diet compliance good', 'improving', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(3, 2, 3, '2026-10-24 00:42:18', 'Patient reports reduced frequency of asthma attacks, inhaler use decreased', 'improving', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(4, 3, 4, '2026-10-24 00:42:18', 'Patient reports migraine frequency reduced from weekly to bi-weekly', 'improving', 3, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(5, 4, 5, '2026-10-24 00:42:18', 'Gastritis symptoms resolved, patient tolerating treatment well', 'recovered', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18'),
(6, 6, 6, '2026-10-24 00:42:18', 'Fever reduced to 37.8°C, patient showing improvement', 'improving', 2, '2026-10-24 00:42:18', '2026-10-24 00:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('doctor','staff','patient') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `email`, `full_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'staff', 'admin@medcare.local', 'System Administrator', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38'),
(2, 'doctor1', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'doctor', 'doctor1@medcare.local', 'Dr. Maria Santos', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38'),
(3, 'doctor2', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'doctor', 'doctor2@medcare.local', 'Dr. Jose Cruz', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38'),
(4, 'staff1', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'staff', 'staff1@medcare.local', 'Juan Dela Cruz', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38'),
(5, 'staff2', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'staff', 'staff2@medcare.local', 'Ana Garcia', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38'),
(6, 'patient1', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'patient', 'patient1@medcare.local', 'Pedro Reyes', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38'),
(7, 'patient2', '$2y$10$iEmxroyoNdrDFuUW8NLGPOxbZ0KyFbn06jSZQMvpuF4C2421lffz6', 'patient', 'patient2@medcare.local', 'Maria Santos', 1, '2026-10-24 00:41:38', '2026-10-24 00:41:38');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `diagnoses`
--
ALTER TABLE `diagnoses`
  ADD CONSTRAINT `diagnoses_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diagnoses_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diagnoses_ibfk_3` FOREIGN KEY (`diagnosed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `healthcare_communication`
--
ALTER TABLE `healthcare_communication`
  ADD CONSTRAINT `healthcare_communication_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `healthcare_communication_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `healthcare_communication_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE SET NULL;

--
-- Constraints for table `health_education`
--
ALTER TABLE `health_education`
  ADD CONSTRAINT `health_education_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_medical_history`
--
ALTER TABLE `patient_medical_history`
  ADD CONSTRAINT `patient_medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_medical_history_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patient_priority`
--
ALTER TABLE `patient_priority`
  ADD CONSTRAINT `patient_priority_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_priority_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`);

--
-- Constraints for table `treatments`
--
ALTER TABLE `treatments`
  ADD CONSTRAINT `treatments_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatments_ibfk_3` FOREIGN KEY (`diagnosis_id`) REFERENCES `diagnoses` (`diagnosis_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatments_ibfk_4` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `treatment_progress`
--
ALTER TABLE `treatment_progress`
  ADD CONSTRAINT `treatment_progress_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_progress_ibfk_2` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`treatment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_progress_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
