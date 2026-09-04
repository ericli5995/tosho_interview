CREATE TABLE product_labels (
    product_id  INT UNSIGNED NOT NULL,
    label       VARCHAR(40)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (product_id, label),
    KEY idx_labels_label (label),                 -- exact-label search
    CONSTRAINT fk_labels_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
