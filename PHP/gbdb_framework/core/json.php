<?php

class Json {

    /**
     * Dekodiert einen JSON-String in ein PHP-Array oder Objekt
     * Gibt bei Fehlern eine klare Fehlermeldung zurück.
     */
    public static function decode(string $json, bool $assoc = false): mixed {
        if ($json === "") {
            return null;
        }

        $decoded = json_decode($json, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[Json::decode] JSON Error: " . json_last_error_msg());
            return null;
        }

        return $decoded;
    }

    /**
     * Kodiert ein PHP-Array oder Objekt in einen JSON-String.
     * UTF-8 sicher, pretty-print im DEV Mode.
     */
    public static function encode(mixed $data, bool $pretty = false): string {
        $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($pretty || (class_exists('Vars') && Vars::json_pretty())) {
            $options |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($data, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[Json::encode] JSON Error: " . json_last_error_msg());
            return "";
        }

        return $json;
    }

    /**
     * Überprüft, ob eine Zeichenkette valides JSON ist.
     */
    public static function isJson(string $json): bool {
        if (!is_string($json) || trim($json) === "") return false;

        json_decode($json);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Wendet eine Callback-Funktion auf jedes Element eines Arrays oder Objekts an.
     */
    public static function loop(mixed $data, callable $callback): mixed {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $callback($value, $key);
            }
        }

        elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = $callback($value, $key);
            }
        }

        return $data;
    }

    /**
     * Überprüft, ob ein Schlüssel in Array/Objekt existiert.
     */
    public static function elementExists(mixed $data, string $key): bool {
        if (is_array($data)) {
            return array_key_exists($key, $data);
        }

        if (is_object($data)) {
            return property_exists($data, $key);
        }

        return false;
    }

    /**
     * Ruft ein Element aus Array/Objekt ab — oder null.
     */
    public static function getElement(mixed $data, string $key): mixed {
        if (!self::elementExists($data, $key)) {
            return null;
        }

        return is_array($data) ? $data[$key] : $data->$key;
    }
}
