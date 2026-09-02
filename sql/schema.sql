-- Full schema snapshot for convenience / reference.
-- The authoritative source is sql/migrations/*.sql, applied via `php bin/migrate.php`.
-- Create the database first, e.g.:
--   CREATE DATABASE tosho_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_specs;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS schema_migrations;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    parent_id   INT UNSIGNED NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE products (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    model_code           VARCHAR(60) NOT NULL,
    name                 VARCHAR(200) NOT NULL,
    slug                 VARCHAR(220) NOT NULL,
    category_id          INT UNSIGNED NULL,
    motor_type           VARCHAR(20) NOT NULL DEFAULT '',
    rated_voltage        DECIMAL(6, 2) NULL,
    gear_ratio           VARCHAR(30) NULL,
    body_diameter        INT UNSIGNED NULL,
    rated_torque         DECIMAL(8, 2) NULL,
    rated_speed          INT UNSIGNED NULL,
    noise_level          DECIMAL(5, 1) NULL,
    life_hours           INT UNSIGNED NULL,
    description          TEXT NULL,
    outline_drawing_path VARCHAR(255) NULL,
    is_published         TINYINT(1) NOT NULL DEFAULT 0,
    is_featured          TINYINT(1) NOT NULL DEFAULT 0,
    sort_order           INT NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL,
    updated_at           DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_published (is_published),
    KEY idx_products_category (category_id),
    KEY idx_products_diameter (body_diameter),
    KEY idx_products_voltage (rated_voltage),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE product_specs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id  INT UNSIGNED NOT NULL,
    label       VARCHAR(80) NOT NULL,
    value       VARCHAR(160) NOT NULL,
    unit        VARCHAR(30) NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_specs_product (product_id),
    CONSTRAINT fk_specs_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE product_images (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id  INT UNSIGNED NOT NULL,
    path        VARCHAR(255) NOT NULL,
    thumb_path  VARCHAR(255) NULL,
    medium_path VARCHAR(255) NULL,
    alt         VARCHAR(200) NOT NULL DEFAULT '',
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    width       INT UNSIGNED NULL,
    height      INT UNSIGNED NULL,
    size_bytes  INT UNSIGNED NULL,
    mime        VARCHAR(50) NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_images_product (product_id),
    CONSTRAINT fk_images_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE admin_users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email          VARCHAR(190) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    name           VARCHAR(120) NOT NULL DEFAULT 'Administrator',
    last_login_at  DATETIME NULL,
    created_at     DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_users_email (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE schema_migrations (
    version    VARCHAR(255) NOT NULL,
    applied_at DATETIME NOT NULL,
    PRIMARY KEY (version)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
