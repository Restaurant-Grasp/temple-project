-- Complete SaaS Temple Management System Database Schema
-- Including roles, permissions, and audit tables

-- Drop existing tables if needed (be careful in production!)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `login_activities`;
DROP TABLE IF EXISTS `ip_restrictions`;
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(4) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_attempts` int(11) DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `users_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table
CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) DEFAULT '0' COMMENT 'System roles cannot be deleted',
  `status` tinyint(1) DEFAULT '1',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  UNIQUE KEY `code` (`code`),
  KEY `roles_guard_name_index` (`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: module.permission',
  `guard_name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Module grouping',
  `permission` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Permission action',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`),
  KEY `permissions_guard_name_index` (`guard_name`),
  KEY `permissions_module_index` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model has roles (Spatie)
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model has permissions (Spatie)
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role has permissions
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login activities table
CREATE TABLE `login_activities` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_status` enum('success','failed','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'failed',
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `login_activities_user_id_index` (`user_id`),
  KEY `login_activities_ip_address_index` (`ip_address`),
  KEY `login_activities_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP Restrictions table
CREATE TABLE `ip_restrictions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `restrictable_type` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User or Role',
  `restrictable_id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('allow','deny') COLLATE utf8mb4_unicode_ci DEFAULT 'allow',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_restrictions_restrictable_index` (`restrictable_type`,`restrictable_id`),
  KEY `ip_restrictions_ip_address_index` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permissions
INSERT INTO `permissions` (`name`, `module`, `permission`, `description`) VALUES
-- Dashboard
('dashboard.view', 'dashboard', 'view', 'View dashboard and statistics'),

-- Temples (formerly Tenants)
('temples.create', 'temples', 'create', 'Create new temples'),
('temples.view', 'temples', 'view', 'View temple list and details'),
('temples.update', 'temples', 'update', 'Update temple information'),
('temples.delete', 'temples', 'delete', 'Delete temples'),
('temples.suspend', 'temples', 'suspend', 'Suspend/activate temple access'),

-- Billing
('billing.view', 'billing', 'view', 'View billing information'),
('billing.update', 'billing', 'update', 'Update billing details'),
('billing.refund', 'billing', 'refund', 'Process refunds'),

-- Users
('users.create', 'users', 'create', 'Create new users'),
('users.view', 'users', 'view', 'View user list and details'),
('users.update', 'users', 'update', 'Update user information'),
('users.delete', 'users', 'delete', 'Delete users'),

-- Roles
('roles.create', 'roles', 'create', 'Create new roles'),
('roles.view', 'roles', 'view', 'View roles and permissions'),
('roles.update', 'roles', 'update', 'Update roles and assign permissions'),
('roles.delete', 'roles', 'delete', 'Delete roles'),

-- Reports
('reports.view', 'reports', 'view', 'View reports'),
('reports.export', 'reports', 'export', 'Export reports'),

-- Settings
('settings.view', 'settings', 'view', 'View system settings'),
('settings.update', 'settings', 'update', 'Update system settings'),

-- Audit Logs
('audit_logs.view', 'audit_logs', 'view', 'View audit logs and login activities'),

-- IP Restrictions
('ip_restrictions.create', 'ip_restrictions', 'create', 'Create IP restrictions'),
('ip_restrictions.view', 'ip_restrictions', 'view', 'View IP restrictions'),
('ip_restrictions.update', 'ip_restrictions', 'update', 'Update IP restrictions'),
('ip_restrictions.delete', 'ip_restrictions', 'delete', 'Delete IP restrictions');

-- Insert default roles
INSERT INTO `roles` (`name`, `code`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', 1),
('Admin', 'admin', 'Administrative access with most permissions', 1),
('Manager', 'manager', 'Management access for temples and billing', 1),
('Support', 'support', 'Support staff with limited access', 1);

-- Assign all permissions to Super Admin
INSERT INTO `role_has_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Assign permissions to Admin (all except some critical ones)
INSERT INTO `role_has_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` 
WHERE `name` NOT IN ('roles.delete', 'settings.update');

-- Assign permissions to Manager
INSERT INTO `role_has_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions` 
WHERE `name` IN (
    'dashboard.view',
    'temples.create', 'temples.view', 'temples.update', 'temples.suspend',
    'billing.view', 'billing.update',
    'users.view',
    'reports.view', 'reports.export'
);

-- Assign permissions to Support
INSERT INTO `role_has_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions` 
WHERE `name` IN (
    'dashboard.view',
    'temples.view',
    'billing.view',
    'users.view',
    'audit_logs.view'
);

-- Temple Management System Additional Tables
-- Run this after your existing schema

-- Countries table
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO code (IN, US, etc.)',
  `phone_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '+91, +1, etc.',
  `currency_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_currency_id` (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Currencies table
CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'USD, INR, etc.',
  `symbol` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '$, ₹, etc.',
  `decimal_places` tinyint(1) DEFAULT '2',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temples table (main entity)
CREATE TABLE `temples` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: TMP0000001',
  `temple_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to logo file',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  
  -- Location fields
  `country_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address1` text COLLATE utf8mb4_unicode_ci,
  `address2` text COLLATE utf8mb4_unicode_ci,
  
  -- Billing configuration
  `billing_start_date` date NOT NULL,
  `billing_type` enum('onetime','monthly','annually') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `billing_reminder_days` int(11) DEFAULT '7' COMMENT 'Days before due date',
  `due_reminder_days` int(11) DEFAULT '3' COMMENT 'Days after due date',
  `auto_disable_grace_days` int(11) DEFAULT '15' COMMENT 'Grace period before disabling',
  
  -- System fields
  `domain_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Can be subdomain or custom domain',
  `decimal_places` tinyint(1) DEFAULT '2' COMMENT 'For amount formatting',
  
  -- Customization
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#667eea',
  `secondary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#764ba2',
  `background_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#f4f6f9',
  `text_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#333333',
  
  -- API Configuration
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_secret` text COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted',
  `api_enabled` tinyint(1) DEFAULT '0',
  `api_rate_limit` int(11) DEFAULT '1000' COMMENT 'Requests per hour',
  `webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  
  -- Limits
  `max_users` int(11) DEFAULT '10',
  
  -- Database connection (encrypted)
  `db_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'localhost',
  `db_port` int(11) DEFAULT '3306',
  `db_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_password` text COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted',
  
  -- Status and control
  `status` enum('active','suspended','expired','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `maintenance_mode` tinyint(1) DEFAULT '0',
  `suspended_at` datetime DEFAULT NULL,
  `suspended_reason` text COLLATE utf8mb4_unicode_ci,
  `expires_at` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  
  -- Tracking
  `last_accessed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `temple_code` (`temple_code`),
  UNIQUE KEY `domain_name` (`domain_name`),
  KEY `idx_status` (`status`),
  KEY `idx_country_id` (`country_id`),
  KEY `idx_currency_id` (`currency_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `temples_deleted_at_index` (`deleted_at`),
  CONSTRAINT `fk_temples_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `fk_temples_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  CONSTRAINT `fk_temples_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temple contacts table
CREATE TABLE `temple_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'President, Secretary, etc.',
  `is_primary` tinyint(1) DEFAULT '0',
  `is_billing_contact` tinyint(1) DEFAULT '0',
  `is_technical_contact` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_temple_id` (`temple_id`),
  CONSTRAINT `fk_temple_contacts_temple` FOREIGN KEY (`temple_id`) REFERENCES `temples` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temple billing history
CREATE TABLE `temple_billings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_id` bigint(20) UNSIGNED NOT NULL,
  `billing_period_start` date NOT NULL,
  `billing_period_end` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','paid','overdue','waived') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `due_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_temple_billing` (`temple_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  CONSTRAINT `fk_temple_billings_temple` FOREIGN KEY (`temple_id`) REFERENCES `temples` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temple status history
CREATE TABLE `temple_status_histories` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_id` bigint(20) UNSIGNED NOT NULL,
  `old_status` enum('active','suspended','expired','pending') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` enum('active','suspended','expired','pending') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_temple_id` (`temple_id`),
  KEY `idx_changed_by` (`changed_by`),
  CONSTRAINT `fk_temple_status_temple` FOREIGN KEY (`temple_id`) REFERENCES `temples` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_temple_status_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temple API logs
CREATE TABLE `temple_api_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_id` bigint(20) UNSIGNED NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_body` text COLLATE utf8mb4_unicode_ci,
  `response_code` int(11) DEFAULT NULL,
  `response_body` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_temple_api` (`temple_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_temple_api_logs_temple` FOREIGN KEY (`temple_id`) REFERENCES `temples` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temple notifications
CREATE TABLE `temple_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('billing_reminder','due_reminder','suspension_warning','system_update','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('email','sms','both') COLLATE utf8mb4_unicode_ci DEFAULT 'email',
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_temple_notification` (`temple_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_temple_notifications_temple` FOREIGN KEY (`temple_id`) REFERENCES `temples` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default data
-- Sample currencies
INSERT INTO `currencies` (`name`, `code`, `symbol`, `decimal_places`, `is_active`) VALUES
('US Dollar', 'USD', '$', 2, 1),
('Indian Rupee', 'INR', '₹', 2, 1),
('Euro', 'EUR', '€', 2, 1),
('British Pound', 'GBP', '£', 2, 1),
('Canadian Dollar', 'CAD', 'C$', 2, 1),
('Australian Dollar', 'AUD', 'A$', 2, 1),
('Singapore Dollar', 'SGD', 'S$', 2, 1),
('Malaysian Ringgit', 'MYR', 'RM', 2, 1);

-- Sample countries with currency mapping
INSERT INTO `countries` (`name`, `code`, `phone_code`, `currency_id`, `is_active`) VALUES
('United States', 'US', '+1', 1, 1),
('India', 'IN', '+91', 2, 1),
('United Kingdom', 'GB', '+44', 4, 1),
('Canada', 'CA', '+1', 5, 1),
('Australia', 'AU', '+61', 6, 1),
('Singapore', 'SG', '+65', 7, 1),
('Malaysia', 'MY', '+60', 8, 1),
('Germany', 'DE', '+49', 3, 1),
('France', 'FR', '+33', 3, 1),
('Italy', 'IT', '+39', 3, 1);

-- Insert temple-related permissions
INSERT INTO `permissions` (`name`, `module`, `permission`, `description`) VALUES
-- Temples
('temples.create', 'temples', 'create', 'Create new temples'),
('temples.view', 'temples', 'view', 'View temple list and details'),
('temples.update', 'temples', 'update', 'Update temple information'),
('temples.delete', 'temples', 'delete', 'Delete temples'),
('temples.suspend', 'temples', 'suspend', 'Suspend/activate temple access'),
('temples.manage_api', 'temples', 'manage_api', 'Manage temple API keys'),
('temples.view_logs', 'temples', 'view_logs', 'View temple activity and API logs'),

-- Temple Billing
('temple_billing.view', 'temple_billing', 'view', 'View temple billing information'),
('temple_billing.create', 'temple_billing', 'create', 'Create billing records'),
('temple_billing.update', 'temple_billing', 'update', 'Update billing status'),

-- Temple Notifications
('temple_notifications.view', 'temple_notifications', 'view', 'View temple notifications'),
('temple_notifications.send', 'temple_notifications', 'send', 'Send notifications to temples');

-- Assign new permissions to Super Admin role
INSERT INTO `role_has_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions` 
WHERE `module` IN ('temples', 'temple_billing', 'temple_notifications');


-- Add amount fields to temples table
ALTER TABLE `temples` 
ADD COLUMN `billing_amount` DECIMAL(10,2) DEFAULT NULL AFTER `billing_type`,
ADD COLUMN `billing_description` TEXT DEFAULT NULL AFTER `billing_amount`;

-- Add index for billing cron job efficiency
ALTER TABLE `temple_billings` 
ADD INDEX `idx_billing_cron` (`status`, `billing_period_end`);

-- Create billing_templates table for recurring billing settings
CREATE TABLE IF NOT EXISTS `billing_templates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `temple_id` bigint(20) UNSIGNED NOT NULL,
  `billing_type` enum('monthly','annually') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_generated_date` date DEFAULT NULL,
  `next_generation_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_temple_billing_template` (`temple_id`),
  KEY `idx_next_generation` (`next_generation_date`, `is_active`),
  CONSTRAINT `fk_billing_templates_temple` FOREIGN KEY (`temple_id`) REFERENCES `temples` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create billing_payments table to track payments
CREATE TABLE IF NOT EXISTS `billing_payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `billing_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_billing_payment` (`billing_id`),
  KEY `idx_transaction` (`transaction_id`),
  CONSTRAINT `fk_billing_payments_billing` FOREIGN KEY (`billing_id`) REFERENCES `temple_billings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add more fields to temple_billings for better tracking
ALTER TABLE `temple_billings` 
ADD COLUMN `invoice_number` VARCHAR(50) DEFAULT NULL AFTER `id`,
ADD COLUMN `description` TEXT DEFAULT NULL AFTER `currency_code`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `paid_at`,
ADD COLUMN `transaction_id` VARCHAR(100) DEFAULT NULL AFTER `payment_method`,
ADD INDEX `idx_invoice_number` (`invoice_number`);

-- Create a trigger to generate invoice numbers
DELIMITER $$
CREATE TRIGGER `generate_invoice_number` BEFORE INSERT ON `temple_billings`
FOR EACH ROW
BEGIN
    DECLARE invoice_prefix VARCHAR(10);
    DECLARE invoice_count INT;
    
    SET invoice_prefix = CONCAT('INV', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'));
    
    SELECT COUNT(*) + 1 INTO invoice_count 
    FROM temple_billings 
    WHERE invoice_number LIKE CONCAT(invoice_prefix, '%');
    
    SET NEW.invoice_number = CONCAT(invoice_prefix, LPAD(invoice_count, 5, '0'));
END$$
DELIMITER ;