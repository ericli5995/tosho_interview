-- Schema + demo data. Imported automatically by the MySQL container on its
-- first start (docker-entrypoint-initdb.d, see docker-compose.yml).
--
-- Local MySQL:  mysql -u root tosho_dev < sql/init.sql   (re-run to reset)
--
-- Admin login: admin@example.com / password123
-- Demo images live in storage/uploads/demo/ and are checked in.

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS product_labels;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS admin_users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE products (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    model_code    VARCHAR(60)  NOT NULL,
    name          VARCHAR(200) NOT NULL,
    slug          VARCHAR(220) NOT NULL,
    description   TEXT         NULL,
    image_path    VARCHAR(255) NULL,            -- relative to storage/uploads (served as /media/...)
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

CREATE TABLE product_labels (
    product_id  INT UNSIGNED NOT NULL,
    label       VARCHAR(40)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (product_id, label),
    KEY idx_labels_label (label),                 -- exact-label search
    CONSTRAINT fk_labels_product FOREIGN KEY (product_id)
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

-- password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO admin_users (id, email, password_hash, name, created_at) VALUES
    (1, 'admin@example.com', '$2y$10$bOC0qtpID/2HRvBDpSza9.EE/VHx0ixV3Nvwnfb5z/ilIRxYxx5.i', 'Administrator', NOW());

INSERT INTO products (id, model_code, name, slug, description, image_path, stock, is_published, is_featured, sort_order, created_at, updated_at) VALUES
    (1,  'TE-13BR', 'φ13 ブラシギヤードモータ',      'te-13br', 'φ13の小型ブラシ付きギヤードモータ。カメラ・光学機器の絞り駆動やロック機構向け。',           'demo/te-13br.png', 120, 1, 0, 10, NOW(), NOW()),
    (2,  'TE-16BK', 'φ16 ブラシレスギヤードモータ',  'te-16bk', 'φ16のブラシレスギヤードモータ。低騒音・長寿命で医療・分析装置の送液ポンプ駆動に。',          'demo/te-16bk.png',  80, 1, 0, 20, NOW(), NOW()),
    (3,  'TE-22BK', 'φ22 ブラシレスギヤードモータ',  'te-22bk', 'φ22の代表機種。歯車技術とDCモータ技術を組み合わせ、精密機器の駆動を安定して支えます。高トルク・低速・低騒音。', 'demo/te-22bk.png', 200, 1, 1, 1, NOW(), NOW()),
    (4,  'TE-22BR', 'φ22 ブラシギヤードモータ',      'te-22br', 'φ22のブラシ付きギヤードモータ。コスト重視のFA機器・搬送装置に。',                        'demo/te-22br.png', 150, 1, 0, 30, NOW(), NOW()),
    (5,  'TE-32BK', 'φ32 ブラシレスギヤードモータ',  'te-32bk', 'φ32のブラシレスギヤードモータ。高減速比で大きな出力トルクを確保。産業用ロボットの関節駆動に。', 'demo/te-32bk.png',  40, 1, 0, 40, NOW(), NOW()),
    (6,  'TE-32BR', 'φ32 ブラシギヤードモータ',      'te-32br', 'φ32のブラシ付きギヤードモータ。汎用性の高い中型モデル。',                                  NULL,  60, 1, 0, 50, NOW(), NOW()),
    (7,  'TE-35BK', 'φ35 ブラシレスギヤードモータ',  'te-35bk', 'φ35のフラッグシップ・ブラシレス機。最大トルクと長寿命を両立。',                              'demo/te-35bk.png',   0, 1, 0, 60, NOW(), NOW()),
    (8,  'TE-35BR', 'φ35 ブラシギヤードモータ',      'te-35br', 'φ35のブラシ付きギヤードモータ。据え置き装置の駆動に適した大型モデル。',                      NULL,  25, 1, 0, 70, NOW(), NOW()),
    (9,  'TE-16BR', 'φ16 ブラシギヤードモータ',      'te-16br', 'φ16のブラシ付きギヤードモータ。電池駆動機器向けの低電圧モデル。',                          NULL,  90, 1, 0, 25, NOW(), NOW()),
    (10, 'TE-22CS', 'φ22 カスタムギヤードモータ',    'te-22cs', 'φ22ベースのカスタム対応例。軸長・コネクタ・減速比を用途に合わせて変更可能。',                 NULL,   5, 1, 0, 80, NOW(), NOW());

INSERT INTO product_labels (product_id, label, sort_order) VALUES
    (1, 'ブラシ', 0),     (1, 'φ13', 1), (1, '6V', 2),
    (2, 'ブラシレス', 0), (2, 'φ16', 1), (2, '12V', 2), (2, '低騒音', 3),
    (3, 'ブラシレス', 0), (3, 'φ22', 1), (3, '24V', 2), (3, '高トルク', 3), (3, '低騒音', 4),
    (4, 'ブラシ', 0),     (4, 'φ22', 1), (4, '12V', 2),
    (5, 'ブラシレス', 0), (5, 'φ32', 1), (5, '24V', 2), (5, '高トルク', 3),
    (6, 'ブラシ', 0),     (6, 'φ32', 1), (6, '24V', 2),
    (7, 'ブラシレス', 0), (7, 'φ35', 1), (7, '24V', 2), (7, '高トルク', 3), (7, '長寿命', 4),
    (8, 'ブラシ', 0),     (8, 'φ35', 1), (8, '24V', 2),
    (9, 'ブラシ', 0),     (9, 'φ16', 1), (9, '6V', 2),
    (10, 'ブラシレス', 0), (10, 'φ22', 1), (10, '12V', 2), (10, 'カスタム', 3);
