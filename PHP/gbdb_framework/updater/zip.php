<?php

declare(strict_types=1);

/**
 * ZIP lesen ohne PHP-Erweiterung zip (viele Webspaces haben sie nicht).
 *
 * - Methoden „gespeichert“ (0) und „Deflate“ (8, zlib); Prüfung von CRC und Größe je Eintrag.
 * - Abgewiesen: Verschlüsselung, ZIP64, mehrteilige Archive, symbolische Links, doppelte Einträge,
 *   Pfade mit „..“, absolute Pfade, Laufwerksbuchstaben, Steuerzeichen und Backslashes.
 * - ZIP-Bomben: Grenzen für Eintragszahl, Eintragsgröße, Gesamtgröße und Kompressionsrate; beim
 *   Entpacken wird nie mehr als die angegebene Größe erzeugt.
 *
 * Diese Datei ist identisch im Release-Manager enthalten (lib/shared).
 */
final class GBDBUpdateZip {
    public const LIMITS = ['entries' => 20000, 'entry_bytes' => 67108864, 'total_bytes' => 314572800, 'ratio' => 250];

    private function __construct() {
    }

    /**
     * Einträge aus dem zentralen Verzeichnis (ohne Ordner).
     *
     * @param array{entries?: int, entry_bytes?: int, total_bytes?: int, ratio?: int} $limits
     * @return list<array{name: string, size: int, compressed: int, method: int, crc: int, offset: int, mtime: int}>
     */
    public static function entries(string $file, array $limits = []) :array {
        $limits += self::LIMITS;
        $handle = self::open($file);

        try {
            $size = (int) fstat($handle)['size'];
            $tail = min($size, 65557);
            fseek($handle, $size - $tail);
            $end = (string) fread($handle, $tail);
            $position = strrpos($end, "PK\x05\x06");

            if ($position === false || strlen($end) - $position < 22) {
                throw new InvalidArgumentException('Die Datei ist kein gültiges ZIP-Archiv.');
            }

            $eocd = unpack('vdisk/vstartDisk/ventriesDisk/ventries/Vsize/Voffset/vcommentLength', substr($end, $position + 4, 18));

            if ($eocd === false || $eocd['disk'] !== 0 || $eocd['startDisk'] !== 0 || $eocd['entries'] === 0xFFFF || $eocd['offset'] === 0xFFFFFFFF || $eocd['size'] === 0xFFFFFFFF) {
                throw new InvalidArgumentException('Mehrteilige oder ZIP64-Archive werden nicht unterstützt.');
            }

            if ($eocd['entries'] > $limits['entries']) {
                throw new InvalidArgumentException('Das Archiv enthält mehr als ' . $limits['entries'] . ' Einträge.');
            }

            if ($eocd['offset'] + $eocd['size'] > $size) {
                throw new InvalidArgumentException('Das Inhaltsverzeichnis des Archivs ist beschädigt.');
            }

            fseek($handle, $eocd['offset']);
            $directory = $eocd['size'] > 0 ? (string) fread($handle, $eocd['size']) : '';
        } finally {
            fclose($handle);
        }

        $entries = [];
        $names = [];
        $folded = [];
        $folders = [];
        $total = 0;
        $pointer = 0;

        for ($index = 0; $index < $eocd['entries']; $index++) {
            if (substr($directory, $pointer, 4) !== "PK\x01\x02") {
                throw new InvalidArgumentException('Das Inhaltsverzeichnis des Archivs ist beschädigt.');
            }

            $header = unpack('vmadeBy/vneeded/vflags/vmethod/vtime/vdate/Vcrc/VcompSize/Vsize/vnameLength/vextraLength/vcommentLength/vdisk/vinternal/Vexternal/Voffset', substr($directory, $pointer + 4, 42));

            if ($header === false) {
                throw new InvalidArgumentException('Das Inhaltsverzeichnis des Archivs ist beschädigt.');
            }

            $name = substr($directory, $pointer + 46, $header['nameLength']);
            $pointer += 46 + $header['nameLength'] + $header['extraLength'] + $header['commentLength'];

            if (($header['madeBy'] >> 8) === 3 && (($header['external'] >> 16) & 0xF000) === 0xA000) {
                throw new InvalidArgumentException('Symbolische Links sind im Archiv nicht erlaubt: ' . self::shortName($name));
            }

            if (str_ends_with($name, '/')) {
                self::checkPath(rtrim($name, '/'));
                $folders[strtolower(rtrim($name, '/'))] = true;

                continue;
            }

            self::checkPath($name);

            if (isset($names[$name]) || isset($folded[strtolower($name)])) {
                throw new InvalidArgumentException('Doppelter Eintrag im Archiv: ' . self::shortName($name));
            }

            $names[$name] = true;
            $folded[strtolower($name)] = true;

            for ($parent = dirname($name); $parent !== '.' && $parent !== ''; $parent = dirname($parent)) {
                $folders[strtolower($parent)] = true;
            }

            if (($header['flags'] & 0x1) !== 0) {
                throw new InvalidArgumentException('Verschlüsselte Einträge werden nicht unterstützt: ' . self::shortName($name));
            }

            if (!in_array($header['method'], [0, 8], true)) {
                throw new InvalidArgumentException('Nicht unterstützte Komprimierung in „' . self::shortName($name) . '“ (nur Deflate).');
            }

            if ($header['method'] === 0 && $header['size'] !== $header['compSize']) {
                throw new InvalidArgumentException('Ein Eintrag des Archivs ist beschädigt: ' . self::shortName($name));
            }

            if ($header['size'] > $limits['entry_bytes'] || ($total += $header['size']) > $limits['total_bytes']) {
                throw new InvalidArgumentException('Das Archiv ist entpackt zu groß.');
            }

            if ($header['size'] > 1048576 && $header['size'] > $limits['ratio'] * max(1, $header['compSize'])) {
                throw new InvalidArgumentException('Verdächtige Kompressionsrate (ZIP-Bombe?): ' . self::shortName($name));
            }

            $entries[] = [
                'name' => $name,
                'size' => $header['size'],
                'compressed' => $header['compSize'],
                'method' => $header['method'],
                'crc' => $header['crc'],
                'offset' => $header['offset'],
                'mtime' => self::dosTime($header['time'], $header['date']),
            ];
        }

        // Ein Pfad darf nicht zugleich Datei und Ordner sein (z. B. „a“ und „a/b.php“).
        $clash = array_intersect_key($folded, $folders);

        if ($clash !== []) {
            throw new InvalidArgumentException('Pfad ist im Archiv zugleich Datei und Ordner: ' . self::shortName((string) array_key_first($clash)));
        }

        return $entries;
    }

