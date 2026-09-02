<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredAndMaxAreEnforced(): void
    {
        $validator = new Validator(['model_code' => '', 'name' => str_repeat('x', 210)]);

        $passed = $validator->validate(
            ['model_code' => 'required|string|max:60', 'name' => 'required|max:200'],
            ['model_code' => '型番', 'name' => '製品名']
        );

        $this->assertFalse($passed);
        $this->assertArrayHasKey('model_code', $validator->errors());
        $this->assertArrayHasKey('name', $validator->errors());
        $this->assertCount(2, $validator->flatErrors());
    }

    public function testValidPayloadPasses(): void
    {
        $validator = new Validator([
            'model_code' => 'TE-22BK',
            'name' => 'φ22 ブラシレスギヤードモータ',
            'rated_voltage' => '24.0',
            'body_diameter' => '22',
            'motor_type' => 'brushless',
        ]);

        $passed = $validator->validate([
            'model_code' => 'required|string|max:60',
            'name' => 'required|string|max:200',
            'rated_voltage' => 'numeric',
            'body_diameter' => 'integer',
            'motor_type' => 'in:,brushed,brushless',
        ]);

        $this->assertTrue($passed);
        $this->assertSame([], $validator->flatErrors());
    }

    public function testOptionalFieldsMayBeBlank(): void
    {
        $validator = new Validator(['rated_voltage' => '', 'body_diameter' => null]);

        $this->assertTrue($validator->validate([
            'rated_voltage' => 'numeric',
            'body_diameter' => 'integer',
        ]));
    }

    public function testNonNumericValueIsRejected(): void
    {
        $validator = new Validator(['rated_voltage' => 'abc']);

        $this->assertFalse($validator->validate(['rated_voltage' => 'numeric'], ['rated_voltage' => '定格電圧']));
        $this->assertStringContainsString('定格電圧', $validator->flatErrors()[0]);
    }
}
