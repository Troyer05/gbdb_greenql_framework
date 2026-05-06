<?php

/**
 * @author Markus Müller
 *
 * EventQR API Bindung
 * Doku URL:
 */
class EqrAPI {
    private static string $api_url = "";
    private static string $auth = "";
    private static int $timeout = 15;
    private static bool $return_raw = false;

    /**
     * Setzt die API URL manuell.
     * @param string $url API URL.
     * @return void
     */
    public static function setUrl(string $url): void {
        self::$api_url = trim($url);
    }

    /**
     * Setzt den API Auth Token manuell.
     * @param string $auth API Auth Token.
     * @return void
     */
    public static function setAuth(string $auth): void {
        self::$auth = trim($auth);
    }

    /**
     * Setzt das Request Timeout.
     * @param int $seconds Timeout in Sekunden.
     * @return void
     */
    public static function setTimeout(int $seconds): void {
        self::$timeout = max(1, $seconds);
    }

    /**
     * Schaltet Rohantworten an oder aus.
     * @param bool $state Status.
     * @return void
     */
    public static function returnRaw(bool $state): void {
        self::$return_raw = $state;
    }

    /**
     * Gibt den Standard Auth Token zurück.
     * @return string Rückgabewert.
     */
    public static function auth(): string {
        if (self::$auth !== "") {
            return self::$auth;
        }

        if (method_exists("Vars", "EQR_API_AUTH")) {
            $auth = trim((string)Vars::EQR_API_AUTH());

            if ($auth !== "") {
                return $auth;
            }
        }

        return hash("sha256", Vars::srvp_static_key() . "|" . Vars::mRoot_pid());
    }

    /**
     * Gibt die API URL zurück.
     * @return string Rückgabewert.
     */
    public static function url(): string {
        if (self::$api_url !== "") {
            return self::$api_url;
        }

        if (method_exists("Vars", "EQR_API_URL")) {
            $url = trim((string)Vars::EQR_API_URL());

            if ($url !== "") {
                return $url;
            }
        }

        if (method_exists("Vars", "eventqr_api_url")) {
            $url = trim((string)Vars::eventqr_api_url());

            if ($url !== "") {
                return $url;
            }
        }

        $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
        $script = $_SERVER["SCRIPT_NAME"] ?? "/api.php";
        $dir = rtrim(str_replace("\\", "/", dirname($script)), "/");

        if ($dir === "." || $dir === "/") {
            $dir = "";
        }

        return $scheme . "://" . $host . $dir . "/api.php";
    }

    /**
     * Baut den Request Body.
     * @param string $do API Action.
     * @param array $body Top-Level Body.
     * @param array $data Data Payload.
     * @return array Rückgabewert.
     */
    private static function body(string $do, array $body = [], array $data = []): array {
        $req = array_merge([
            "auth" => self::auth(),
            "do" => $do
        ], $body);

        if (!empty($data)) {
            $req["data"] = $data;
        }

        return $req;
    }

    /**
     * Führt einen API Request aus.
     * @param string $do API Action.
     * @param array $body Top-Level Body.
     * @param array $data Data Payload.
     * @return array Rückgabewert.
     */
    public static function request(string $do, array $body = [], array $data = []): array {
        $url = self::url();
        $payload = self::body($do, $body, $data);

        $resp = Http::post($url, $payload, [], self::$timeout);

        if ($resp === false || $resp === "") {
            return [
                "ok" => false,
                "status" => 0,
                "data" => [
                    "msg" => "Keine Antwort von EventQR API",
                    "url" => $url
                ]
            ];
        }

        if (is_array($resp)) {
            return $resp;
        }

        $json = json_decode((string)$resp, true);

        if (!is_array($json)) {
            return [
                "ok" => false,
                "status" => 0,
                "data" => [
                    "msg" => "Ungültige JSON Antwort",
                    "raw" => self::$return_raw ? $resp : mb_substr((string)$resp, 0, 500)
                ]
            ];
        }

        if (self::$return_raw) {
            $json["_raw"] = $resp;
        }

        return $json;
    }

