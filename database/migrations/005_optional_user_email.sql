-- Existing-install migration: email is optional while Email Service is disabled.
-- MySQL permits multiple NULL values in a UNIQUE column, so users without email
-- can coexist while preserving uniqueness for supplied email addresses.
ALTER TABLE users MODIFY email VARCHAR(190) NULL;
