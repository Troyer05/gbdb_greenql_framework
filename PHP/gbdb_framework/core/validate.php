<?php

class Validate {
    /** Prüft, ob Felder gesetzt und nicht leer sind */
    /**
     * Verarbeitet die Funktion required.
     * @param array $data Übergabewert.
     * @param array $fields Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function required(array $data, array $fields): bool {
        foreach ($fields as $f) {
            if (!array_key_exists($f, $data)) {
                return false;
            }
            if (trim((string)$data[$f]) === '') {
                return false;
            }
        }

        return true;
    }

    /** Prüft, ob eine gültige E-Mail-Adresse übergeben wurde */
    /**
     * Verarbeitet die Funktion email.
     * @param string $value Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function email(string $value): bool {
        return (bool) filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    }

    /** Prüft, ob der Wert eine Zahl oder Kommazahl ist */
    /**
     * Verarbeitet die Funktion number.
     * @param string|int|float $value Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function number(string|int|float $value): bool {
        if (is_array($value)) return false;

        $v = str_replace(',', '.', trim((string)$value));

        return is_numeric($v);
    }

    /** Prüft Mindestlänge eines Strings */
    /**
     * Verarbeitet die Funktion min length.
     * @param string $value Übergabewert.
     * @param int $min Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function minLength(string $value, int $min): bool {
        return mb_strlen(trim($value)) >= $min;
    }

    /** Prüft Maximallänge eines Strings */
    /**
     * Verarbeitet die Funktion max length.
     * @param string $value Übergabewert.
     * @param int $max Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function maxLength(string $value, int $max): bool {
        return mb_strlen(trim($value)) <= $max;
    }

    /** Prüft, ob ein Wert einem regulären Ausdruck entspricht */
    /**
     * Verarbeitet die Funktion regex.
     * @param string $value Übergabewert.
     * @param string $pattern Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function regex(string $value, string $pattern): bool {
        // Sicherheitsmaßnahme: leere oder invalide Patterns verhindern
        if (@preg_match($pattern, $value) === false) {
            return false;
        }

        return (bool) @preg_match($pattern, $value);
    }

    /** Prüft, ob eine Zahl zwischen zwei Werten liegt */
    /**
     * Verarbeitet die Funktion between.
     * @param float|int $value Übergabewert.
     * @param float|int $min Übergabewert.
     * @param float|int $max Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function between(float|int $value, float|int $min, float|int $max): bool {
        if (!is_numeric($value)) return false;
        return $value >= $min && $value <= $max;
    }

    /** Prüft, ob ein Wert in einer erlaubten Liste vorkommt */
    /**
     * Verarbeitet die Funktion in.
     * @param string|int $value Übergabewert.
     * @param array $allowed Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function in(string|int $value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }

    /** Prüft, ob zwei Strings exakt gleich sind */
    /**
     * Verarbeitet die Funktion match.
     * @param string $a Übergabewert.
     * @param string $b Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function match(string $a, string $b): bool {
        return hash_equals((string)$a, (string)$b);
    }

    /**
     * Prüft ein komplettes Datenarray anhand definierter Regeln
     *
     * Beispiel:
     * Validate::validateArray($_POST, [
     *     'email' => 'required|email',
     *     'password' => 'required|min:8|max:32'
     * ]);
     *
     * Output:
     * [
     *   "email" => ["required", "email"],
     *   "password" => ["min"]
     * ]
     */
    public static function validateArray(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleString) {

            // Wert aus Daten holen
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $param = null;

                // Parameter extrahieren (z.B. min:8)
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $valid = true;

                switch ($rule) {
                    case 'required':
                        $valid = !is_null($value) && trim((string)$value) !== '';
                        break;

                    case 'email':
                        $valid = self::email((string)$value);
                        break;

                    case 'number':
                        $valid = self::number($value);
                        break;

                    case 'min':
                        $valid = self::minLength((string)$value, (int)$param);
                        break;

                    case 'max':
                        $valid = self::maxLength((string)$value, (int)$param);
                        break;

                    case 'regex':
                        $valid = self::regex((string)$value, (string)$param);
                        break;

                    case 'in':
                        $valid = self::in((string)$value, explode(',', (string)$param));
                        break;

                    case 'between':
                        // Param: "5,10"
                        if ($param !== null && str_contains($param, ',')) {
                            [$min, $max] = explode(',', $param, 2);
                            $valid = self::between((float)$value, (float)$min, (float)$max);
                        } else {
                            $valid = false;
                        }
                        break;

                    default:
                        // Unbekannte Regel → ignorieren
                        $valid = true;
                }

                if (!$valid) {
                    $errors[$field][] = $rule;
                }
            }
        }

        return $errors;
    }
}
