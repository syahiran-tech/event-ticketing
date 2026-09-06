CREATE DATABASE IF NOT EXISTS campushop_db;
USE campushop_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  id_number VARCHAR(20) NULL,
  faculty VARCHAR(150) NULL,
  date_of_birth DATE NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed admin account: admin@example.com / admin123
-- Change this password immediately in any real deployment.
INSERT INTO users (name, email, password_hash, is_admin) VALUES
('Admin', 'admin@example.com', '$2y$10$HI3gLmyD4OGmfNLAGUIL8.eBhhKu5nzL7wTDws.6mUNO9V44kyM5q', 1);

-- One row per scheduled shuttle trip (a single route + date + departure time).
-- Booking is quantity-based only - there is no seat map/seat picker.
CREATE TABLE trips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  route_name VARCHAR(150) NOT NULL,
  trip_date DATE NOT NULL,
  departure_time TIME NOT NULL,
  pickup_point VARCHAR(150) NOT NULL,
  dropoff_point VARCHAR(150) NOT NULL,
  capacity INT NOT NULL,
  seats_booked INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500) NULL
);

-- image_url is left NULL for the seed trips - entity_image_url() in
-- helpers.php falls back to a neutral placeholder graphic until an admin
-- uploads a real route/bus photo through admin/trip_create.php or
-- admin/trip_edit.php.
INSERT INTO trips (route_name, trip_date, departure_time, pickup_point, dropoff_point, capacity, seats_booked, image_url) VALUES
('Main Campus -> LRT Station', '2026-09-12', '08:30:00', 'Main Campus Bus Bay', 'LRT Station Main Entrance', 40, 0, NULL),
('Main Campus -> City Mall', '2026-10-03', '17:45:00', 'Main Campus Bus Bay', 'City Mall Drop-Off Zone', 30, 0, NULL),
('Hostel Block -> Main Campus', '2026-11-20', '07:15:00', 'Hostel Block C Lobby', 'Main Campus Bus Bay', 25, 0, NULL),
('Main Campus -> Train Terminal', '2026-09-05', '16:00:00', 'Main Campus Bus Bay', 'Train Terminal Concourse', 35, 0, NULL),
('Main Campus -> LRT Station', '2026-10-18', '18:30:00', 'Main Campus Bus Bay', 'LRT Station Main Entrance', 40, 0, NULL),
('Hostel Block -> City Mall', '2026-08-22', '10:00:00', 'Hostel Block C Lobby', 'City Mall Drop-Off Zone', 20, 0, NULL);

-- One row per individual seat booked (whether part of a group booking or a
-- single seat), each with its own unique QR check-in token used as a boarding
-- pass. The token is a random opaque string, not the passenger's personal
-- info - the check-in scanner looks up passenger/trip details server-side
-- from this token.
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  trip_id INT NOT NULL,
  quantity INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (trip_id) REFERENCES trips(id)
);

CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  qr_token VARCHAR(64) NOT NULL UNIQUE,
  checked_in_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  trip_id INT NOT NULL,
  comment TEXT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (trip_id) REFERENCES trips(id)
);

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PHP sessions are stored here instead of on local disk, so that any EC2
-- instance behind an ALB/ASG can read a session written by a different
-- instance. See auth.php's DbSessionHandler.
CREATE TABLE sessions (
  id VARCHAR(128) PRIMARY KEY,
  data MEDIUMTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX idx_last_activity (last_activity)
);
