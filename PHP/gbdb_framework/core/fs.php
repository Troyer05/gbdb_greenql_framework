<?php

class FS {
    /** Sichere Ordner-Erstellung */
    /**
     * Verarbeitet die Funktion create folder.
     * @param string $pathAndName Übergabewert.
     * @return void Rückgabewert.
     */
    public static function createFolder(string $pathAndName): void {
        if (!is_dir($pathAndName)) {
            @mkdir($pathAndName, 0777, true);
        }
    }

    /** File schreiben + Stream-Option */
    /**
     * Verarbeitet die Funktion write.
     * @param string $file Übergabewert.
     * @param mixed $data Übergabewert.
     * @param bool $stream Übergabewert.
     * @param bool $overwrite Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function write(string $file, mixed $data, bool $stream = false, bool $overwrite = false): bool {
        self::createFolder(dirname($file));

        if ($stream) {
            $mode = $overwrite ? 'w' : 'a';
            $f = @fopen($file, $mode);

            if (!$f) {
                error_log("[FS] Failed to open stream for {$file}");
                return false;
            }

            fwrite($f, $data);
            fclose($f);

            return true;
        }

        return file_put_contents($file, $data) !== false;
    }

    /** Datei lesen */
    /**
     * Verarbeitet die Funktion read.
     * @param string $file Übergabewert.
     * @return mixed Rückgabewert.
     */
    public function read(string $file): mixed {
        if (!is_file($file)) return "";
        return file_get_contents($file);
    }

    /** Sicheres rekursives Löschen eines Ordners */
    /**
     * Verarbeitet die Funktion delete directory.
     * @param string $dir Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteDirectory(string $dir): bool {
        $dir = rtrim($dir, "/");

        // Schutz: gefährliche Pfade blockieren
        if ($dir === "" || $dir === "/" || strlen($dir) < 2) {
            error_log("[FS] Attempt to delete dangerous directory: {$dir}");
            return false;
        }

        if (!is_dir($dir)) {
            return false;
        }

        foreach (scandir($dir) as $file) {
            if ($file === "." || $file === "..") continue;

            $path = $dir . "/" . $file;

            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }

    /** Ordnergröße */
    /**
     * Verarbeitet die Funktion get folder size.
     * @param string $path Übergabewert.
     * @return string Rückgabewert.
     */
    public static function getFolderSize(string $path): string {
        if (!is_dir($path)) {
            return "0 B";
        }

        $size = 0;

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        ) as $file) {
            $size += $file->getSize();
        }

        // Formatierung
        return FileTool::dirSize($path);
    }

    /** Löscht alle Dateien innerhalb eines Ordners */
    /**
     * Verarbeitet die Funktion delete files.
     * @param string $path Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteFiles(string $path): bool {
        if (!is_dir($path)) {
            return false;
        }

        foreach (scandir($path) as $file) {
            if ($file === "." || $file === "..") continue;

            $filePath = $path . "/" . $file;

            if (is_file($filePath)) {
                if (!@unlink($filePath)) {
                    return false;
                }
            }
        }

        return true;
    }
}
