-- Sample data so the site has something to show
-- Prices checked against Los Angeles rental companies in Oct 2026 and set to
-- stay below budget local shops. Packages cost about 10 to 15% less than
-- renting each piece on its own.
USE decor_rental;

-- Logins (both use the password ChangeMe123!)
--   admin@zerowaste.test  -> admin dashboard
--   demo@zerowaste.test   -> returning customer with past rentals (gets the loyalty discount)
-- Change these passwords before you put the site online.
INSERT INTO users (id, name, email, phone, password_hash, role) VALUES
(1, 'Site Admin', 'admin@zerowaste.test', NULL,
 '$2y$10$V7tz9N.Fgv0W4y2julyrEuMrRZVlW5leVF4IaSCvhuxFqxzZoYK.q', 'admin'),
(2, 'Demo Customer', 'demo@zerowaste.test', '818-555-0142',
 '$2y$10$V7tz9N.Fgv0W4y2julyrEuMrRZVlW5leVF4IaSCvhuxFqxzZoYK.q', 'customer');

INSERT INTO categories (id, name, slug) VALUES
(1, 'Backdrops',       'backdrops'),
(2, 'Centerpieces',    'centerpieces'),
(3, 'Linens',          'linens'),
(4, 'Lighting',        'lighting'),
(5, 'Signs and stands','signs'),
(6, 'Arches',          'arches');

INSERT INTO items (id, category_id, name, description, color_name, color_hex, price, quantity) VALUES
(1, 1, 'Gold sequin backdrop',      '8 by 8 ft shimmer wall on a steel frame. Great behind a head table or photo spot.', 'Gold', '#C9A227', 85.00, 3),
(2, 1, 'Blush flower wall',         '8 by 8 ft silk flower panel wall. Panels clip together, frame included.',           'Blush', '#E8A9B5', 225.00, 2),
(3, 1, 'Ivory drape backdrop',      'Sheer chiffon drapes with a 10 ft adjustable stand.',                                'Ivory', '#EFE9DA', 95.00, 4),
(4, 2, 'Gold mercury glass vase',   'Tall 16 in vase. Fits faux florals or candles.',                                     'Gold', '#B8962E', 6.00, 40),
(5, 2, 'Faux peony centerpiece',    'Low round arrangement of silk peonies and greenery.',                                'Blush', '#DFA0AE', 18.00, 30),
(6, 2, 'Glass candle holder set',   'Set of three clear glass cylinders with LED candles.',                               'Clear', '#D8E3E8', 5.00, 50),
(7, 3, 'White round tablecloth',    '120 in round polyester cloth. Fits a 60 in round table to the floor.',               'White', '#F4F4F2', 12.00, 60),
(8, 3, 'Navy table runner',         '12 by 108 in satin runner.',                                                         'Navy', '#1F2C56', 4.00, 40),
(9, 3, 'Gold charger plate',        '13 in acrylic charger. Sold per plate.',                                             'Gold', '#C7A23A', 1.00, 120),
(10, 4, 'Warm string lights',       '50 ft strand of warm white bulbs. Indoor or outdoor.',                              'Warm white', '#F6D88A', 15.00, 10),
(11, 4, 'Marquee letters',          '3 ft light up letters. Rent per letter.',                                            'White', '#F2F0EA', 45.00, 26),
(12, 5, 'Welcome sign easel',       'Wooden easel with a blank acrylic sign. Add your own lettering.',                   'Natural wood', '#B38B5D', 12.00, 6),
(13, 5, 'Gold cake stand',          '12 in round metal stand.',                                                           'Gold', '#BF9B30', 10.00, 8),
(14, 6, 'Round balloon arch frame', '7 ft round metal hoop. Balloons not included.',                                     'Black', '#2B2B2B', 30.00, 4),
(15, 5, 'Red white and blue banner','Pleated fan bunting, set of 5.',                                                     'Red, white, blue', '#B22234', 10.00, 12);

INSERT INTO packages (id, name, event_type, description, price, setup_fee, season, color_hex) VALUES
(1, 'Quince glow',      'Quinceañera',
 'Gold sequin backdrop, marquee letters for her name, and gold centerpieces for 10 tables.', 495.00, 150.00, NULL, '#C9A227'),
(2, 'Garden wedding',   'Wedding',
 'Blush flower wall, peony centerpieces, white linens, and string lights for 10 tables.', 499.00, 175.00, NULL, '#E8A9B5'),
(3, 'Birthday basics',  'Birthday',
 'Balloon arch frame, welcome sign, cake stand, and candles for 5 tables.', 69.00, 60.00, NULL, '#2B2B2B'),
(4, 'Backyard Fourth',  'Fourth of July',
 'Red white and blue bunting, navy runners, and string lights.', 59.00, 60.00, NULL, '#B22234'),
(5, 'Harvest table',    'Fall gathering',
 'Ivory drapes, warm string lights, gold vases, and white linens for a fall dinner or Friendsgiving.', 199.00, 90.00, 'Fall 2026', '#C9813A');

INSERT INTO package_items (package_id, item_id, quantity) VALUES
(1, 1, 1), (1, 11, 5), (1, 4, 10), (1, 7, 10), (1, 9, 80),
(2, 2, 1), (2, 5, 10), (2, 7, 10), (2, 10, 3),
(3, 14, 1), (3, 12, 1), (3, 13, 1), (3, 6, 5),
(4, 15, 2), (4, 8, 4), (4, 10, 2),
(5, 3, 1), (5, 10, 2), (5, 4, 6), (5, 7, 6);

-- Pieces we also sell, for people who want to keep them
-- (sale stock is separate from the rental stock)
UPDATE items SET sale_price = 12.00, sale_stock = 20  WHERE id = 6;   -- glass candle holder set
UPDATE items SET sale_price = 4.00,  sale_stock = 100 WHERE id = 9;   -- gold charger plate
UPDATE items SET sale_price = 35.00, sale_stock = 3   WHERE id = 12;  -- welcome sign easel
UPDATE items SET sale_price = 18.00, sale_stock = 10  WHERE id = 15;  -- red white and blue banner
UPDATE items SET sale_price = 40.00, sale_stock = 8   WHERE id = 5;   -- faux peony centerpiece

-- Past rentals for the demo customer, so the reuse counter and loyalty discount have data
INSERT INTO rentals (id, user_id, package_id, event_date, pickup_date, return_date, service, status, subtotal, total_price) VALUES
(1, 2, 3, '2026-08-15', '2026-08-14', '2026-08-16', 'pickup', 'returned', 95.00, 95.00),
(2, 2, NULL, '2026-09-12', '2026-09-11', '2026-09-13', 'pickup', 'returned', 270.00, 270.00);

INSERT INTO rental_items (rental_id, item_id, quantity, unit_price) VALUES
(1, 14, 1, 0), (1, 12, 1, 0), (1, 13, 1, 0), (1, 6, 5, 0),
(2, 7, 10, 9.00), (2, 5, 10, 18.00);
