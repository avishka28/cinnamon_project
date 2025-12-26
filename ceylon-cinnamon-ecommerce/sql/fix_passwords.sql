-- ============================================================================
-- Fix User Passwords
-- ============================================================================
-- This script updates all user passwords to 'password' for testing
-- The hash below is for the password: password
-- ============================================================================

UPDATE `users` SET `password_hash` = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Verify the update
SELECT id, email, first_name, role FROM users;

-- ============================================================================
-- NEW LOGIN CREDENTIALS:
-- Email: admin@ceyloncinnamon.com
-- Password: password
-- ============================================================================