    /**
     * Gibt nur data zurück, wenn der Request erfolgreich war.
     * @param string $do API Action.
     * @param array $body Top-Level Body.
     * @param array $data Data Payload.
     * @return mixed Rückgabewert.
     */
    public static function data(string $do, array $body = [], array $data = []): mixed {
        $res = self::request($do, $body, $data);

        if (!($res["ok"] ?? false)) {
            return [];
        }

        return $res["data"] ?? [];
    }

    /**
     * Prüft die Verbindung.
     * @return array Rückgabewert.
     */
    public static function ping(): array {
        return self::request("ping");
    }

    /**
     * Holt die API Dokumentation.
     * @return array Rückgabewert.
     */
    public static function docs(): array {
        return self::request("docs");
    }

    /**
     * Holt globale Einstellungen.
     * @return array Rückgabewert.
     */
    public static function settings(): array {
        return self::request("settings.get");
    }

    /**
     * Speichert globale Einstellungen.
     * @param array $data Einstellungsdaten.
     * @return array Rückgabewert.
     */
    public static function updateSettings(array $data): array {
        return self::request("settings.update", [], $data);
    }

    /**
     * Holt alle Events.
     * @return array Rückgabewert.
     */
    public static function events(): array {
        return self::request("events.list");
    }

    /**
     * Holt ein Event.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function event(string $eid): array {
        return self::request("events.get", ["eid" => $eid]);
    }

    /**
     * Erstellt ein Event.
     * @param array $data Eventdaten.
     * @return array Rückgabewert.
     */
    public static function createEvent(array $data): array {
        return self::request("events.create", [], $data);
    }

    /**
     * Speichert ein Event.
     * @param string $eid Event-ID.
     * @param array $data Eventdaten.
     * @return array Rückgabewert.
     */
    public static function updateEvent(string $eid, array $data): array {
        return self::request("events.update", ["eid" => $eid], $data);
    }

    /**
     * Löscht ein Event.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function deleteEvent(string $eid): array {
        return self::request("events.delete", ["eid" => $eid]);
    }

    /**
     * Holt Event Einstellungen.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function eventSettings(string $eid): array {
        return self::request("events.settings.get", ["eid" => $eid]);
    }

    /**
     * Speichert Event Einstellungen.
     * @param string $eid Event-ID.
     * @param array $data Einstellungsdaten.
     * @return array Rückgabewert.
     */
    public static function updateEventSettings(string $eid, array $data): array {
        return self::request("events.settings.update", ["eid" => $eid], $data);
    }

    /**
     * Berechnet Event Meta Daten neu.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function refreshMeta(string $eid): array {
        return self::request("events.meta.refresh", ["eid" => $eid]);
    }

    /**
     * Holt alle Tickets.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function tickets(string $eid): array {
        return self::request("tickets.list", ["eid" => $eid]);
    }

    /**
     * Holt ein Ticket.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @return array Rückgabewert.
     */
    public static function ticket(string $eid, string $tid): array {
        return self::request("tickets.get", ["eid" => $eid, "tid" => $tid]);
    }

    /**
     * Erstellt ein Ticket.
     * @param string $eid Event-ID.
     * @param array $data Ticketdaten.
     * @return array Rückgabewert.
     */
    public static function createTicket(string $eid, array $data): array {
        return self::request("tickets.create", ["eid" => $eid], $data);
    }

    /**
     * Speichert ein Ticket.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @param array $data Ticketdaten.
     * @return array Rückgabewert.
     */
    public static function updateTicket(string $eid, string $tid, array $data): array {
        return self::request("tickets.update", ["eid" => $eid, "tid" => $tid], $data);
    }

    /**
     * Löscht ein Ticket.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @return array Rückgabewert.
     */
    public static function deleteTicket(string $eid, string $tid): array {
        return self::request("tickets.delete", ["eid" => $eid, "tid" => $tid]);
    }

    /**
     * Setzt den Ticketstatus.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @param int|string $status Status.
     * @return array Rückgabewert.
     */
    public static function setTicketStatus(string $eid, string $tid, int|string $status): array {
        return self::request("tickets.status", ["eid" => $eid, "tid" => $tid], [
            "status" => $status
        ]);
    }

