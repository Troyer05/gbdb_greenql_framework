<?php

/**
 * @author Markus Müller
 *
 * greenbucket ShareSuite API Anbindung
 * API Doku: https://sharesuite.greenbucket.online/api_doc.php
 */

class ShareSuiteAPI {
    /**
     * Verarbeitet die Funktion base.
     * @return array Rückgabewert.
     */
    private static function base(): array {
        return [
            "sid" => Vars::sharesuite_sid(),
            "api_key" => Vars::sharesuite_api_key(),
            "auth_key" => Vars::sharesuite_api_auth()
        ];
    }

    /**
     * Verarbeitet die Funktion fetch.
     * @param string $method Übergabewert.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    private static function fetch(string $method, array $data): array {
        $url = Vars::sharesuite_api_url();

        $payload = json_encode(array_merge(self::base(), $data), JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
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
                "ok" => false,
                "status" => $code,
                "msg" => $err != "" ? $err : "Keine Antwort von der API"
            ];
        }

        $json = json_decode($resp, true);

        if (!is_array($json)) {
            return [
                "ok" => false,
                "status" => $code,
                "msg" => "Ungültige API Antwort",
                "raw" => $resp
            ];
        }

        if (!isset($json["status"])) {
            $json["status"] = $code;
        }

        return $json;
    }

    /**
     * Verarbeitet die Funktion get table.
     * @param string $tid Übergabewert.
     * @param string $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getTable(string $tid, string $id = ""): array {
        $data = [
            "dest" => "table",
            "tid" => $tid
        ];

        if ($id != "") {
            $data["id"] = $id;
        }

        return self::fetch("GET", $data);
    }

    /**
     * Verarbeitet die Funktion get table settings.
     * @param string $tid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getTableSettings(string $tid): array {
        return self::fetch("GET", [
            "dest" => "table_settings",
            "tid" => $tid
        ]);
    }

    /**
     * Verarbeitet die Funktion get table index.
     * @return array Rückgabewert.
     */
    public static function getTableIndex(): array {
        return self::fetch("GET", [
            "dest" => "table_index"
        ]);
    }

    /**
     * Verarbeitet die Funktion get calendar.
     * @param string $kid Übergabewert.
     * @param string $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getCalendar(string $kid, string $id = ""): array {
        $data = [
            "dest" => "calendar",
            "kid" => $kid
        ];

        if ($id != "") {
            $data["id"] = $id;
        }

        return self::fetch("GET", $data);
    }

    /**
     * Verarbeitet die Funktion get bib.
     * @param string $bid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getBib(string $bid = ""): array {
        $data = [
            "dest" => "bib"
        ];

        if ($bid != "") {
            $data["bid"] = $bid;
        }

        return self::fetch("GET", $data);
    }

    /**
     * Verarbeitet die Funktion get blogs.
     * @param string $bid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getBlogs(string $bid = ""): array {
        $data = [
            "dest" => "blogs"
        ];

        if ($bid != "") {
            $data["bid"] = $bid;
        }

        return self::fetch("GET", $data);
    }

    /**
     * Verarbeitet die Funktion get tickets.
     * @param string $tid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getTickets(string $tid = ""): array {
        $data = [
            "dest" => "tickets"
        ];

        if ($tid != "") {
            $data["tid"] = $tid;
        }

        return self::fetch("GET", $data);
    }

    /**
     * Verarbeitet die Funktion new table entry.
     * @param string $tid Übergabewert.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    public static function newTableEntry(string $tid, array $data): array {
        return self::fetch("POST", [
            "dest" => "table",
            "tid" => $tid,
            "data" => $data
        ]);
    }

    /**
     * Verarbeitet die Funktion new calendar entry.
     * @param string $kid Übergabewert.
     * @param string $titel Übergabewert.
     * @param string $von Übergabewert.
     * @param string $bis Übergabewert.
     * @param string $text Übergabewert.
     * @return array Rückgabewert.
     */
    public static function newCalendarEntry(string $kid, string $titel, string $von, string $bis, string $text = ""): array {
        return self::fetch("POST", [
            "dest" => "calendar",
            "kid" => $kid,
            "data" => [
                "titel" => $titel,
                "von" => $von,
                "bis" => $bis,
                "text" => $text
            ]
        ]);
    }

