<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\ProductSearchService;
use PHPUnit\Framework\TestCase;

final class ProductSearchServiceTest extends TestCase
{
    public function testHostileInputIsClampedAndWhitelisted(): void
    {
        $service = new ProductSearchService();

        $criteria = $service->fromInput([
            'q' => str_repeat('あ', 250),
            'motor_type' => 'evil',
            'diameter' => ['13', '22', '22', '-5', 'abc', '99999'],
            'voltage' => ['24', '12'],
            'sort' => 'DROP TABLE products',
            'page' => '-9',
            'per_page' => '100000',
        ]);

        $this->assertSame(100, mb_strlen($criteria->keyword));
        $this->assertSame('', $criteria->motorType, 'unknown motor type is dropped');
        $this->assertSame([13, 22], $criteria->diameters, 'deduped, positive, bounded');
        $this->assertSame([24, 12], $criteria->voltages);
        $this->assertSame('featured', $criteria->sort, 'unknown sort falls back');
        $this->assertSame(1, $criteria->page, 'page is at least 1');
        $this->assertSame(48, $criteria->perPage, 'per_page is capped');
    }

    public function testValidInputIsPreserved(): void
    {
        $service = new ProductSearchService();

        $criteria = $service->fromInput([
            'q' => '  TE-22  ',
            'motor_type' => 'brushed',
            'diameter' => ['16'],
            'sort' => 'name',
            'page' => '3',
        ]);

        $this->assertSame('TE-22', $criteria->keyword);
        $this->assertSame('brushed', $criteria->motorType);
        $this->assertSame([16], $criteria->diameters);
        $this->assertSame('name', $criteria->sort);
        $this->assertSame(3, $criteria->page);
        $this->assertSame(24, $criteria->offset(), '(3 - 1) * 12');
    }

    public function testEmptyInputYieldsSensibleDefaults(): void
    {
        $criteria = (new ProductSearchService())->fromInput([]);

        $this->assertSame('', $criteria->keyword);
        $this->assertSame([], $criteria->diameters);
        $this->assertSame('featured', $criteria->sort);
        $this->assertSame(1, $criteria->page);
        $this->assertSame(12, $criteria->perPage);
    }
}
