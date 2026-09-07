-- ============================================================================
-- EventHive schema
-- ============================================================================
-- DESIGN DECISION - tiers vs. seating (read this before touching seat_select.php
-- or payment.php):
--
-- Each event now supports 1-2 pricing TIERS (ticket_tiers table below) instead
-- of a single flat events.ticket_price. A buyer picks a tier first (skipped
-- automatically when an event only has one), then either a quantity (general
-- admission) or exact seats (assigned seating).
--
-- For events with assigned seating, we deliberately kept the seat MAP
-- tier-agnostic: the `seats` table is still keyed only by event_id, exactly
-- as in the original demo, not by tier_id. A buyer picks a tier, then picks
-- any open seat on the shared map - the seat itself doesn't encode "this row
-- is VIP". We considered the alternative (front rows = VIP seats, back rows
-- = Regular, i.e. adding seats.tier_id) but rejected it for this project:
-- it would mean allocating rows to tiers at creation time, teaching the seat
-- picker to filter/color by the buyer's chosen tier, and reconciling
-- "how many VIP seats are left" against ticket_tiers.total_tickets in two
-- places at once (the tier's row count AND the tier's ticket count). That's
-- a real feature but it roughly doubles the surface area of seat_select.php
-- and payment.php for a class assignment, and the two counts can drift out
-- of sync if an admin ever edits a tier. Keeping seats tier-agnostic means
-- ticket_tiers.total_tickets/tickets_sold is the ONLY inventory count for a
-- tier (general-admission tiers subtract from it directly; seated tiers
-- subtract from it too, independently of which physical seat was picked),
-- and the seat map only tracks "is this physical seat free", which is the
-- simpler design to implement correctly and reason about.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS eventhive_db;
USE eventhive_db;

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

-- `category` is a light free-text tag (Music / Sports / Workshops / Talks /
-- Social, ...) used for filtering on the browse page - deliberately not a
-- separate categories table since the list is short and never needs its own
-- CRUD for this project.
--
-- Pricing no longer lives here - see ticket_tiers below. An event's total
-- capacity/remaining count is the SUM of its tiers' total_tickets/tickets_sold.
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(150) NOT NULL,
  event_date DATE NOT NULL,
  venue VARCHAR(150) NOT NULL,
  category VARCHAR(50) NOT NULL DEFAULT 'Social',
  image_url VARCHAR(500) NULL,
  has_seating TINYINT(1) NOT NULL DEFAULT 0,
  seat_rows INT NULL,
  seats_per_row INT NULL
);

INSERT INTO events (event_name, event_date, venue, category, image_url) VALUES
('Cultural Night 2026', '2026-09-12', 'Dewan Tunku Canselor', 'Social', '/uploads/sample-cultural-night.jpg'),
('Battle of the Bands', '2026-10-03', 'Open Air Theatre', 'Music', '/uploads/sample-battle-of-bands.jpg'),
('Charity Gala Dinner', '2026-11-20', 'Grand Hall', 'Social', '/uploads/sample-gala-dinner.jpg'),
('Jazz Night Live', '2026-09-05', 'Campus Amphitheatre', 'Music', '/uploads/sample-jazz-night.jpg'),
('The Tempest - Drama Night', '2026-10-18', 'TARUMT Auditorium', 'Talks', '/uploads/sample-drama-night.jpg'),
('Freshman Welcome Carnival', '2026-08-22', 'Campus Field', 'Social', '/uploads/sample-welcome-carnival.jpg');

-- One or more pricing tiers per event. Every event needs at least one row
-- here (most events: a single "Regular" tier, functionally identical to the
-- original demo's flat price). A handful of seed events get a second "VIP"
-- tier with its own price, its own slice of the inventory, and an optional
-- perks description. tickets_sold is maintained the same way the old
-- events.tickets_sold was: incremented on purchase, decremented on
-- cancel/edit, all inside the same transaction as the orders/tickets writes.
CREATE TABLE ticket_tiers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  tier_name VARCHAR(50) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  total_tickets INT NOT NULL,
  tickets_sold INT NOT NULL DEFAULT 0,
  perks VARCHAR(255) NULL,
  FOREIGN KEY (event_id) REFERENCES events(id)
);

INSERT INTO ticket_tiers (event_id, tier_name, price, total_tickets, perks) VALUES
((SELECT id FROM events WHERE event_name = 'Cultural Night 2026'), 'Regular', 15.00, 200, NULL),
((SELECT id FROM events WHERE event_name = 'Battle of the Bands'), 'Regular', 20.00, 150, NULL),
((SELECT id FROM events WHERE event_name = 'Freshman Welcome Carnival'), 'Regular', 5.00, 300, NULL);