    /**
     * Verarbeitet die Funktion new blog.
     * @param string $user Übergabewert.
     * @param string $user_auth Übergabewert.
     * @param string $title Übergabewert.
     * @param string $text Übergabewert.
     * @return array Rückgabewert.
     */
    public static function newBlog(string $user, string $user_auth, string $title, string $text): array {
        return self::fetch("POST", [
            "dest" => "blogs",
            "user" => $user,
            "user_auth" => $user_auth,
            "title" => $title,
            "text" => $text
        ]);
    }

    /**
     * Verarbeitet die Funktion edit table entry.
     * @param string $tid Übergabewert.
     * @param string $id Übergabewert.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editTableEntry(string $tid, string $id, array $data): array {
        return self::fetch("PUT", [
            "dest" => "table",
            "tid" => $tid,
            "id" => $id,
            "data" => $data
        ]);
    }

    /**
     * Verarbeitet die Funktion edit calendar entry.
     * @param string $kid Übergabewert.
     * @param string $id Übergabewert.
     * @param string $titel Übergabewert.
     * @param string $von Übergabewert.
     * @param string $bis Übergabewert.
     * @param string $text Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editCalendarEntry(string $kid, string $id, string $titel, string $von, string $bis, string $text = ""): array {
        return self::fetch("PUT", [
            "dest" => "calendar",
            "kid" => $kid,
            "id" => $id,
            "data" => [
                "titel" => $titel,
                "von" => $von,
                "bis" => $bis,
                "text" => $text
            ]
        ]);
    }

    /**
     * Verarbeitet die Funktion edit blog.
     * @param string $id Übergabewert.
     * @param string $user Übergabewert.
     * @param string $user_auth Übergabewert.
     * @param string $title Übergabewert.
     * @param string $text Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editBlog(string $id, string $user, string $user_auth, string $title, string $text): array {
        return self::fetch("PUT", [
            "dest" => "blogs",
            "id" => $id,
            "user" => $user,
            "user_auth" => $user_auth,
            "title" => $title,
            "text" => $text
        ]);
    }

    /**
     * Verarbeitet die Funktion edit bib.
     * @param string $id Übergabewert.
     * @param string $name Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editBib(string $id, string $name): array {
        return self::fetch("PUT", [
            "dest" => "bib",
            "id" => $id,
            "name" => $name
        ]);
    }

    /**
     * Verarbeitet die Funktion edit ticket.
     * @param string $id Übergabewert.
     * @param string $status Übergabewert.
     * @param string $reply Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editTicket(string $id, string $status, string $reply = ""): array {
        $data = [
            "dest" => "ticket",
            "id" => $id,
            "status" => $status
        ];

        if ($reply != "") {
            $data["reply"] = $reply;
        }

        return self::fetch("PUT", $data);
    }

    /**
     * Verarbeitet die Funktion delete table entry.
     * @param string $tid Übergabewert.
     * @param string $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteTableEntry(string $tid, string $id): array {
        return self::fetch("DELETE", [
            "dest" => "table",
            "tid" => $tid,
            "id" => $id
        ]);
    }

    /**
     * Verarbeitet die Funktion delete calendar entry.
     * @param string $kid Übergabewert.
     * @param string $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteCalendarEntry(string $kid, string $id): array {
        return self::fetch("DELETE", [
            "dest" => "calendar",
            "kid" => $kid,
            "id" => $id
        ]);
    }

    /**
     * Verarbeitet die Funktion delete blog.
     * @param string $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteBlog(string $id): array {
        return self::fetch("DELETE", [
            "dest" => "blogs",
            "id" => $id
        ]);
    }

    /**
     * Verarbeitet die Funktion delete bib.
     * @param string $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteBib(string $id): array {
        return self::fetch("DELETE", [
            "dest" => "bib",
            "id" => $id
        ]);
    }
}
