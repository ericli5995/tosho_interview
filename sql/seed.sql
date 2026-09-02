-- Demo catalogue data. Safe to re-run after `php bin/migrate.php --fresh`.
-- Admin users are NOT seeded here; create one with:
--   php bin/create-admin.php admin@example.com "your-password"

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE product_specs;
TRUNCATE TABLE product_images;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO categories (id, name, slug, parent_id, sort_order) VALUES
    (1, 'ブラシレスギヤードモータ', 'brushless', NULL, 1),
    (2, 'ブラシ付きギヤードモータ', 'brushed', NULL, 2),
    (3, 'カスタム・特殊仕様', 'custom', NULL, 3);

INSERT INTO products
    (id, model_code, name, slug, category_id, motor_type, rated_voltage, gear_ratio,
     body_diameter, rated_torque, rated_speed, noise_level, life_hours, description,
     is_published, is_featured, sort_order, created_at, updated_at)
VALUES
    (1, 'TE-13BR', 'φ13 ブラシギヤードモータ', 'te-13br', 2, 'brushed', 6.00, '1/50',
     13, 8.00, 60, 42.0, 3000, 'φ13の小型ブラシ付きギヤードモータ。カメラ・光学機器の絞り駆動やロック機構向け。',
     1, 0, 10, NOW(), NOW()),
    (2, 'TE-16BK', 'φ16 ブラシレスギヤードモータ', 'te-16bk', 1, 'brushless', 12.00, '1/100',
     16, 20.00, 45, 40.0, 8000, 'φ16のブラシレスギヤードモータ。低騒音・長寿命で医療・分析装置の送液ポンプ駆動に。',
     1, 0, 20, NOW(), NOW()),
    (3, 'TE-22BK', 'φ22 ブラシレスギヤードモータ', 'te-22bk', 1, 'brushless', 24.00, '1/120',
     22, 45.00, 30, 38.0, 10000, 'φ22の代表機種。歯車技術とDCモータ技術を組み合わせ、精密機器の駆動を安定して支えます。高トルク・低速・低騒音。',
     1, 1, 1, NOW(), NOW()),
    (4, 'TE-22BR', 'φ22 ブラシギヤードモータ', 'te-22br', 2, 'brushed', 12.00, '1/80',
     22, 35.00, 40, 44.0, 4000, 'φ22のブラシ付きギヤードモータ。コスト重視のFA機器・搬送装置に。',
     1, 0, 30, NOW(), NOW()),
    (5, 'TE-32BK', 'φ32 ブラシレスギヤードモータ', 'te-32bk', 1, 'brushless', 24.00, '1/200',
     32, 120.00, 18, 41.0, 12000, 'φ32のブラシレスギヤードモータ。高減速比で大きな出力トルクを確保。産業用ロボットの関節駆動に。',
     1, 0, 40, NOW(), NOW()),
    (6, 'TE-32BR', 'φ32 ブラシギヤードモータ', 'te-32br', 2, 'brushed', 24.00, '1/150',
     32, 100.00, 22, 46.0, 5000, 'φ32のブラシ付きギヤードモータ。汎用性の高い中型モデル。',
     1, 0, 50, NOW(), NOW()),
    (7, 'TE-35BK', 'φ35 ブラシレスギヤードモータ', 'te-35bk', 1, 'brushless', 24.00, '1/250',
     35, 160.00, 15, 43.0, 15000, 'φ35のフラッグシップ・ブラシレス機。最大トルクと長寿命を両立。',
     1, 0, 60, NOW(), NOW()),
    (8, 'TE-35BR', 'φ35 ブラシギヤードモータ', 'te-35br', 2, 'brushed', 24.00, '1/180',
     35, 140.00, 20, 48.0, 6000, 'φ35のブラシ付きギヤードモータ。据え置き装置の駆動に適した大型モデル。',
     1, 0, 70, NOW(), NOW()),
    (9, 'TE-16BR', 'φ16 ブラシギヤードモータ', 'te-16br', 2, 'brushed', 6.00, '1/60',
     16, 15.00, 55, 43.0, 3500, 'φ16のブラシ付きギヤードモータ。電池駆動機器向けの低電圧モデル。',
     1, 0, 25, NOW(), NOW()),
    (10, 'TE-22CS', 'φ22 カスタムギヤードモータ', 'te-22cs', 3, 'brushless', 12.00, '1/144',
     22, 50.00, 28, 37.0, 10000, 'φ22ベースのカスタム対応例。軸長・コネクタ・減速比を用途に合わせて変更可能。',
     1, 0, 80, NOW(), NOW());

INSERT INTO product_specs (product_id, label, value, unit, sort_order) VALUES
    (1, 'モータ種類', 'DCブラシ', NULL, 0),
    (1, '定格電圧', '6', 'V', 1),
    (1, '減速比', '1/50', NULL, 2),
    (1, '定格トルク', '8', 'mN・m', 3),
    (2, 'モータ種類', 'BLDC', NULL, 0),
    (2, '定格電圧', '12', 'V', 1),
    (2, '減速比', '1/100', NULL, 2),
    (2, '定格トルク', '20', 'mN・m', 3),
    (3, 'モータ種類', 'BLDC', NULL, 0),
    (3, '定格電圧', '24', 'V', 1),
    (3, '減速比', '1/120', NULL, 2),
    (3, '定格トルク', '45', 'mN・m', 3),
    (3, '定格回転数', '30', 'r/min', 4),
    (3, '出力軸径', 'φ3', NULL, 5),
    (4, 'モータ種類', 'DCブラシ', NULL, 0),
    (4, '定格電圧', '12', 'V', 1),
    (4, '減速比', '1/80', NULL, 2),
    (5, 'モータ種類', 'BLDC', NULL, 0),
    (5, '定格電圧', '24', 'V', 1),
    (5, '減速比', '1/200', NULL, 2),
    (5, '定格トルク', '120', 'mN・m', 3),
    (7, 'モータ種類', 'BLDC', NULL, 0),
    (7, '定格電圧', '24', 'V', 1),
    (7, '減速比', '1/250', NULL, 2),
    (7, '定格トルク', '160', 'mN・m', 3),
    (10, 'モータ種類', 'BLDC', NULL, 0),
    (10, '定格電圧', '12', 'V', 1),
    (10, '減速比', '1/144', NULL, 2);
