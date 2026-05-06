<?php

class Time {

    /**
     * Gibt wieder, wie lange ein Datum her ist (deutsche Ausgabe)
     */
    public static function timeAgo(mixed $timestamp): string {
        // Timestamp validieren
        $uploadedTime = strtotime((string)$timestamp);

        if ($uploadedTime === false) {
            return "Ungültiges Datum";
        }

        $diff = time() - $uploadedTime;

        if ($diff < 0) {
            return "in der Zukunft";
        }

        $seconds = $diff;
        $minutes = floor($seconds / 60);
        $hours   = floor($seconds / 3600);
        $days    = floor($seconds / 86400);
        $weeks   = floor($seconds / 604800);
        $months  = floor($seconds / 2629440);
        $years   = floor($seconds / 31553280);

        // Sekunden
        if ($seconds < 60) {
            return "vor $seconds Sekunden";
        }

        // Minuten
        if ($minutes < 60) {
            return $minutes === 1
                ? "vor einer Minute"
                : "vor $minutes Minuten";
        }

        // Stunden
        if ($hours < 24) {
            return $hours === 1
                ? "vor einer Stunde"
                : "vor $hours Stunden";
        }

        // Tage
        if ($days < 7) {
            return $days === 1
                ? "vor einem Tag"
                : "vor $days Tagen";
        }

        // Wochen
        if ($weeks < 5) {
            return $weeks === 1
                ? "vor einer Woche"
                : "vor $weeks Wochen";
        }

        // Monate
        if ($months < 12) {
            return $months === 1
                ? "vor einem Monat"
                : "vor $months Monaten";
        }

        // Jahre
        return $years === 1
            ? "vor einem Jahr"
            : "vor $years Jahren";
    }
}
