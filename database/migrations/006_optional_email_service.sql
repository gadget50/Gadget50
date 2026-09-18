-- Optional email service defaults for fresh and existing installations.
INSERT INTO settings (setting_key, setting_value) VALUES
('email_service_enabled', '0'),
('email_required', '0'),
('email_verification_enabled', '0'),
('password_reset_email_enabled', '0'),
('system_email_notifications_enabled', '0'),
('email_smtp_validated', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Existing installations may already have a NOT NULL email column.
-- Run this statement once before enabling email-optional registration.
ALTER TABLE users MODIFY email VARCHAR(190) NULL;
