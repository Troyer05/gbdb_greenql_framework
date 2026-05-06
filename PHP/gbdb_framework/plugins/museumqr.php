<?php

/**
 * @author Markus Müller
 *
 * MuseumQR API Anbindung
 * API Dokumentation: https://museumqr.de/api_doc.html
 */

class MqrApi {
    /**
     * Verarbeitet die Funktion base.
     * @return array Rückgabewert.
     */
    private static function base(): array {
        return [
            "auth_key" => Vars::mqr_api_key()
        ];
    }

    /**
     * Verarbeitet die Funktion fetch.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    private static function fetch(array $data): array {
        $url = Vars::mqr_api_url();

        $payload = json_encode(array_merge(self::base(), $data), JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json",
                "Content-Length: " . strlen($payload)
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($resp === false || $resp === "") {
            return [
                "success" => false,
                "status" => $code,
                "error" => $err != "" ? $err : "Keine Antwort von der API"
            ];
        }

        $json = json_decode($resp, true);

        if (!is_array($json)) {
            return [
                "success" => false,
                "status" => $code,
                "error" => "Ungültige API Antwort",
                "raw" => $resp
            ];
        }

        if (!isset($json["status"])) {
            $json["status"] = $code;
        }

        return $json;
    }

    /**
     * Verarbeitet die Funktion get feedback.
     * @param string $item_id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getFeedback(string $item_id = ""): array {
        $data = [
            "get" => "feedback"
        ];

        if ($item_id != "") {
            $data["item_id"] = $item_id;
        }

        return self::fetch($data);
    }

    /**
     * Verarbeitet die Funktion get objects.
     * @return array Rückgabewert.
     */
    public static function getObjects(): array {
        return self::fetch([
            "get" => "object"
        ]);
    }

    /**
     * Verarbeitet die Funktion get object.
     * @param string $oid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getObject(string $oid): array {
        return self::fetch([
            "get" => "object",
            "oid" => $oid
        ]);
    }

    /**
     * Verarbeitet die Funktion get settings.
     * @return array Rückgabewert.
     */
    public static function getSettings(): array {
        return self::fetch([
            "get" => "settings"
        ]);
    }

    /**
     * Verarbeitet die Funktion get langs.
     * @return array Rückgabewert.
     */
    public static function getLangs(): array {
        return self::fetch([
            "get" => "langs"
        ]);
    }

    /**
     * Verarbeitet die Funktion get tours.
     * @return array Rückgabewert.
     */
    public static function getTours(): array {
        return self::fetch([
            "get" => "tours"
        ]);
    }

    /**
     * Verarbeitet die Funktion new api key.
     * @param string $permission Übergabewert.
     * @return array Rückgabewert.
     */
    public static function newApiKey(string $permission = "readwrite"): array {
        return self::fetch([
            "write" => "api",
            "permission" => $permission
        ]);
    }

    /**
     * Verarbeitet die Funktion save settings.
     * @param string $theme Übergabewert.
     * @param string $name Übergabewert.
     * @param string $intro Übergabewert.
     * @return array Rückgabewert.
     */
    public static function saveSettings(string $theme, string $name, string $intro): array {
        return self::fetch([
            "write" => "settings",
            "theme" => $theme,
            "name" => $name,
            "intro" => $intro
        ]);
    }

    /**
     * Verarbeitet die Funktion new lang.
     * @param string $lid Übergabewert.
     * @param string $name Übergabewert.
     * @return array Rückgabewert.
     */
    public static function newLang(string $lid, string $name): array {
        return self::fetch([
            "write" => "langs",
            "lid" => $lid,
            "name" => $name
        ]);
    }

    /**
     * Verarbeitet die Funktion new tour.
     * @param string $name Übergabewert.
     * @return array Rückgabewert.
     */
    public static function newTour(string $name): array {
        return self::fetch([
            "write" => "tours",
            "name" => $name
        ]);
    }
}
