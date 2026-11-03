<?php

declare(strict_types=1);

/**
 * ZIP schreiben ohne PHP-Erweiterung zip, direkt in eine Datei (Deflate, UTF-8-Namen, 0644).
 *
 * Mit festem Zeitstempel und sortierten Einträgen entsteht bei gleichem Inhalt dieselbe Datei.
 * Diese Datei ist identisch im Release-Manager enthalten (lib/shared).
 */
final class GBDBUpdateZipWriter {
    /** @var resource */
    private $handle;
    private string $directory = '';
    private int $count = 0;
    private int $offset = 0;
    private bool $closed = false;

    /** @var array<string, true> */
    private array $names = [];

    /** @var array<string, true> */
    private array $folders = [];

    public function __construct(private readonly string $file, private readonly ?int $fixedTime = null) {
        $handle = @fopen($file, 'xb');

        if ($handle === false) {
            throw new RuntimeException('Das Archiv kann nicht angelegt werden (Datei vorhanden oder Ordner nicht beschreibbar).');
        }

        $this->handle = $handle;
    }

    public function addContent(string $name, string $content, ?int $mtime = null) :void {
        if ($this->closed) {
            throw new RuntimeException('Das Archiv ist bereits geschlossen.');
        }

        GBDBUpdateZip::checkPath($name);
        $key = strtolower($name);

        if (isset($this->names[$key]) || isset($this->folders[$key])) {
            throw new InvalidArgumentException('Doppelter Eintrag oder Konflikt mit einem Ordner: ' . $name);
        }

        for ($parent = dirname($key); $parent !== '.' && $parent !== ''; $parent = dirname($parent)) {
            if (isset($this->names[$parent])) {
                throw new InvalidArgumentException('Pfad wäre zugleich Datei und Ordner: ' . $parent);
            }

            $this->folders[$parent] = true;
        }

        if ($this->count >= 0xFFFF - 1 || strlen($content) >= 0xFFFFFFFF || $this->offset >= 0xFFFFFFFF - strlen($content) - 1024) {
            throw new RuntimeException('Das Archiv wird zu groß (ZIP64 wird nicht unterstützt).');
        }

        $this->names[$key] = true;
        $compressed = (string) gzdeflate($content, 6);
        $method = 8;

        // Bereits verdichtete Inhalte nicht aufblähen.
        if (strlen($compressed) >= strlen($content)) {
            $compressed = $content;
            $method = 0;
        }

        [$time, $date] = self::dosTime($this->fixedTime ?? $mtime ?? time());
        $crc = crc32($content) & 0xFFFFFFFF;
        $header = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $time, $date, $crc, strlen($compressed), strlen($content), strlen($name), 0) . $name;
        $this->write($header . $compressed);
        $this->directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, (3 << 8) | 20, 20, 0x0800, $method, $time, $date, $crc, strlen($compressed), strlen($content), strlen($name), 0, 0, 0, 0, 0100644 << 16, $this->offset) . $name;
        $this->offset += strlen($header) + strlen($compressed);
        $this->count++;
    }

    public function addFile(string $name, string $path) :void {
        if (!is_file($path) || is_link($path)) {
            throw new InvalidArgumentException('Datei fehlt oder ist ein Link: ' . $name);
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException('Datei nicht lesbar: ' . $name);
        }

        $this->addContent($name, $content, (int) filemtime($path));
    }

    /**
     * Archiv abschließen.
     *
     * @return array{entries: int, size: int}
     */
    public function close() :array {
        if (!$this->closed) {
            $this->write($this->directory . pack('VvvvvVVv', 0x06054b50, 0, 0, $this->count, $this->count, strlen($this->directory), $this->offset, 0));
            fclose($this->handle);
            $this->closed = true;
        }

        clearstatcache(true, $this->file);

        return ['entries' => $this->count, 'size' => (int) filesize($this->file)];
    }

    public function abort() :void {
        if (!$this->closed) {
            fclose($this->handle);
            $this->closed = true;
        }

        @unlink($this->file);
    }

    private function write(string $bytes) :void {
        if (fwrite($this->handle, $bytes) !== strlen($bytes)) {
            $this->abort();

            throw new RuntimeException('Das Archiv konnte nicht geschrieben werden (Speicherplatz?).');
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function dosTime(int $timestamp) :array {
        $parts = explode(' ', gmdate('Y n j G i s', max($timestamp, 315532800)));
        [$year, $month, $day, $hour, $minute, $second] = array_map('intval', $parts);

        return [($hour << 11) | ($minute << 5) | intdiv($second, 2), (($year - 1980) << 9) | ($month << 5) | $day];
    }
}
