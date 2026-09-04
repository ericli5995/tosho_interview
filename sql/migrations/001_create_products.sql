CREATE TABLE products (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    model_code    VARCHAR(60)  NOT NULL,
    name          VARCHAR(200) NOT NULL,
    slug          VARCHAR(220) NOT NULL,
    description   TEXT         NULL,
    image_path    VARCHAR(255) NULL,            -- products/{id}/{hash}.jpg; _medium/_thumb variants by convention
    stock         INT UNSIGNED NOT NULL DEFAULT 0,
    is_published  TINYINT(1)   NOT NULL DEFAULT 0,
    is_featured   TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order    INT          NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL,
    updated_at    DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_published (is_published, sort_order)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
