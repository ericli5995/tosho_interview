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
