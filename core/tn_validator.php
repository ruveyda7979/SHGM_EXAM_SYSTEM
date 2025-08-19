<?php
/**
 * SHGM Exam System - Simple Validator
 * Kurallar: required | email | numeric | string | boolean | min:N | max:N | in:a,b,c | regex:/.../
 */

class TN_Validator
{
    /** @var array<string,string> */
    protected $errors = [];

    /**
     * @param array $data   Doğrulanacak veri (genelde $_POST)
     * @param array $rules  ['field' => 'required|min:3|max:50|email', ...]
     * @return bool|array   true veya HATA DİZİSİ
     */
    public function validate(array $data, array $rules)
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value   = $data[$field] ?? null;
            $ruleset = array_filter(explode('|', (string)$ruleString));

            foreach ($ruleset as $rule) {
                $name  = $rule;
                $param = null;

                // min:3, max:255, in:a,b,c, regex:/.../
                if (strpos($rule, ':') !== false) {
                    [$name, $param] = explode(':', $rule, 2);
                }

                $method = 'rule_' . $name;
                if (method_exists($this, $method)) {
                    $this->{$method}($field, $value, $param);
                } else {
                    // Bilinmeyen kural atlanır
                }
            }
        }

        return empty($this->errors) ? true : $this->errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    // ---------- Kurallar ----------

    protected function rule_required(string $field, $value, $param = null): void
    {
        $empty = ($value === null || $value === '' || (is_array($value) && count($value) === 0));
        if ($empty) {
            $this->errors[$field] = 'Bu alan zorunludur.';
        }
    }

    protected function rule_email(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '') return;
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Geçerli bir e-posta giriniz.';
        }
    }

    protected function rule_numeric(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '') return;
        if (!is_numeric($value)) {
            $this->errors[$field] = 'Sayı olmalıdır.';
        }
    }

    protected function rule_string(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '') return;
        if (!is_string($value)) {
            $this->errors[$field] = 'Metin olmalıdır.';
        }
    }

    protected function rule_boolean(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '') return;
        if (!is_bool($value) && !in_array($value, [0,1,'0','1',true,false], true)) {
            $this->errors[$field] = 'Boolean olmalıdır.';
        }
    }

    protected function rule_min(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;

        $n = (int)$param;
        if (is_numeric($value)) {
            if ((float)$value < $n) $this->errors[$field] = "En az {$n} olmalıdır.";
        } else {
            if (mb_strlen((string)$value) < $n) $this->errors[$field] = "En az {$n} karakter olmalıdır.";
        }
    }

    protected function rule_max(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;

        $n = (int)$param;
        if (is_numeric($value)) {
            if ((float)$value > $n) $this->errors[$field] = "En fazla {$n} olmalıdır.";
        } else {
            if (mb_strlen((string)$value) > $n) $this->errors[$field] = "En fazla {$n} karakter olmalıdır.";
        }
    }

    protected function rule_in(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;
        $list = array_map('trim', explode(',', $param));
        if (!in_array((string)$value, $list, true)) {
            $this->errors[$field] = 'Geçersiz değer.';
        }
    }

    protected function rule_regex(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;

        // Örn: regex:/^[a-z0-9_]{3,}$/i
        $pattern = $param;
        if (@preg_match($pattern, '') === false) {
            // Geçersiz pattern verilmişse yoksay
            return;
        }
        if (!preg_match($pattern, (string)$value)) {
            $this->errors[$field] = 'Format uygun değil.';
        }
    }
}
