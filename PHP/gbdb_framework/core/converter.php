<?php

class Converter {
    /**
     * Normalisiert eine Eingabe:
     *  - Entfernt Leerzeichen, Währungssymbole, Prozentzeichen
     *  - Deutsch → Englisch Komma zu Punkt
     *  - Gibt Float zurück
     */
    protected static function normalize(string|float|int $value): float {
        // In String wandeln
        $value = (string)$value;

        // Entferne Leerzeichen
        $value = trim($value);

        // Entferne typische unerwünschte Zeichen
        $value = str_replace(['€', '%', ' '], '', $value);

        // Deutsches Komma → Punkt
        $value = str_replace(',', '.', $value);

        // Falls kein valider Numerischer Wert → 0.0
        if (!is_numeric($value)) {
            return 0.0;
        }

        return (float)$value;
    }

    /**
     * Addiert bzw. multipliziert zwei Kommazahlen.
     * Rückgabe: deutsche Formatierung (z. B. "123,45")
     */
    public static function getSumme(int|float|string $p, int|float $a): string {
        $p      = self::normalize($p);
        $result = $p * $a;

        // Deutsche Formatierung: 2 Nachkommastellen, Komma, keine Tausenderpunkte
        return number_format($result, 2, ',', '');
    }

    /**
     * Konvertiert eine Kommazahl zu einer Ganzzahl (abschneiden, kein Runden)
     */
    public static function convertToNumber(int|float|string $x): int {
        $x = self::normalize($x);
        return (int) floor($x);
    }

    /**
     * Konvertiert eine deutsche Kommazahl zu float
     */
    public static function toFloat(string|float|int $value): float {
        return self::normalize($value);
    }
}
