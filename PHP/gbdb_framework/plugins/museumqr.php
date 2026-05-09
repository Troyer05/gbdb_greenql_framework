<?php

/**
 * @author Markus Müller
 *
 * MuseumQR API connector
 * API Dokumentation: https://museumqr.de/api_doc.html
 *
 * gets bigger with new MuseumQR updates
 */

class MqrApi {
    private static function base(): array {
        return [
            "auth_key" => Vars::mqr_api_key()
        ];
    }

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
     * gets feedback
     * @param string $item_id
     * @return array
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
     * gets objects
     * @return array
     */
    public static function getObjects(): array {
        return self::fetch([
            "get" => "object"
        ]);
    }

    /**
     * get specific object
     * @param string $oid
     * @return array
     */
    public static function getObject(string $oid): array {
        return self::fetch([
            "get" => "object",
            "oid" => $oid
        ]);
    }

    /**
     * get settings
     * @return array
     */
    public static function getSettings(): array {
        return self::fetch([
            "get" => "settings"
        ]);
    }

    /**
     * get languages
     * @return array
     */
    public static function getLangs(): array {
        return self::fetch([
            "get" => "langs"
        ]);
    }

    /**
     * get tours
     * @return array
     */
    public static function getTours(): array {
        return self::fetch([
            "get" => "tours"
        ]);
    }

}
