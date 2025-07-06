/*
  # Custom Pricing System Migration

  1. Database Changes
    - Add in_city_price and out_city_price columns to therapists table
    - Add night_fee_enabled column to therapists table
    - Add location and night_fee columns to bookings table
    - Update users table to make email/phone flexible

  2. Pricing Logic
    - In-city vs out-city pricing
    - Night fee calculation (10 PM - 6 AM)
    - Dynamic price calculation based on location and time

  3. User Authentication
    - Flexible email/phone login
    - Optional email/phone during registration
*/

-- Add pricing columns to therapists table
ALTER TABLE therapists 
ADD COLUMN in_city_price DECIMAL(10,2) DEFAULT 0,
ADD COLUMN out_city_price DECIMAL(10,2) DEFAULT 0,
ADD COLUMN night_fee_enabled BOOLEAN DEFAULT TRUE;

-- Update existing therapists to use current price as in_city_price
UPDATE therapists SET in_city_price = price_per_session, out_city_price = price_per_session + 500;

-- Add location and night fee tracking to bookings
ALTER TABLE bookings 
ADD COLUMN user_location VARCHAR(100) DEFAULT 'Delhi',
ADD COLUMN is_night_booking BOOLEAN DEFAULT FALSE,
ADD COLUMN night_fee DECIMAL(10,2) DEFAULT 0,
ADD COLUMN base_amount DECIMAL(10,2) DEFAULT 0;

-- Update users table to make email/phone more flexible
ALTER TABLE users 
MODIFY COLUMN email VARCHAR(100) NULL,
MODIFY COLUMN phone VARCHAR(20) NULL,
ADD CONSTRAINT check_email_or_phone CHECK (email IS NOT NULL OR phone IS NOT NULL);

-- Add index for better performance
CREATE INDEX idx_therapists_pricing ON therapists(in_city_price, out_city_price);
CREATE INDEX idx_bookings_location ON bookings(user_location, is_night_booking);