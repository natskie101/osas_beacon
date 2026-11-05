<?php

declare(strict_types=1);

namespace App\Support;

final class Validator
{
    private bool $hasRun = false;

    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     */
    private function __construct(
        private readonly array $data,
        private readonly array $rules,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool
    {
        $this->run();

        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        $this->run();

        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        $this->run();
        $validated = [];

        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    private function run(): void
    {
        if ($this->hasRun) {
            return;
        }

        $this->hasRun = true;

        foreach ($this->rules as $field => $ruleSet) {
            $this->validateField($field, $this->data[$field] ?? null, $this->normalizeRules($ruleSet));
        }
    }

    /** @param string|list<string> $rules @return list<string> */
    private function normalizeRules(string|array $rules): array
    {
        if (is_string($rules)) {
            return array_values(array_filter(array_map('trim', explode('|', $rules))));
        }

        $normalized = [];

        foreach ($rules as $rule) {
            foreach (explode('|', $rule) as $part) {
                if (trim($part) !== '') {
                    $normalized[] = trim($part);
                }
            }
        }

        return $normalized;
    }

    /** @param list<string> $rules */
    private function validateField(string $field, mixed $value, array $rules): void
    {
        $nullable = in_array('nullable', $rules, true);
        $required = in_array('required', $rules, true);
        $isEmpty = $value === null || $value === '' || (is_array($value) && $value === []);

        if ($nullable && $isEmpty) {
            return;
        }

        if ($required && $isEmpty) {
            $this->addError($field, 'The ' . $this->label($field) . ' field is required.');

            return;
        }

        if ($isEmpty) {
            return;
        }

        $numericField = in_array('integer', $rules, true) || in_array('numeric', $rules, true);

        foreach ($rules as $rule) {
            $name = $rule;
            $param = null;

            if (str_contains($rule, ':')) {
                [$name, $param] = explode(':', $rule, 2);
            }

            switch ($name) {
                case 'required':
                case 'nullable':
                    break;

                case 'string':
                    if (!is_string($value)) {
                        $this->addError($field, 'The ' . $this->label($field) . ' must be text.');
                    }
                    break;

                case 'email':
                    if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                        $this->addError($field, 'Enter a valid email address.');
                    }
                    break;

                case 'integer':
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $this->addError($field, 'The ' . $this->label($field) . ' must be a whole number.');
                    }
                    break;

                case 'numeric':
                    if (!is_numeric($value)) {
                        $this->addError($field, 'The ' . $this->label($field) . ' must be a number.');
                    }
                    break;

                case 'boolean':
                    if (!in_array($value, [true, false, 0, 1, '0', '1', 'on', 'yes', 'no'], true)) {
                        $this->addError($field, 'The ' . $this->label($field) . ' must be true or false.');
                    }
                    break;

                case 'min':
                    $minimum = (int) $param;
                    if ($numericField && is_numeric($value)) {
                        if ((float) $value < $minimum) {
                            $this->addError($field, 'The ' . $this->label($field) . ' must be at least ' . $minimum . '.');
                        }
                    } elseif (is_string($value) && mb_strlen($value) < $minimum) {
                        $this->addError($field, 'The ' . $this->label($field) . ' must be at least ' . $minimum . ' characters.');
                    }
                    break;

                case 'max':
                    $maximum = (int) $param;
                    if ($numericField && is_numeric($value)) {
                        if ((float) $value > $maximum) {
                            $this->addError($field, 'The ' . $this->label($field) . ' may not be greater than ' . $maximum . '.');
                        }
                    } elseif (is_string($value) && mb_strlen($value) > $maximum) {
                        $this->addError($field, 'The ' . $this->label($field) . ' may not be longer than ' . $maximum . ' characters.');
                    }
                    break;

                case 'in':
                    $options = explode(',', (string) $param);
                    if (!in_array((string) $value, $options, true)) {
                        $this->addError($field, 'Choose a valid ' . $this->label($field) . '.');
                    }
                    break;

                case 'same':
                    if ((string) $value !== (string) ($this->data[(string) $param] ?? '')) {
                        $this->addError($field, 'The ' . $this->label($field) . ' does not match ' . $this->label((string) $param) . '.');
                    }
                    break;

                case 'confirmed':
                    if ((string) $value !== (string) ($this->data[$field . '_confirmation'] ?? '')) {
                        $this->addError($field, 'The ' . $this->label($field) . ' confirmation does not match.');
                    }
                    break;

                case 'date':
                    if (is_string($value) && strtotime($value) === false) {
                        $this->addError($field, 'Enter a valid date for ' . $this->label($field) . '.');
                    }
                    break;

                case 'unique':
                    $this->validateUnique($field, $value, (string) $param);
                    break;
            }
        }
    }

    private function validateUnique(string $field, mixed $value, string $param): void
    {
        $parts = explode(',', $param);
        $table = $parts[0] ?? '';
        $column = $parts[1] ?? '';
        $exceptId = $parts[2] ?? null;

        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1 || preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1) {
            return;
        }

        $sql = 'SELECT COUNT(*) FROM `' . $table . '` WHERE `' . $column . '` = ?';
        $bindings = [$value];

        if ($exceptId !== null && $exceptId !== '') {
            $sql .= ' AND `id` <> ?';
            $bindings[] = $exceptId;
        }

        if ((int) DB::value($sql, $bindings) > 0) {
            $this->addError($field, 'The ' . $this->label($field) . ' has already been taken.');
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!array_key_exists($field, $this->errors)) {
            $this->errors[$field] = $message;
        }
    }

    private function label(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }
}