-- Two-tier seed events: Regular + VIP at different prices with perks.
INSERT INTO ticket_tiers (event_id, tier_name, price, total_tickets, perks) VALUES
((SELECT id FROM events WHERE event_name = 'Charity Gala Dinner'), 'Regular', 50.00, 70, NULL),
((SELECT id FROM events WHERE event_name = 'Charity Gala Dinner'), 'VIP', 90.00, 30, 'Front-row table, welcome drink, and a keepsake gift.'),
((SELECT id FROM events WHERE event_name = 'Jazz Night Live'), 'Regular', 30.00, 65, NULL),
((SELECT id FROM events WHERE event_name = 'Jazz Night Live'), 'VIP', 55.00, 25, 'Front-row seating and a free drink.'),
((SELECT id FROM events WHERE event_name = 'The Tempest - Drama Night'), 'Regular', 20.00, 70, NULL),
((SELECT id FROM events WHERE event_name = 'The Tempest - Drama Night'), 'VIP', 38.00, 26, 'Best-view seating and a meet-the-cast pass.');

-- Seed announcement so the homepage banner and Announcements page have
-- something to show out of the box.
INSERT INTO announcements (title, body, posted_by) VALUES
('Welcome to EventHive', 'Browse events, pick your tier, and grab your ticket before it sells out. Check back here for updates on event changes and new releases.', (SELECT id FROM users WHERE email = 'admin@example.com'));

-- `orders` is kept as the underlying table name (simplicity per the design
-- brief), but every user-facing label calls these "My Tickets" /
-- "reservations" now, never "orders" - see index.php, confirmation.php, etc.
-- tier_id replaces the old reliance on events.ticket_price: the price used
-- for total_price is always the tier's price at the time of purchase.
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  tier_id INT NOT NULL,
  quantity INT NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (event_id) REFERENCES events(id),
  FOREIGN KEY (tier_id) REFERENCES ticket_tiers(id)
);

-- One row per selectable seat, only populated for events with has_seating = 1.
-- Deliberately tier-agnostic - see the design note at the top of this file.
CREATE TABLE seats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  row_label VARCHAR(5) NOT NULL,
  seat_number INT NOT NULL,
  is_booked TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (event_id) REFERENCES events(id),
  UNIQUE KEY unique_event_seat (event_id, row_label, seat_number)
);

-- Seed events with assigned seating: row letters A, B, C... x seats per row.
-- Seat map size matches each event's total capacity across both its tiers
-- (Regular + VIP combined), since seats are shared across tiers.
UPDATE events SET has_seating = 1, seat_rows = 5, seats_per_row = 20 WHERE event_name = 'Charity Gala Dinner';
UPDATE events SET has_seating = 1, seat_rows = 6, seats_per_row = 15 WHERE event_name = 'Jazz Night Live';
UPDATE events SET has_seating = 1, seat_rows = 8, seats_per_row = 12 WHERE event_name = 'The Tempest - Drama Night';

INSERT INTO seats (event_id, row_label, seat_number)
WITH RECURSIVE seq AS (
  SELECT 0 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 99
)
SELECT (SELECT id FROM events WHERE event_name = 'Charity Gala Dinner'), CHAR(65 + FLOOR(n / 20)), MOD(n, 20) + 1
FROM seq;

INSERT INTO seats (event_id, row_label, seat_number)
WITH RECURSIVE seq AS (
  SELECT 0 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 89
)
SELECT (SELECT id FROM events WHERE event_name = 'Jazz Night Live'), CHAR(65 + FLOOR(n / 15)), MOD(n, 15) + 1
FROM seq;

INSERT INTO seats (event_id, row_label, seat_number)
WITH RECURSIVE seq AS (
  SELECT 0 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 95
)
SELECT (SELECT id FROM events WHERE event_name = 'The Tempest - Drama Night'), CHAR(65 + FLOOR(n / 12)), MOD(n, 12) + 1
FROM seq;

-- One row per individual ticket (one per unit purchased, whether seated or
-- general admission), each with its own unique QR check-in token. The token
-- is a random opaque string, not the attendee's personal info - the scanner
-- looks up attendee/event/seat details server-side from this token.
CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  seat_id INT NULL,
  qr_token VARCHAR(64) NOT NULL UNIQUE,
  checked_in_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (seat_id) REFERENCES seats(id)
);

CREATE TABLE testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  comment TEXT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (event_id) REFERENCES events(id)
);

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin-posted announcements shown to all users (e.g. "Cultural Night moved to
-- Hall B", "Ticket sales close Friday"). Ordered newest-first everywhere they
-- appear; the homepage banner shows only the single most recent one.
CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  body TEXT NOT NULL,
  posted_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id)
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