    /**
     * Scannt ein Ticket.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID oder QR-Link.
     * @param string $scanner Scanner Name.
     * @return array Rückgabewert.
     */
    public static function scanTicket(string $eid, string $tid, string $scanner = "framework"): array {
        return self::request("tickets.scan", [
            "eid" => $eid,
            "tid" => $tid,
            "scanner" => $scanner
        ]);
    }

    /**
     * Holt die Ticket URL.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @return array Rückgabewert.
     */
    public static function ticketUrl(string $eid, string $tid): array {
        return self::request("tickets.url", [
            "eid" => $eid,
            "tid" => $tid
        ]);
    }

    /**
     * Prüft ob ein Ticket bezahlt ist.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @return array Rückgabewert.
     */
    public static function ticketPaid(string $eid, string $tid): array {
        return self::request("tickets.paid", [
            "eid" => $eid,
            "tid" => $tid
        ]);
    }

    /**
     * Lädt ein Ticket Wallet auf.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @param float|int|string $betrag Betrag.
     * @param int|string $paymentStatus Payment Status.
     * @return array Rückgabewert.
     */
    public static function addWallet(string $eid, string $tid, float|int|string $betrag, int|string $paymentStatus = 1): array {
        return self::request("tickets.wallet.add", [
            "eid" => $eid,
            "tid" => $tid
        ], [
            "betrag" => $betrag,
            "payment_status" => $paymentStatus
        ]);
    }

    /**
     * Holt Tickettypen.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function ticketTypes(string $eid): array {
        return self::request("tickettypes.list", ["eid" => $eid]);
    }

    /**
     * Holt Ticketstatus.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function ticketStates(string $eid): array {
        return self::request("ticketstates.list", ["eid" => $eid]);
    }

    /**
     * Holt Paymenttypen.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function paymentTypes(string $eid): array {
        return self::request("paymenttypes.list", ["eid" => $eid]);
    }

    /**
     * Holt Paymentstatus.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function paymentStates(string $eid): array {
        return self::request("paymentstates.list", ["eid" => $eid]);
    }

    /**
     * Holt alle Payments.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function payments(string $eid): array {
        return self::request("payments.list", ["eid" => $eid]);
    }

    /**
     * Holt ein Payment.
     * @param string $eid Event-ID.
     * @param string $pid Payment-ID.
     * @return array Rückgabewert.
     */
    public static function payment(string $eid, string $pid): array {
        return self::request("payments.get", [
            "eid" => $eid,
            "pid" => $pid
        ]);
    }

    /**
     * Erstellt ein Payment.
     * @param string $eid Event-ID.
     * @param array $data Paymentdaten.
     * @return array Rückgabewert.
     */
    public static function createPayment(string $eid, array $data): array {
        return self::request("payments.create", ["eid" => $eid], $data);
    }

    /**
     * Markiert ein Payment als bezahlt.
     * @param string $eid Event-ID.
     * @param string $pid Payment-ID.
     * @param string $orderId Externe Order-ID.
     * @return array Rückgabewert.
     */
    public static function markPaymentPaid(string $eid, string $pid, string $orderId = ""): array {
        return self::request("payments.mark_paid", [
            "eid" => $eid,
            "pid" => $pid,
            "order_id" => $orderId
        ]);
    }

    /**
     * Holt alle Produkte.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function products(string $eid): array {
        return self::request("products.list", ["eid" => $eid]);
    }

    /**
     * Holt ein Produkt.
     * @param string $eid Event-ID.
     * @param string $bid Produkt-ID.
     * @return array Rückgabewert.
     */
    public static function product(string $eid, string $bid): array {
        return self::request("products.get", [
            "eid" => $eid,
            "bid" => $bid
        ]);
    }

    /**
     * Erstellt ein Produkt.
     * @param string $eid Event-ID.
     * @param array $data Produktdaten.
     * @return array Rückgabewert.
     */
    public static function createProduct(string $eid, array $data): array {
        return self::request("products.create", ["eid" => $eid], $data);
    }

    /**
     * Speichert ein Produkt.
     * @param string $eid Event-ID.
     * @param string $bid Produkt-ID.
     * @param array $data Produktdaten.
     * @return array Rückgabewert.
     */
    public static function updateProduct(string $eid, string $bid, array $data): array {
        return self::request("products.update", [
            "eid" => $eid,
            "bid" => $bid
        ], $data);
    }

