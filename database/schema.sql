-- DecorRentalSystem database schema
-- Run this first, then run seed.sql

CREATE DATABASE IF NOT EXISTS decor_rental
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE decor_rental;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS rental_items;
DROP TABLE IF EXISTS rentals;
DROP TABLE IF EXISTS package_items;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(255)  NOT NULL UNIQUE,
  phone         VARCHAR(30)   NULL,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(60) NOT NULL,
  slug  VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Every physical thing we rent out. quantity = how many we own.
CREATE TABLE items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name        VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  color_name  VARCHAR(40)  NOT NULL DEFAULT 'Assorted',
  color_hex   CHAR(7)      NOT NULL DEFAULT '#CCCCCC',
  price       DECIMAL(8,2) NOT NULL,          -- rental price per event, per unit
  quantity    INT NOT NULL DEFAULT 1,         -- units we own for renting
  sale_price  DECIMAL(8,2) NULL,              -- set this to also sell the item
  sale_stock  INT NOT NULL DEFAULT 0,         -- units for sale (separate from rental stock)
  image_url   VARCHAR(500) NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- Ready made bundles for a type of event
CREATE TABLE packages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  event_type  VARCHAR(60)  NOT NULL,
  description TEXT NOT NULL,
  price       DECIMAL(8,2) NOT NULL,
  setup_fee   DECIMAL(8,2) NOT NULL DEFAULT 75.00, -- our price to set up and take down
  season      VARCHAR(40) NULL,                    -- e.g. 'Fall 2026' for seasonal collections
  color_hex   CHAR(7) NOT NULL DEFAULT '#CCCCCC',
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE package_items (
  package_id INT NOT NULL,
  item_id    INT NOT NULL,
  quantity   INT NOT NULL DEFAULT 1,
  PRIMARY KEY (package_id, item_id),
  FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id)    REFERENCES items(id)
) ENGINE=InnoDB;

-- One booking. Pickup is the day before the event, return is the day after.
CREATE TABLE rentals (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  package_id  INT NULL,
  event_date  DATE NOT NULL,
  pickup_date DATE NOT NULL,
  return_date DATE NOT NULL,
  service     ENUM('pickup','setup') NOT NULL DEFAULT 'pickup', -- setup = our team decorates
  address     VARCHAR(255) NULL,                                -- event address for setup
  status      ENUM('pending','confirmed','picked_up','returned','cancelled')
              NOT NULL DEFAULT 'pending',
  subtotal    DECIMAL(10,2) NOT NULL,
  setup_fee   DECIMAL(8,2)  NOT NULL DEFAULT 0,
  discount    DECIMAL(8,2)  NOT NULL DEFAULT 0,                 -- returning customer discount
  total_price DECIMAL(10,2) NOT NULL,
  notes       VARCHAR(500) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(id),
  FOREIGN KEY (package_id) REFERENCES packages(id),
  INDEX idx_dates (pickup_date, return_date),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- Which items (and how many) are in each rental
CREATE TABLE rental_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  rental_id  INT NOT NULL,
  item_id    INT NOT NULL,
  quantity   INT NOT NULL,
  unit_price DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id)   REFERENCES items(id)
) ENGINE=InnoDB;

-- Pieces customers buy to keep
CREATE TABLE orders (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  status      ENUM('pending','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  total_price DECIMAL(10,2) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  item_id    INT NOT NULL,
  quantity   INT NOT NULL,
  unit_price DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id)  REFERENCES items(id)
) ENGINE=InnoDB;
