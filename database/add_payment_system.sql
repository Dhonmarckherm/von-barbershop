-- Payment System Migration
-- Adds payment tracking to appointments table
-- Run this AFTER the main schema.sql

USE barbershop;

-- Add payment columns to appointments table
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','verified','rejected') DEFAULT 'pending' COMMENT 'Payment verification status',
ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) DEFAULT NULL COMMENT 'Filename of payment screenshot',
ADD COLUMN IF NOT EXISTS downpayment_amount DECIMAL(10,2) DEFAULT 50.00 COMMENT 'Downpayment amount (₱50)',
ADD COLUMN IF NOT EXISTS balance_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Remaining balance to pay at shop',
ADD COLUMN IF NOT EXISTS payment_verified_at TIMESTAMP NULL COMMENT 'When payment was verified';

-- Create payment_logs table for tracking payment history
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'GCash',
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    proof_filename VARCHAR(255) DEFAULT NULL,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    verified_by INT DEFAULT NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert sample data (if needed)
-- UPDATE appointments SET downpayment_amount = 50.00 WHERE downpayment_amount IS NULL;
