-- Decor Home Store — schema
-- Runs automatically on first MySQL container init (alphabetical order in /docker-entrypoint-initdb.d).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    phone         VARCHAR(30) NULL,
    address       VARCHAR(255) NULL,
    role          ENUM('admin', 'moderator', 'user') NOT NULL DEFAULT 'user',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    description TEXT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    name        VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL,
    description TEXT NULL,
    price       DECIMAL(10,2) NOT NULL,
    image_path  VARCHAR(255) NULL,
    stock       INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category_active (category_id, is_active),
    KEY idx_products_featured_active (is_featured, is_active),
    FULLTEXT KEY ft_products_name_description (name, description),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- promotions
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promotions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(200) NOT NULL,
    slug             VARCHAR(200) NOT NULL,
    description      TEXT NULL,
    discount_percent TINYINT UNSIGNED NOT NULL,
    image_path       VARCHAR(255) NULL,
    starts_at        DATETIME NOT NULL,
    ends_at          DATETIME NOT NULL,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_promotions_slug (slug),
    KEY idx_promotions_active_dates (is_active, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- promotion_products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promotion_products (
    promotion_id INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (promotion_id, product_id),
    CONSTRAINT fk_promo_products_promotion
        FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE CASCADE,
    CONSTRAINT fk_promo_products_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- orders
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NULL,
    order_number     VARCHAR(20) NOT NULL,
    status           ENUM('new', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    subtotal         DECIMAL(10,2) NOT NULL,
    discount_total   DECIMAL(10,2) NOT NULL DEFAULT 0,
    total            DECIMAL(10,2) NOT NULL,
    customer_name    VARCHAR(150) NOT NULL,
    customer_email   VARCHAR(190) NOT NULL,
    customer_phone   VARCHAR(30) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    comment          TEXT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_order_number (order_number),
    KEY idx_orders_user_created (user_id, created_at),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- order_items
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id         INT UNSIGNED NOT NULL,
    product_id       INT UNSIGNED NULL,
    product_name     VARCHAR(200) NOT NULL,
    unit_price       DECIMAL(10,2) NOT NULL,
    discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    qty              INT NOT NULL,
    line_total       DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- reviews
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NULL,
    rating        TINYINT NOT NULL,
    body          TEXT NOT NULL,
    status        ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    moderated_by  INT UNSIGNED NULL,
    moderated_at  DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reviews_status_featured (status, is_featured),
    KEY idx_reviews_product_status (product_id, status),
    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_moderated_by
        FOREIGN KEY (moderated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contact_messages
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    phone      VARCHAR(30) NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
