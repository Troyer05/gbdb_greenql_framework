<?php

class Format {
    private static function validDate(mixed $value): bool {
        if (empty($value)) return false;

        $ts = strtotime((string)$value);

        return $ts !== false && $ts > 0;
    }

    /**
     * Formats a date perfectly for a input-tag
     * @param mixed $date
     * @return string
     */
    public static function dateForInput(mixed $date): string {
        if (!self::validDate($date)) return "";

        return date('Y-m-d', strtotime((string)$date));
    }

    /**
     * formats a time perfectly for a input-tag
     * @param mixed $time
     * @return string
     */
    public static function timeForInput(mixed $time): string {
        if (!self::validDate($time)) return "";

        return date('H:i:s', strtotime((string)$time));
    }

    /**
     * converts a date to german date for displaying
     * @param mixed $date
     * @return string
     */
    public static function dateToView(mixed $date): string {
        if (!self::validDate($date)) return "";

        return date('d.m.Y', strtotime((string)$date));
    }

    /**
     * cuts a string and adds 3 dots to it at the cut
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    public static function shortString(
        string $string,
        int $maxLength = 14
    ): string {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        $cut = $maxLength - 3;

        if ($cut < 1) $cut = 1;

        return substr($string, 0, $cut) . "...";
    }

    /**
     * cleans a string from unknown characters
     * @param string $string
     * @return array|string|null
     */
    public static function cleanString(string $string): string {
        return preg_replace("/[^a-zA-Z0-9_\-äöüÄÖÜß]/u", "", $string);
    }

    /**
     * converts strings from \n to <br> and from <br> to \n -> supports \n \r\n \n\¶
     * @param string $string
     * @param bool $forHtml
     * @return array|string
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
