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
