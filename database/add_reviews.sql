-- Add reviews table and update appointments table
-- Run this to update existing database

USE barbershop;

-- Add new columns to appointments if they don't exist
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS haircut_description TEXT,
ADD COLUMN IF NOT EXISTS location VARCHAR(255);

-- Update status enum to include 'accepted'
ALTER TABLE appointments 
MODIFY COLUMN status ENUM('pending','accepted','completed','cancelled') DEFAULT 'pending';

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB;
