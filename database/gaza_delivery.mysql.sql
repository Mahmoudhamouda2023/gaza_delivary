-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: gaza_delivery_preview
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_audit_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_staff_id` bigint unsigned DEFAULT NULL,
  `subject_staff_id` bigint unsigned DEFAULT NULL,
  `subject_user_id` bigint unsigned DEFAULT NULL,
  `subject_store_id` bigint unsigned DEFAULT NULL,
  `event` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `account_audit_logs_actor_staff_id_foreign` (`actor_staff_id`),
  KEY `account_audit_logs_subject_staff_id_id_index` (`subject_staff_id`,`id`),
  KEY `account_audit_logs_subject_user_id_id_index` (`subject_user_id`,`id`),
  KEY `account_audit_logs_subject_store_id_id_index` (`subject_store_id`,`id`),
  CONSTRAINT `account_audit_logs_actor_staff_id_foreign` FOREIGN KEY (`actor_staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `account_audit_logs_subject_staff_id_foreign` FOREIGN KEY (`subject_staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `account_audit_logs_subject_store_id_foreign` FOREIGN KEY (`subject_store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `account_audit_logs_subject_user_id_foreign` FOREIGN KEY (`subject_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_audit_logs`
--


--
-- Table structure for table `cache`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--


--
-- Table structure for table `cache_locks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--


--
-- Table structure for table `captain_payouts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `captain_payouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `captain_id` bigint unsigned NOT NULL,
  `amount_minor` bigint unsigned NOT NULL,
  `reference` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `captain_payouts_captain_id_foreign` (`captain_id`),
  KEY `captain_payouts_paid_by_foreign` (`paid_by`),
  CONSTRAINT `captain_payouts_captain_id_foreign` FOREIGN KEY (`captain_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `captain_payouts_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `captain_payouts`
--


--
-- Table structure for table `captain_profiles`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `captain_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned NOT NULL,
  `vehicle_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `captain_profiles_staff_id_unique` (`staff_id`),
  CONSTRAINT `captain_profiles_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `captain_profiles`
--


--
-- Table structure for table `coupon_redemptions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_redemptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_redemptions_order_id_unique` (`order_id`),
  KEY `coupon_redemptions_store_id_foreign` (`store_id`),
  KEY `coupon_redemptions_coupon_id_store_id_index` (`coupon_id`,`store_id`),
  CONSTRAINT `coupon_redemptions_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `coupon_redemptions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `coupon_redemptions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_redemptions`
--


--
-- Table structure for table `coupons`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int unsigned NOT NULL,
  `max_discount_minor` int unsigned DEFAULT NULL,
  `min_fee_minor` int unsigned NOT NULL DEFAULT '0',
  `max_uses` int unsigned NOT NULL DEFAULT '100',
  `per_store_limit` int unsigned NOT NULL DEFAULT '1',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_store_id_foreign` (`store_id`),
  CONSTRAINT `coupons_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--


--
-- Table structure for table `delivery_audits`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `delivery_audits_staff_id_foreign` (`staff_id`),
  KEY `delivery_audits_user_id_foreign` (`user_id`),
  CONSTRAINT `delivery_audits_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `delivery_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_audits`
--


--
-- Table structure for table `delivery_credits`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_credits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `remaining` int unsigned NOT NULL,
  `expires_on` date NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `granted_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_credits_store_id_foreign` (`store_id`),
  KEY `delivery_credits_granted_by_foreign` (`granted_by`),
  CONSTRAINT `delivery_credits_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `delivery_credits_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_credits`
--


--
-- Table structure for table `delivery_packages`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_minor` int unsigned NOT NULL,
  `duration_days` smallint unsigned NOT NULL,
  `included_deliveries` int unsigned NOT NULL DEFAULT '0',
  `discount_bps` smallint unsigned NOT NULL DEFAULT '0',
  `benefits` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_packages`
--


--
-- Table structure for table `expenses`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spent_on` date NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_minor` int unsigned NOT NULL,
  `reference` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `expenses_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--


--
-- Table structure for table `failed_jobs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--


--
-- Table structure for table `financial_entries`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `captain_id` bigint unsigned NOT NULL,
  `outcome` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collected_minor` int unsigned NOT NULL,
  `fee_minor` int unsigned NOT NULL,
  `captain_pay_minor` int unsigned NOT NULL,
  `merchant_net_minor` bigint NOT NULL,
  `remittance_id` bigint unsigned DEFAULT NULL,
  `settlement_id` bigint unsigned DEFAULT NULL,
  `captain_payout_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `merchant_received_minor` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `financial_entries_order_id_unique` (`order_id`),
  KEY `financial_entries_remittance_id_foreign` (`remittance_id`),
  KEY `financial_entries_settlement_id_foreign` (`settlement_id`),
  KEY `financial_entries_captain_payout_id_foreign` (`captain_payout_id`),
  KEY `financial_entries_captain_id_remittance_id_index` (`captain_id`,`remittance_id`),
  KEY `financial_entries_store_id_settlement_id_index` (`store_id`,`settlement_id`),
  CONSTRAINT `financial_entries_captain_id_foreign` FOREIGN KEY (`captain_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `financial_entries_captain_payout_id_foreign` FOREIGN KEY (`captain_payout_id`) REFERENCES `captain_payouts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `financial_entries_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `financial_entries_remittance_id_foreign` FOREIGN KEY (`remittance_id`) REFERENCES `remittances` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `financial_entries_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `financial_entries_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_entries`
--


--
-- Table structure for table `job_batches`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--


--
-- Table structure for table `jobs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--


--
-- Table structure for table `migrations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_10_24_000000_create_permission_tables',1),(4,'2026_10_24_000001_create_delivery_identity_tables',1),(5,'2026_10_24_000002_create_delivery_catalog',1),(6,'2026_10_24_000003_create_delivery_orders',1),(7,'2026_10_24_000004_create_delivery_finance',1),(8,'2026_10_26_000005_add_store_delivery_features',1),(9,'2026_10_26_000006_normalize_local_phone_numbers',1);

--
-- Table structure for table `model_has_permissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--


--
-- Table structure for table `model_has_roles`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--


--
-- Table structure for table `neighborhoods`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `neighborhoods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `neighborhoods_zone_id_name_unique` (`zone_id`,`name`),
  CONSTRAINT `neighborhoods_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `neighborhoods`
--


--
-- Table structure for table `order_events`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_events_order_id_foreign` (`order_id`),
  KEY `order_events_staff_id_foreign` (`staff_id`),
  KEY `order_events_user_id_foreign` (`user_id`),
  CONSTRAINT `order_events_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_events_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_events`
--


--
-- Table structure for table `order_issues`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `message` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_issues_order_id_foreign` (`order_id`),
  KEY `order_issues_user_id_foreign` (`user_id`),
  KEY `order_issues_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `order_issues_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_issues_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_issues_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_issues`
--


--
-- Table structure for table `order_payments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_minor` int unsigned NOT NULL,
  `account_label` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reported_by` bigint unsigned NOT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_receipt_unique` (`method`,`account_label`,`reference`),
  UNIQUE KEY `order_payments_order_id_unique` (`order_id`),
  KEY `order_payments_reported_by_foreign` (`reported_by`),
  KEY `order_payments_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_payments_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_payments_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_payments`
--


--
-- Table structure for table `orders`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tracking_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `idempotency_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_address_id` bigint unsigned NOT NULL,
  `to_zone_id` bigint unsigned NOT NULL,
  `captain_id` bigint unsigned DEFAULT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `recipient_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_snapshot` varchar(700) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_snapshot` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_payer` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `goods_minor` int unsigned NOT NULL,
  `base_fee_minor` int unsigned NOT NULL,
  `discount_minor` int unsigned NOT NULL,
  `fee_minor` int unsigned NOT NULL,
  `captain_pay_minor` int unsigned NOT NULL,
  `return_fee_minor` int unsigned NOT NULL,
  `captain_return_pay_minor` int unsigned NOT NULL,
  `collect_minor` int unsigned NOT NULL,
  `discount_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_id` bigint unsigned DEFAULT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `delivery_credit_id` bigint unsigned DEFAULT NULL,
  `benefit_reserved` tinyint(1) NOT NULL DEFAULT '0',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `to_neighborhood_id` bigint unsigned DEFAULT NULL,
  `delivery_mode` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'now',
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `captain_pay_mode` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `captain_share_bps` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_store_id_idempotency_key_unique` (`store_id`,`idempotency_key`),
  UNIQUE KEY `orders_public_id_unique` (`public_id`),
  UNIQUE KEY `orders_tracking_token_unique` (`tracking_token`),
  KEY `orders_pickup_address_id_foreign` (`pickup_address_id`),
  KEY `orders_to_zone_id_foreign` (`to_zone_id`),
  KEY `orders_coupon_id_foreign` (`coupon_id`),
  KEY `orders_subscription_id_foreign` (`subscription_id`),
  KEY `orders_delivery_credit_id_foreign` (`delivery_credit_id`),
  KEY `orders_store_id_status_id_index` (`store_id`,`status`,`id`),
  KEY `orders_captain_id_status_id_index` (`captain_id`,`status`,`id`),
  KEY `orders_status_created_at_index` (`status`,`created_at`),
  KEY `orders_to_neighborhood_id_foreign` (`to_neighborhood_id`),
  KEY `orders_scheduled_for_index` (`scheduled_for`),
  CONSTRAINT `orders_captain_id_foreign` FOREIGN KEY (`captain_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_delivery_credit_id_foreign` FOREIGN KEY (`delivery_credit_id`) REFERENCES `delivery_credits` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_pickup_address_id_foreign` FOREIGN KEY (`pickup_address_id`) REFERENCES `pickup_addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_to_neighborhood_id_foreign` FOREIGN KEY (`to_neighborhood_id`) REFERENCES `neighborhoods` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_to_zone_id_foreign` FOREIGN KEY (`to_zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--


--
-- Table structure for table `permissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` VALUES (1,'stores.view','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(2,'stores.review','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(3,'staff.view','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(4,'orders.view','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(5,'orders.assign','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(6,'finance.view','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(7,'finance.remittances.approve','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(8,'finance.settlements.approve','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(9,'pricing.manage','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(10,'orders.view_assigned','staff','2026-10-26 07:45:12','2026-10-26 07:45:12'),(11,'orders.update_assigned','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(12,'remittances.submit_own','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(13,'staff.manage','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(14,'roles.manage','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(15,'audit.view','staff','2026-10-26 07:45:13','2026-10-26 07:45:13');

--
-- Table structure for table `pickup_addresses`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pickup_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned NOT NULL,
  `zone_id` bigint unsigned NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `neighborhood_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pickup_addresses_zone_id_foreign` (`zone_id`),
  KEY `pickup_addresses_store_id_active_index` (`store_id`,`active`),
  KEY `pickup_addresses_neighborhood_id_foreign` (`neighborhood_id`),
  CONSTRAINT `pickup_addresses_neighborhood_id_foreign` FOREIGN KEY (`neighborhood_id`) REFERENCES `neighborhoods` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pickup_addresses_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pickup_addresses_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pickup_addresses`
--


--
-- Table structure for table `remittances`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remittances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `captain_id` bigint unsigned NOT NULL,
  `amount_minor` bigint unsigned NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reference` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remittances_captain_id_foreign` (`captain_id`),
  KEY `remittances_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `remittances_captain_id_foreign` FOREIGN KEY (`captain_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `remittances_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remittances`
--


--
-- Table structure for table `role_has_permissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(1,2),(6,2),(7,2),(8,2),(9,2),(10,3),(11,3),(12,3),(1,4),(2,4),(3,4),(4,4),(5,4),(6,4),(7,4),(8,4),(9,4),(10,4),(11,4),(12,4),(13,4),(14,4),(15,4);

--
-- Table structure for table `roles`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` VALUES (1,'operations','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(2,'finance','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(3,'captain','staff','2026-10-26 07:45:13','2026-10-26 07:45:13'),(4,'super_admin','staff','2026-10-26 07:45:13','2026-10-26 07:45:13');

--
-- Table structure for table `settlements`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned NOT NULL,
  `amount_minor` bigint NOT NULL,
  `reference` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settlements_store_id_foreign` (`store_id`),
  KEY `settlements_paid_by_foreign` (`paid_by`),
  CONSTRAINT `settlements_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `settlements_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settlements`
--


--
-- Table structure for table `staff`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `session_version` int unsigned NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_public_id_unique` (`public_id`),
  UNIQUE KEY `staff_phone_unique` (`phone`),
  KEY `staff_status_id_index` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--


--
-- Table structure for table `store_agreements`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_agreements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned NOT NULL,
  `discount_bps` smallint unsigned NOT NULL DEFAULT '0',
  `starts_on` date NOT NULL,
  `ends_on` date DEFAULT NULL,
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_agreements_store_id_unique` (`store_id`),
  CONSTRAINT `store_agreements_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_agreements`
--


--
-- Table structure for table `stores`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_user_id` bigint unsigned NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','active','suspended','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_staff_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stores_public_id_unique` (`public_id`),
  UNIQUE KEY `stores_owner_user_id_unique` (`owner_user_id`),
  KEY `stores_reviewed_by_staff_id_foreign` (`reviewed_by_staff_id`),
  KEY `stores_status_created_at_id_index` (`status`,`created_at`,`id`),
  CONSTRAINT `stores_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stores_reviewed_by_staff_id_foreign` FOREIGN KEY (`reviewed_by_staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stores`
--


--
-- Table structure for table `subscriptions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned NOT NULL,
  `delivery_package_id` bigint unsigned NOT NULL,
  `package_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_minor` int unsigned NOT NULL,
  `duration_days` smallint unsigned NOT NULL,
  `included_deliveries` int unsigned NOT NULL,
  `remaining_deliveries` int unsigned NOT NULL DEFAULT '0',
  `discount_bps` smallint unsigned NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `payment_reference` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_delivery_package_id_foreign` (`delivery_package_id`),
  KEY `subscriptions_approved_by_foreign` (`approved_by`),
  KEY `subscriptions_store_id_status_expires_at_index` (`store_id`,`status`,`expires_at`),
  CONSTRAINT `subscriptions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `subscriptions_delivery_package_id_foreign` FOREIGN KEY (`delivery_package_id`) REFERENCES `delivery_packages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `subscriptions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--


--
-- Table structure for table `tariffs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tariffs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_zone_id` bigint unsigned NOT NULL,
  `to_zone_id` bigint unsigned NOT NULL,
  `fee_minor` int unsigned NOT NULL,
  `captain_pay_minor` int unsigned NOT NULL,
  `return_fee_minor` int unsigned NOT NULL DEFAULT '0',
  `captain_return_pay_minor` int unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `captain_pay_mode` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `captain_share_bps` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tariffs_from_zone_id_to_zone_id_unique` (`from_zone_id`,`to_zone_id`),
  KEY `tariffs_to_zone_id_foreign` (`to_zone_id`),
  CONSTRAINT `tariffs_from_zone_id_foreign` FOREIGN KEY (`from_zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tariffs_to_zone_id_foreign` FOREIGN KEY (`to_zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tariffs`
--


--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','active','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `session_version` int unsigned NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_public_id_unique` (`public_id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_status_id_index` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--


--
-- Table structure for table `zones`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zones`
--

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
