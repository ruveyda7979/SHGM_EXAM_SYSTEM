<?php
/**
 * SHGM Exam System - Validator (Güncel)
 * -------------------------------------------------------------
 * Kurallar:
 *   required | email | numeric | int | string | boolean
 *   min:N | max:N | minLen:N | maxLen:N | between:min,max
 *   in:a,b,c | regex:/.../
 *
 * Dönen:
 *   - Geçerliyse: true
 *   - Hatalar varsa: ['field' => 'mesaj', ...]
 *
 * Opsiyonlar:
 *   - validate($data, $rules, ['bail' => true])  // alan başına ilk hatada dur
 */

class TN_Validator
{
    /** @var array<string,string> */
    protected array $errors = [];

    /**
     * @param array              $data   Doğrulanacak veri (genelde $_POST)
     * @param array<string,string> $rules  ['email' => 'required|email|minLen:3|maxLen:120', ...]
     * @param array              $options Örn: ['bail' => true]
     * @return bool|array
     */
    public function validate(array $data, array $rules, array $options = [])
    {
        $this->errors = [];
        $bail = (bool)($options['bail'] ?? true); // alan başına ilk hatada dur

        foreach ($rules as $field => $ruleString) {
            $value   = $data[$field] ?? null;
            $ruleset = array_filter(array_map('trim', explode('|', (string)$ruleString)));

            foreach ($ruleset as $rule) {
                $name  = $rule;
                $param = null;

                if (strpos($rule, ':') !== false) {
                    [$name, $param] = explode(':', $rule, 2);
                }

                $method = 'rule_' . $name;
                if (method_exists($this, $method)) {
                    $this->{$method}($field, $value, $param);
                }
                // bilinmeyen kural verilmişse sessizce atlarız

                if ($bail && isset($this->errors[$field])) {
                    break; // bu alan için ilk hata yeterli
                }
            }
        }

        return empty($this->errors) ? true : $this->errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    // ---------------------------------------------------------
    // Kurallar
    // ---------------------------------------------------------

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
            $this->errors[$field] = 'Sayısal bir değer olmalıdır.';
        }
    }

    protected function rule_int(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '') return;
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = 'Tamsayı olmalıdır.';
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
            $this->errors[$field] = 'Boolean (true/false) olmalıdır.';
        }
    }

    /**
     * min:max — sayısal ise değerin kendisi, değilse uzunluk kontrolü
     */
    protected function rule_min(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;

        $n = (int)$param;
        if (is_numeric($value)) {
            if ((float)$value < $n) {
                $this->errors[$field] = "En az {$n} olmalıdır.";
            }
        } else {
            if (mb_strlen((string)$value) < $n) {
                $this->errors[$field] = "En az {$n} karakter olmalıdır.";
            }
        }
    }

    protected function rule_max(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;

        $n = (int)$param;
        if (is_numeric($value)) {
            if ((float)$value > $n) {
                $this->errors[$field] = "En fazla {$n} olmalıdır.";
            }
        } else {
            if (mb_strlen((string)$value) > $n) {
                $this->errors[$field] = "En fazla {$n} karakter olabilir.";
            }
        }
    }

    /** salt uzunluk bazlı min */
    protected function rule_minLen(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;
        $n = (int)$param;
        if (mb_strlen((string)$value) < $n) {
            $this->errors[$field] = "En az {$n} karakter olmalıdır.";
        }
    }

    /** salt uzunluk bazlı max */
    protected function rule_maxLen(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;
        $n = (int)$param;
        if (mb_strlen((string)$value) > $n) {
            $this->errors[$field] = "En fazla {$n} karakter olabilir.";
        }
    }

    /** between:min,max — uzunluk aralığı */
    protected function rule_between(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;
        [$min, $max] = array_map('intval', explode(',', (string)$param));
        $len = mb_strlen((string)$value);
        if ($len < $min || $len > $max) {
            $this->errors[$field] = "Uzunluk {$min}-{$max} karakter arasında olmalıdır.";
        }
    }

    protected function rule_in(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;
        $list = array_map('trim', explode(',', (string)$param));
        if (!in_array((string)$value, $list, true)) {
            $this->errors[$field] = 'Geçersiz değer.';
        }
    }

    protected function rule_regex(string $field, $value, $param = null): void
    {
        if ($value === null || $value === '' || $param === null) return;

        // Ör: regex:/^[a-z0-9_]{3,}$/i
        $pattern = (string)$param;
        if (@preg_match($pattern, '') === false) {
            // geçersiz pattern verilmişse yoksay
            return;
        }
        if (!preg_match($pattern, (string)$value)) {
            $this->errors[$field] = 'Format uygun değil.';
        }
    }
}
