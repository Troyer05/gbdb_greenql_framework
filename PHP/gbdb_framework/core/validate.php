<?php

class Validate {

    /**
     * checks required fields
     * @param array $data
     * @param array $fields
     * @return bool
     */
    public static function required(array $data, array $fields): bool {
        foreach ($fields as $f) {
            if (!array_key_exists($f, $data)) {
                return false;
            }

            if (trim((string)$data[$f]) === "") {
                return false;
            }

        }

        return true;
    }

    /**
     * checks if value is valid email
     * @param string $value
     * @return bool
     */
    public static function email(string $value): bool {
        return (bool)filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    }

    /**
     * checks if value is numeric
     * @param string|int|float $value
     * @return bool
     */
    public static function number(string|int|float $value): bool {
        $v = str_replace(",", ".", trim((string)$value));

        return is_numeric($v);
    }

    /**
     * checks minimum string length
     * @param string $value
     * @param int $min
     * @return bool
     */
    public static function minLength(string $value, int $min): bool {
        return mb_strlen(trim($value)) >= $min;
    }

    /**
     * checks maximum string length
     * @param string $value
     * @param int $max
     * @return bool
     */
    public static function maxLength(string $value, int $max): bool {
        return mb_strlen(trim($value)) <= $max;
    }

    /**
     * checks if value matches regex pattern
     * @param string $value
     * @param string $pattern
     * @return bool
     */
    public static function regex(string $value, string $pattern): bool {
        if ($pattern === "") {
            return false;
        }

        $result = @preg_match($pattern, $value);

        if ($result === false) {
            return false;
        }

        return $result === 1;
    }

    /**
     * checks if number is between min and max
     * @param float|int $value
     * @param float|int $min
     * @param float|int $max
     * @return bool
     */
    public static function between(float|int $value, float|int $min, float|int $max): bool {
        return $value >= $min && $value <= $max;
    }

    /**
     * checks if value is in allowed list
     * @param string|int $value
     * @param array $allowed
     * @return bool
     */
    public static function in(string|int $value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }

    /**
     * checks if two strings match
     * @param string $a
     * @param string $b
     * @return bool
     */
    public static function match(string $a, string $b): bool {
        return hash_equals($a, $b);
    }

    /**
     * validates data array with rules
     * @param array $data
     * @param array $rules
     * @return array
     */
    public static function validateArray(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleList = explode("|", (string)$ruleString);

            foreach ($ruleList as $rule) {
                $param = null;

                if (str_contains($rule, ":")) {
                    [$rule, $param] = explode(":", $rule, 2);
                }

                $valid = true;

                switch ($rule) {
                    case "required":
                        $valid = $value !== null && trim((string)$value) !== "";
                        break;

                    case "email":
                        $valid = self::email((string)$value);
                        break;

                    case "number":
                        $valid = self::number((string)$value);
                        break;

                    case "min":
                        $valid = self::minLength((string)$value, (int)$param);
                        break;

                    case "max":
                        $valid = self::maxLength((string)$value, (int)$param);
                        break;

                    case "regex":
                        $valid = self::regex((string)$value, (string)$param);
                        break;

                    case "in":
                        $valid = self::in((string)$value, explode(",", (string)$param));
                        break;

                    case "between":

                        if ($param !== null && str_contains($param, ",")) {
                            [$min, $max] = explode(",", $param, 2);
                            $valid = self::between((float)$value, (float)$min, (float)$max);
                        } else {
                            $valid = false;
                        }

                        break;

                    default:
                        $valid = true;
                        break;
                }

                if (!$valid) {
                    $errors[$field][] = $rule;
                }

            }

        }

        return $errors;
    }

}
