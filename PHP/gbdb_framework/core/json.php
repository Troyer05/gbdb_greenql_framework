<?php

class Json {

    /**
     * decodes json for php usage
     * @param string $json
     * @param bool $assoc
     * @return mixed
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
     * decodes php object/array to valid json
     * @param mixed $data
     * @param bool $pretty
     * @return bool|string
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
     * is it valid json?
     * @param string $json
     * @return bool
     */
    public static function isJson(string $json): bool {
        if (!is_string($json) || trim($json) === "") return false;

        json_decode($json);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * loops through a array/object
     * @param mixed $data
     * @param callable $callback
     * @return mixed
     */
    public static function loop(mixed $data, callable $callback): mixed {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $callback($value, $key);
            }

        } else if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = $callback($value, $key);
            }

        }

        return $data;
    }

    /**
     * checks if a element exists
     * @param mixed $data
     * @param string $key
     * @return bool
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
     * gets an element of a json
     * @param mixed $data
     * @param string $key
     * @return mixed
     */
    public static function getElement(mixed $data, string $key): mixed {
        if (!self::elementExists($data, $key)) {
            return null;
        }

        return is_array($data) ? $data[$key] : $data->$key;
    }

}
