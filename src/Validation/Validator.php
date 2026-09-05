<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Small rule-string validator: `'field' => 'required|string|max:200'`.
 * Japanese messages; one error per field.
 */
final class Validator
{
    /** @var array<string,list<string>> */
    private array $errors = [];

    /** @param array<string,mixed> $data */
    public function __construct(private array $data)
    {
    }

    /**
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     */
    public function validate(array $rules, array $labels = []): bool
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $label = $labels[$field] ?? $field;

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                if (!$this->passes($name, $value, $arg)) {
                    $this->errors[$field][] = $this->message($name, $label, $arg);
                    break;
                }
            }
        }

        return $this->errors === [];
    }

    /** @return array<string,list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function passes(?string $rule, mixed $value, ?string $arg): bool
    {
        $isBlank = $value === null || $value === '' || $value === [];

        return match ($rule) {
            'required' => !$isBlank,
            'string' => $isBlank || is_string($value),
            'numeric' => $isBlank || is_numeric($value),
            'integer' => $isBlank || preg_match('/^-?\d+$/', (string) $value) === 1,
            'max' => $isBlank || mb_strlen((string) $value) <= (int) $arg,
            'min' => $isBlank || mb_strlen((string) $value) >= (int) $arg,
            'in' => $isBlank || in_array((string) $value, explode(',', (string) $arg), true),
            default => true,
        };
    }

    private function message(?string $rule, string $label, ?string $arg): string
    {
        return match ($rule) {
            'required' => "{$label}は必須です。",
            'max' => "{$label}は{$arg}文字以内で入力してください。",
            'min' => "{$label}は{$arg}文字以上で入力してください。",
            'numeric', 'integer' => "{$label}は数値で入力してください。",
            'in' => "{$label}の選択が不正です。",
            default => "{$label}の値が不正です。",
        };
    }
}
