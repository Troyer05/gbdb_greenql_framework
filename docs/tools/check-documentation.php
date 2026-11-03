<?php

declare(strict_types=1);

/** Prüft die lokalen Markdown-Dokumente, ohne Beispiele oder Anwendungscode auszuführen. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (count($argv) !== 1) {
    fwrite(STDERR, "Aufruf: php docs/tools/check-documentation.php\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
$files = [$root . '/README.md', $root . '/CHANGELOG.md', $root . '/vsc/README.md'];

foreach ([$root . '/docs', $root . '/PHP'] as $directory) {
    $filter = new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $entry) :bool {
            return !$entry->isLink() && !in_array($entry->getFilename(), ['.git', '.DB', '.runtime', '.logs'], true);
        }
    );

    foreach (new RecursiveIteratorIterator($filter) as $entry) {
        if ($entry->isFile() && strtolower($entry->getExtension()) === 'md') {
            $files[] = $entry->getPathname();
        }
    }
}

$files = array_values(array_unique($files));
sort($files, SORT_STRING);
$errors = [];
$counts = ['markdown' => count($files), 'links' => 0, 'php' => 0, 'json' => 0];

/** Syntaxprüfung eines vollständigen PHP-Beispiels oder eines ausdrücklich gezeigten Vars-Ausschnitts. */
function documentationPhpSyntax(string $source) :void {
    $source = trim($source);

    if (preg_match('/^(?:private|public|protected)\s+const\s/', $source)) {
        $source = "<?php\nclass DokumentationsAusschnitt {\n" . $source . "\n}\n";
    } elseif (!str_starts_with($source, '<?php')) {
        $source = "<?php\n" . $source;
    }

    PhpToken::tokenize($source, TOKEN_PARSE);
}

foreach ($files as $file) {
    $name = str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($root) + 1));
    $text = file_get_contents($file);

    if ($text === false) {
        $errors[] = $name . ': nicht lesbar';
        continue;
    }

    if ($text !== '' && !str_ends_with($text, "\n")) {
        $errors[] = $name . ': abschließender Zeilenumbruch fehlt';
    }

    if (preg_match('/[\t ]+$/m', $text)) {
        $errors[] = $name . ': Leerraum am Zeilenende';
    }

    $block = null;
    $prose = '';

    foreach (explode("\n", $text) as $index => $line) {
        if (preg_match('/^(`{3,}|~{3,})([^`]*)$/', $line, $match)) {
            $marker = $match[1];

            if ($block === null) {
                $block = ['marker' => $marker, 'language' => trim($match[2]), 'line' => $index + 1, 'body' => ''];
            } elseif ($marker[0] === $block['marker'][0] && strlen($marker) >= strlen($block['marker']) && trim($match[2]) === '') {
                try {
                    if ($block['language'] === 'php') {
                        $counts['php']++;
                        documentationPhpSyntax($block['body']);
                    } elseif ($block['language'] === 'json') {
                        $counts['json']++;
                        json_decode($block['body'], true, 512, JSON_THROW_ON_ERROR);
                    }
                } catch (Throwable $error) {
                    $errors[] = $name . ':' . $block['line'] . ': ' . $block['language'] . ': ' . $error->getMessage();
                }

                $block = null;
            } else {
                $block['body'] .= $line . "\n";
            }

            continue;
        }

        if ($block !== null) {
            $block['body'] .= $line . "\n";
        } else {
            $prose .= $line . "\n";
        }
    }

    if ($block !== null) {
        $errors[] = $name . ':' . $block['line'] . ': Codeblock nicht geschlossen';
    }

    preg_match_all('/!?\[[^\]\n]+\]\(([^)\n]+)\)/', $prose, $links, PREG_SET_ORDER);

    foreach ($links as $link) {
        $url = trim($link[1], '<>');

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) || str_starts_with($url, '//')) {
            continue;
        }

        $counts['links']++;
        [$relative, $fragment] = array_pad(explode('#', $url, 2), 2, '');
        $relative = rawurldecode(explode('?', $relative, 2)[0]);
        $target = realpath($relative === '' ? $file : dirname($file) . '/' . $relative);

        if ($target === false) {
            $errors[] = $name . ': lokales Linkziel fehlt: ' . $url;
            continue;
        }

        if ($target !== $root && !str_starts_with($target, $root . DIRECTORY_SEPARATOR)) {
            $errors[] = $name . ': Link verlässt das eigenständige Repository: ' . $url;
        }

        if (preg_match('/^L(\d+)$/', $fragment, $match) && is_file($target)) {
            $source = file_get_contents($target);
            $lineCount = $source === false ? 0 : count(explode("\n", rtrim($source, "\n")));

            if ((int) $match[1] < 1 || (int) $match[1] > $lineCount) {
                $errors[] = $name . ': ungültige Quellzeile: ' . $url;
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo 'Geprüft: ' . $counts['markdown'] . ' Markdown-Dateien, ' . $counts['links'] . ' lokale Links, '
    . $counts['php'] . ' PHP- und ' . $counts['json'] . " JSON-Beispiele. Keine Fehler.\n";
