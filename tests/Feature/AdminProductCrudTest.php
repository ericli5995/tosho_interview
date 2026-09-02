<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use App\Core\Database;
use App\Repository\ProductImageRepository;
use App\Repository\ProductRepository;
use App\Service\ImageUploadService;
use App\Service\ProductSearchService;
use App\Service\ProductService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the product write path + search query against a real MySQL database.
 *
 * Skipped unless a test database is configured, e.g.:
 *   DB_TEST_DSN="mysql:host=127.0.0.1;dbname=tosho_test;charset=utf8mb4" \
 *   DB_TEST_USER=root DB_TEST_PASS= \
 *   php tools/phpunit.phar --testsuite feature
 */
final class AdminProductCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $dsn = getenv('DB_TEST_DSN');
        if ($dsn === false || $dsn === '') {
            $this->markTestSkipped('DB_TEST_DSN is not set.');
        }

        try {
            $pdo = new PDO(
                $dsn,
                getenv('DB_TEST_USER') ?: null,
                getenv('DB_TEST_PASS') ?: null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped('Cannot connect to the test database: ' . $e->getMessage());
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['product_images', 'product_specs', 'products', 'categories', 'admin_users', 'schema_migrations'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$table}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        foreach (glob(BASE_PATH . '/sql/migrations/*.sql') ?: [] as $file) {
            $pdo->exec((string) file_get_contents($file));
        }

        $this->pdo = $pdo;
        Database::setConnection($pdo);
        App::reset();
        App::bind('db', $pdo);
    }

    protected function tearDown(): void
    {
        Database::reset();
        App::reset();
    }

    private function service(): ProductService
    {
        $products = new ProductRepository($this->pdo);

        return new ProductService(
            $products,
            new ProductImageRepository($this->pdo),
            new ImageUploadService(sys_get_temp_dir() . '/tosho-test-uploads'),
            $this->pdo
        );
    }

    /** @return array<string,mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'model_code' => 'TE-TEST',
            'name' => 'テスト製品',
            'slug' => '',
            'category_id' => null,
            'motor_type' => 'brushless',
            'rated_voltage' => '24',
            'gear_ratio' => '1/100',
            'body_diameter' => '22',
            'rated_torque' => '45',
            'rated_speed' => '30',
            'noise_level' => '38',
            'life_hours' => '10000',
            'description' => '説明',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'specs' => [
                ['label' => 'モータ種類', 'value' => 'BLDC', 'unit' => ''],
                ['label' => '', 'value' => 'ignored', 'unit' => ''],
            ],
        ], $overrides);
    }

    public function testCreatePersistsProductAndSkipsBlankSpecRows(): void
    {
        $product = $this->service()->create($this->payload());

        $this->assertGreaterThan(0, $product->id);
        $this->assertSame('te-test', $product->slug, 'slug is derived from the model code');

        $fetched = (new ProductRepository($this->pdo))->findBySlug('te-test');
        $this->assertNotNull($fetched);
        $this->assertSame('テスト製品', $fetched->name);
        $this->assertCount(1, $fetched->specs, 'the blank-label spec row is dropped');
        $this->assertSame('BLDC', $fetched->specs[0]['value']);
    }

    public function testDuplicateSlugIsRejectedByRepository(): void
    {
        $repo = new ProductRepository($this->pdo);
        $this->service()->create($this->payload(['model_code' => 'TE-DUP']));

        $this->assertTrue($repo->slugExists('te-dup'));
        $this->assertFalse($repo->slugExists('te-dup', $repo->findBySlug('te-dup')?->id));
    }

    public function testSearchFiltersByDiameterAndExcludesDrafts(): void
    {
        $service = $this->service();
        $service->create($this->payload(['model_code' => 'TE-22X', 'body_diameter' => '22']));
        $service->create($this->payload(['model_code' => 'TE-32X', 'body_diameter' => '32']));
        $service->create($this->payload(['model_code' => 'TE-22D', 'body_diameter' => '22', 'is_published' => false]));

        $repo = new ProductRepository($this->pdo);
        $criteria = (new ProductSearchService())->fromInput(['diameter' => ['22']]);
        $result = $repo->search($criteria);

        $this->assertSame(1, $result['total'], 'only the published ø22 product matches');
        $this->assertSame('TE-22X', $result['items'][0]->modelCode);
    }

    public function testDeleteRemovesProductAndCascadesSpecs(): void
    {
        $product = $this->service()->create($this->payload(['model_code' => 'TE-DEL']));
        $this->service()->delete($product->id);

        $this->assertNull((new ProductRepository($this->pdo))->find($product->id));
        $specCount = (int) $this->pdo->query('SELECT COUNT(*) FROM product_specs')->fetchColumn();
        $this->assertSame(0, $specCount);
    }
}