    /**
     * Löscht ein Produkt.
     * @param string $eid Event-ID.
     * @param string $bid Produkt-ID.
     * @return array Rückgabewert.
     */
    public static function deleteProduct(string $eid, string $bid): array {
        return self::request("products.delete", [
            "eid" => $eid,
            "bid" => $bid
        ]);
    }

    /**
     * Verkauft ein Produkt über das Ticket Wallet.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @param string $bid Produkt-ID.
     * @param int $menge Menge.
     * @return array Rückgabewert.
     */
    public static function sellProduct(string $eid, string $tid, string $bid, int $menge = 1): array {
        return self::request("products.sell", [
            "eid" => $eid,
            "tid" => $tid
        ], [
            "bid" => $bid,
            "menge" => $menge
        ]);
    }

    /**
     * Holt alle Warenkörbe.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function carts(string $eid): array {
        return self::request("carts.list", ["eid" => $eid]);
    }

    /**
     * Holt alle Warenkorb-Items.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function cartItems(string $eid): array {
        return self::request("carts.items", ["eid" => $eid]);
    }

    /**
     * Erstellt einen Warenkorb.
     * @param string $eid Event-ID.
     * @param string $tid Ticket-ID.
     * @return array Rückgabewert.
     */
    public static function createCart(string $eid, string $tid): array {
        return self::request("carts.create", [
            "eid" => $eid,
            "tid" => $tid
        ]);
    }

    /**
     * Fügt einem Warenkorb einen Artikel hinzu.
     * @param string $eid Event-ID.
     * @param string $cid Warenkorb-ID.
     * @param string $bid Produkt-ID.
     * @param int $menge Menge.
     * @return array Rückgabewert.
     */
    public static function addCartItem(string $eid, string $cid, string $bid, int $menge = 1): array {
        return self::request("carts.add_item", ["eid" => $eid], [
            "cid" => $cid,
            "bid" => $bid,
            "menge" => $menge
        ]);
    }

    /**
     * Holt die Warenkorb-Summe.
     * @param string $eid Event-ID.
     * @param string $cid Warenkorb-ID.
     * @return array Rückgabewert.
     */
    public static function cartSum(string $eid, string $cid): array {
        return self::request("carts.sum", [
            "eid" => $eid,
            "cid" => $cid
        ]);
    }

    /**
     * Schließt einen Warenkorb ab.
     * @param string $eid Event-ID.
     * @param string $cid Warenkorb-ID.
     * @return array Rückgabewert.
     */
    public static function checkoutCart(string $eid, string $cid): array {
        return self::request("carts.checkout", [
            "eid" => $eid,
            "cid" => $cid
        ]);
    }

    /**
     * Holt Checkins.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function checkins(string $eid): array {
        return self::request("checkins.list", ["eid" => $eid]);
    }

    /**
     * Holt die komplette Event Übersicht.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function stats(string $eid): array {
        return self::request("stats.overview", ["eid" => $eid]);
    }

    /**
     * Holt Payment Statistiken.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function paymentStats(string $eid): array {
        return self::request("stats.payments", ["eid" => $eid]);
    }

    /**
     * Holt Checkin Statistiken.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function checkinStats(string $eid): array {
        return self::request("stats.checkins", ["eid" => $eid]);
    }

    /**
     * Holt Kapazitätsdaten.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function capacityStats(string $eid): array {
        return self::request("stats.capacity", ["eid" => $eid]);
    }

    /**
     * Holt Produkt Statistiken.
     * @param string $eid Event-ID.
     * @param int $limit Limit.
     * @return array Rückgabewert.
     */
    public static function productStats(string $eid, int $limit = 5): array {
        return self::request("stats.products", [
            "eid" => $eid,
            "limit" => $limit
        ]);
    }

    /**
     * Holt Warenkorb Statistiken.
     * @param string $eid Event-ID.
     * @return array Rückgabewert.
     */
    public static function cartStats(string $eid): array {
        return self::request("stats.carts", ["eid" => $eid]);
    }
}