    /**
     * Inhalt eines Eintrags (geprüft: CRC und Größe).
     *
     * @param array<string, mixed> $entry aus entries()
     */
    public static function read(string $file, array $entry) :string {
        $handle = self::open($file);

        try {
            fseek($handle, (int) $entry['offset']);
            $local = unpack('Vsignature/vneeded/vflags/vmethod/vtime/vdate/Vcrc/VcompSize/Vsize/vnameLength/vextraLength', (string) fread($handle, 30));

            if ($local === false || $local['signature'] !== 0x04034b50 || $local['method'] !== (int) $entry['method']) {
                throw new InvalidArgumentException('Ein Eintrag des Archivs ist beschädigt: ' . self::shortName((string) $entry['name']));
            }

            fseek($handle, (int) $entry['offset'] + 30 + $local['nameLength'] + $local['extraLength']);
            $raw = (int) $entry['compressed'] > 0 ? (string) fread($handle, (int) $entry['compressed']) : '';
        } finally {
            fclose($handle);
        }

        if (strlen($raw) !== (int) $entry['compressed']) {
            throw new InvalidArgumentException('Ein Eintrag des Archivs ist unvollständig: ' . self::shortName((string) $entry['name']));
        }

        $content = (int) $entry['method'] === 8 ? @gzinflate($raw, (int) $entry['size'] + 1) : $raw;

        if (!is_string($content) || strlen($content) !== (int) $entry['size'] || (crc32($content) & 0xFFFFFFFF) !== (int) $entry['crc']) {
            throw new InvalidArgumentException('Prüfsumme stimmt nicht: ' . self::shortName((string) $entry['name']));
        }

        return $content;
    }

    /**
     * Alle Dateien in einen leeren Zielordner entpacken; liefert die geschriebenen Pfade.
     *
     * @param array{entries?: int, entry_bytes?: int, total_bytes?: int, ratio?: int} $limits
     * @return list<string>
     */
    public static function extractTo(string $file, string $target, array $limits = []) :array {
        $base = realpath($target);

        if ($base === false || !is_dir($base)) {
            throw new RuntimeException('Der Zielordner zum Entpacken fehlt.');
        }

        $written = [];

        foreach (self::entries($file, $limits) as $entry) {
            $path = $base . '/' . $entry['name'];
            $folder = dirname($path);

            if (!is_dir($folder) && !@mkdir($folder, 0770, true) && !is_dir($folder)) {
                throw new RuntimeException('Ordner konnte nicht angelegt werden: ' . self::shortName($entry['name']));
            }

            // Kein Ausbruch über vorhandene symbolische Links im Zielbaum.
            $real = realpath($folder);

            if ($real === false || ($real !== $base && !str_starts_with($real, $base . '/')) || is_link($path)) {
                throw new RuntimeException('Unzulässiger Zielpfad: ' . self::shortName($entry['name']));
            }

            if (file_put_contents($path, self::read($file, $entry), LOCK_EX) === false) {
                throw new RuntimeException('Datei konnte nicht geschrieben werden: ' . self::shortName($entry['name']));
            }

            $written[] = $entry['name'];
        }

        return $written;
    }

    /** Relativer, harmloser Pfad (keine Ausbrüche aus dem Zielordner). */
    public static function checkPath(string $path) :void {
        if ($path === '' || str_starts_with($path, '/') || preg_match('~(^|/)\.\.?(/|$)~', $path) || preg_match('/[\x00-\x1F\x7F\\\\:*?"<>|]/', $path)
            || str_contains($path, '//') || strlen($path) > 400 || preg_match('//u', $path) !== 1) {
            throw new InvalidArgumentException('Unzulässiger Pfad im Archiv: ' . self::shortName($path));
        }
    }

    private static function shortName(string $name) :string {
        $clean = preg_replace('/[\x00-\x1F\x7F]/', '?', $name) ?? '';

        return preg_match('/^.{0,80}/us', $clean, $match) === 1 ? $match[0] : substr($clean, 0, 80);
    }

    /**
     * @return resource
     */
    private static function open(string $file) {
        $handle = is_file($file) ? @fopen($file, 'rb') : false;

        if ($handle === false) {
            throw new InvalidArgumentException('Das Archiv ist nicht lesbar.');
        }

        return $handle;
    }

    private static function dosTime(int $time, int $date) :int {
        if ($date === 0) {
            return 0;
        }

        $value = gmmktime($time >> 11, ($time >> 5) & 0x3F, ($time & 0x1F) * 2, ($date >> 5) & 0x0F, $date & 0x1F, ($date >> 9) + 1980);

        return $value === false ? 0 : $value;
    }
}
