<?php

class Format {
    /**
     * Prüft ob ein Datum valide parsebar ist
     */
    private static function validDate(mixed $value): bool {
        if (empty($value)) return false;

        $ts = strtotime((string)$value);
        return $ts !== false && $ts > 0;
    }

    /**
     * Formatiert ein Datum für HTML <input type="date">
     */
    public static function dateForInput(mixed $date): string {
        if (!self::validDate($date)) return "";
        return date('Y-m-d', strtotime((string)$date));
    }

    /**
     * Formatiert eine Zeit für HTML <input type="time">
     */
    public static function timeForInput(mixed $time): string {
        if (!self::validDate($time)) return "";
        return date('H:i:s', strtotime((string)$time));
    }

    /**
     * Formatiert ein Datum für User-Anzeige (dd.mm.yyyy)
     */
    public static function dateToView(mixed $date): string {
        if (!self::validDate($date)) return "";
        return date('d.m.Y', strtotime((string)$date));
    }

    /**
     * Schneidet String sauber ab
     */
    public static function shortString(
        string $string,
        int $maxLength = 14
    ): string {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        // 3 dots, so we remove 3 chars from content
        $cut = $maxLength - 3;
        if ($cut < 1) $cut = 1;

        return substr($string, 0, $cut) . "...";
    }

    /**
     * Entfernt unsichere Zeichen (DE-kompatibel)
     * Lässt äöüÄÖÜß sowie _ und - für Framework-Namen zu
     */
    public static function cleanString(string $string): string {
        return preg_replace("/[^a-zA-Z0-9_\-äöüÄÖÜß]/u", "", $string);
    }

    /**
     * Konvertiert zwischen Text-Input und HTML <br>
     */
    public static function newLineCode(string $string, bool $forHtml = true): string {
        if ($forHtml) {
            // alle HTML break Varianten unterstützen
            $string = str_replace(["\r\n", "\n\r", "\n"], "<br>", $string);
            return $string;
        }

        // HTML → Text
        return str_ireplace(["<br>", "<br/>", "<br />"], "\r\n", $string);
    }
}
