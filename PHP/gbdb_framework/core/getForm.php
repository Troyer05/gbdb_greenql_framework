<?php

class GetForm {
    /**
     * Verarbeitet die Funktion get dropdown.
     * @param mixed $dropdown Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function getDropdown(mixed $dropdown): mixed {
        $e = "";

        foreach ($dropdown as $val) {
            $e = $val;
        }

        return $e;
    }

    /**
     * Sichere Upload-Funktion (bis 2 MB, MIME-Check, sichere Namen)
     */
    public static function upload(mixed $file, string $path = "./", string $useName = ""): bool {
        if (
            !isset($file['tmp_name']) ||
            empty($file['tmp_name']) ||
            !isset($file['error']) ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {
            return false;
        }

        // 2MB Limit
        if ($file['size'] > (2 * 1024 * 1024)) {
            return false;
        }

        // Sicherer Dateiname
        $fileName = basename($file['name']);
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $fileName);
        $fileName = str_replace('..', '', $fileName);

        // Dateiendung
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Verbotene Extensions (RCE Schutz)
        $blocked = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat'];

        if (in_array($ext, $blocked)) {
            return false;
        }

        // Erlaubte Dateiendungen
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'docx', 'doc', 'xls', 'ppt', 'ppts', 'webp'];

        if (!in_array($ext, $allowed)) {
            return false;
        }

        // Ordner existiert nicht → automatisch erstellen
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                return false;
            }
        }

        if (!is_writable($path)) {
            return false;
        }

        // Finaler Dateiname
        $finalName = empty($useName)
            ? uniqid("up_", true) . "." . $ext
            : preg_replace('/[^a-zA-Z0-9_\-]/', '', $useName) . "." . $ext;

        $target = rtrim($path, "/") . "/" . $finalName;

        // MIME-Type Check
        if (function_exists("finfo_open")) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            $allowedMime = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'text/plain', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($mime, $allowedMime)) {
                return false;
            }
        }

        return move_uploaded_file($file['tmp_name'], $target);
    }

    /**
     * Findet alle Pflichtfelder (_rf)
     */
    public static function check_required_fields(mixed $post_data): mixed {
        $empty = [];

        foreach ($post_data as $field => $value) {
            if (!str_ends_with($field, "_rf")) {
                continue;
            }

            if (trim((string)$value) === "") {
                $empty[] = $field;
            }
        }

        return empty($empty) ? 0 : $empty;
    }

    /**
     * Erzeugt ein HTML Input-Feld
     */
    public static function createInput(string $name, string $type, mixed $form_data, string $placeholder = "", string $class = "", string $id = ""): string {
        $value = htmlspecialchars($form_data[$name] ?? '', ENT_QUOTES, 'UTF-8');

        $element = '<input type="' . $type . '" name="' . $name . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . $value . '"';

        if (!empty($class)) {
            $element .= ' class="' . htmlspecialchars($class) . '"';
        }

        if (!empty($id)) {
            $element .= ' id="' . htmlspecialchars($id) . '"';
        }

        $element .= ' />';

        return $element;
    }

    /**
     * Prüft ob POST-Request erfolgt ist
     */
    public static function checkPost(): bool {
        return ($_SERVER['REQUEST_METHOD'] === 'POST');
    }
}
