<?php

declare(strict_types=1);

/**
 * Erzeugt den deutschen Signaturindex, ohne Framework-Code auszuführen.
 * Aufruf im Repository: php docs/tools/generate-api-reference.php [--check]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$arguments = array_slice($argv, 1);

if ($arguments !== [] && $arguments !== ['--check']) {
    fwrite(STDERR, "Aufruf: php docs/tools/generate-api-reference.php [--check]\n");
    exit(2);
}

$check = $arguments === ['--check'];
$repository = dirname(__DIR__, 2);
$framework = $repository . '/PHP/gbdb_framework';
$destination = dirname(__DIR__) . '/99-api-reference';

/** Liefert den nächsten Token ohne Leerraum oder Kommentare. */
function nextCode(array $tokens, int $index) :int {
    while (isset($tokens[$index]) && $tokens[$index]->isIgnorable()) {
        $index++;
    }

    return $index;
}

/** Signaturen werden tokenweise zusammengefügt; Zeichenketten bleiben unverändert. */
function signature(array $tokens, int $start, int $end) :string {
    $text = '';

    for ($index = $start; $index < $end; $index++) {
        $token = $tokens[$index];

        if ($token->is([T_COMMENT, T_DOC_COMMENT])) {
            continue;
        }

        $text .= $token->id === T_WHITESPACE ? ' ' : $token->text;
    }

    return trim($text);
}

/** Erfasst öffentliche Deklarationen ohne require/include des eingelesenen Codes. */
function declarations(string $path, string $relative) :array {
    $source = file_get_contents($path);

    if ($source === false) {
        throw new RuntimeException('Nicht lesbar: ' . $relative);
    }

    $tokens = PhpToken::tokenize($source, TOKEN_PARSE);
    $items = [];
    $scopes = [];
    $pending = null;
    $depth = 0;
    $modifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];

    for ($index = 0, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];

        if ($token->is([T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM])) {
            $next = nextCode($tokens, $index + 1);

            if (isset($tokens[$next]) && $tokens[$next]->id === T_STRING) {
                $kind = match ($token->id) {
                    T_TRAIT => 'Trait',
                    T_INTERFACE => 'Interface',
                    T_ENUM => 'Enum',
                    default => 'Klasse',
                };
                $pending = count($items);
                $items[] = [
                    'name' => $tokens[$next]->text,
                    'kind' => $kind,
                    'file' => $relative,
                    'line' => $token->line,
                    'methods' => [],
                    'cases' => [],
                ];
            }
        }

        if ($token->id === ord('{') || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
            $depth++;

            if ($pending !== null) {
                $scopes[] = ['item' => $pending, 'depth' => $depth];
                $pending = null;
            }

            continue;
        }

        if ($token->id === ord('}')) {
            if ($scopes !== [] && end($scopes)['depth'] === $depth) {
                array_pop($scopes);
            }

            $depth--;
            continue;
        }

        $scope = $scopes === [] ? null : end($scopes);

        if ($token->id === T_CASE && $scope !== null && $depth === $scope['depth']
            && $items[$scope['item']]['kind'] === 'Enum') {
            $next = nextCode($tokens, $index + 1);

            if (($tokens[$next]->id ?? null) === T_STRING) {
                $items[$scope['item']]['cases'][] = $tokens[$next]->text;
            }
        }

        if ($token->id !== T_FUNCTION || ($scope !== null && $depth !== $scope['depth'])) {
            continue;
        }

        $next = nextCode($tokens, $index + 1);

        if (($tokens[$next]->text ?? '') === '&') {
            $next = nextCode($tokens, $next + 1);
        }

        if (($tokens[$next]->id ?? null) !== T_STRING) {
            continue;
        }

        $start = $index;
        $visibility = T_PUBLIC;

        for ($previous = $index - 1; $previous >= 0; $previous--) {
            $part = $tokens[$previous];

            if ($part->id === T_WHITESPACE) {
                continue;
            }

            if (!$part->is($modifiers)) {
                break;
            }

            $start = $previous;

            if ($part->is([T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
                $visibility = $part->id;
            }
        }

        if ($scope !== null && $visibility !== T_PUBLIC) {
            continue;
        }

        $end = $index + 1;
        $parentheses = 0;
        $brackets = 0;

        for (; $end < $count; $end++) {
            $part = $tokens[$end];

            if ($parentheses === 0 && $brackets === 0 && $part->is(['{', ';'])) {
                break;
            }

            $parentheses += $part->id === ord('(') ? 1 : ($part->id === ord(')') ? -1 : 0);
            $brackets += $part->id === ord('[') ? 1 : ($part->id === ord(']') ? -1 : 0);
        }

        $method = [
            'name' => $tokens[$next]->text,
            'signature' => signature($tokens, $start, $end),
            'line' => $token->line,
        ];

        if ($scope !== null) {
            $items[$scope['item']]['methods'][] = $method;
        } else {
            $globalName = 'Globale Funktionen';
            $found = null;

            foreach ($items as $key => $item) {
                if ($item['name'] === $globalName && $item['kind'] === 'Funktionen') {
                    $found = $key;
                    break;
                }
            }

            if ($found === null) {
                $found = count($items);
                $items[] = ['name' => $globalName, 'kind' => 'Funktionen', 'file' => $relative,
                    'line' => $token->line, 'methods' => [], 'cases' => []];
            }

            $items[$found]['methods'][] = $method;
        }
    }

    return $items;
}

/** Bereich anhand des Quellpfads bestimmen. */
function groupFor(string $file) :string {
    if ($file === 'runtime/facade.php' || str_starts_with($file, 'GBDB_GQL/db_engine/')) {
        return 'gbdb-engine-methods';
    }

    if (str_starts_with($file, 'GBDB_GQL/greenql/')) {
        return 'greenql-methods';
    }

    if (str_starts_with($file, 'public/includes/public_api/')) {
        return 'public-api-methods';
    }

    if (str_starts_with($file, 'SRV/') || $file === 'core/srvp.php') {
        return 'srv-methods';
    }

    if (str_starts_with($file, 'Distributed/')) {
        return 'gdp-methods';
    }

    if (str_starts_with($file, 'updater/')) {
        return 'updater-methods';
    }

    if (str_starts_with($file, 'public/')) {
        return 'ui-methods';
    }

    if (str_starts_with($file, 'plugins/')) {
        return 'plugin-methods';
    }

    if (str_starts_with($file, 'runtime/') && $file !== 'runtime/auth_facade.php') {
        return 'runtime-methods';
    }

    return 'core-helper-methods';
}

function codeCell(string $text) :string {
    return '`' . str_replace(['|', "\n", "\r", '`'], ['\\|', ' ', '', '&#96;'], $text) . '`';
}

$groups = [
    'gbdb-engine-methods' => ['GBDB – Fassade, Engine und Traits',
        'Anwendungscode verwendet GBDB aus runtime/facade.php. GBDBEngine und die Traits sind interne Implementierungen. Traits werden nicht direkt aufgerufen; gleichnamige Deklarationen können durch die Fassade oder Trait-Auflösung ersetzt werden.'],
    'greenql-methods' => ['GreenQL – öffentliche Deklarationen',
        'Die Klasse GreenQL verwendet die aufgeführten Traits. Für Anwendungscode sind GBDB::query() und GBDB::runScript() die regulären Einstiege; GreenQL::run() führt Skripttext aus.'],
    'core-helper-methods' => ['Konfiguration, Auth und Hilfsklassen',
        'Vars beschreibt die zentrale Konfiguration. Anwendungscode verwendet Auth aus runtime/auth_facade.php; GBDBAuthEngine ist intern. Globale Funktionen sind mit ihrer tatsächlichen Quelldatei aufgeführt.'],
    'public-api-methods' => ['Public API – Dispatcher und Module',
        'HTTP-Einstieg ist PHP/public_api.php. Aktionshandler des GBDB-Moduls sind überwiegend privat und deshalb nicht Teil dieses öffentlichen PHP-Signaturindex. Aktionen und Rechte stehen im API-Handbuch.'],
    'srv-methods' => ['SecondServer – Client, Backend und Aufträge',
        'Anwendungen verwenden den Client SecondServer. Srv, SrvFunctions und SrvAuth sind Backend-Bausteine; SRVJob stellt Hilfen für Auftragsmodule bereit.'],
    'runtime-methods' => ['Interne Speicherlaufzeit',
        'Diese Klassen steuern Konfiguration, Dateizugriff, SQL-Speicher und Sperren. Reguläre Datenoperationen über GBDB ausführen. Öffentliche Sichtbarkeit ist hier keine Empfehlung für direkte Anwendungskopplung.'],
    'gdp-methods' => ['GDP – API, Protokoll und Zustände',
        'GDP ist die öffentliche Fassade. Die übrigen Klassen und Enums bilden Protokoll, Speicher, Policies und Synchronisation ab. Transportzugriffe müssen die vorgesehenen Authentifizierungs- und Zustandsprüfungen durchlaufen.'],
    'updater-methods' => ['Framework-Updater – öffentliche Deklarationen',
        'Installation und Wiederherstellung regulär über die Admin-Oberfläche bedienen. Die aufgeführten Klassen sind interne Prüf- und Ablaufbausteine; ihre Sichtbarkeit erlaubt keine Umgehung der Vertrauenskette.'],
    'ui-methods' => ['Verwaltungsoberfläche – Helfer und Funktionen',
        'GreenQLUIv2Helper und globale UI-Funktionen unterstützen Anmeldung, Berechtigungen, Seiten und Formulare. Dateien nicht allein zum Laden einer Funktion einbinden: Manche sind ausführbare Seiteneinstiege.'],
    'plugin-methods' => ['Produkt-Plugins',
        'MqrApi enthält die implementierte MuseumQR-Anbindung. mRoot, ShareSuiteAPI und EqrAPI sind im aktuellen Stand leere Platzhalterklassen.'],
];

try {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($framework, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $entry) {
        if (!$entry->isFile() || $entry->isLink() || $entry->getExtension() !== 'php') {
            continue;
        }

        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($entry->getPathname(), strlen($framework) + 1));

        if (preg_match('~(?:^|/)(?:\.DB|\.runtime|\.logs)(?:/|$)~', $relative)) {
            continue;
        }

        $files[$relative] = $entry->getPathname();
    }

    ksort($files, SORT_STRING);
    $contents = array_fill_keys(array_keys($groups), []);

    foreach ($files as $relative => $path) {
        $contents[groupFor($relative)] = array_merge($contents[groupFor($relative)], declarations($path, $relative));
    }

    $outputs = [];
    $index = "# Öffentliche PHP-Deklarationen – Gesamtindex\n\n"
        . "Dieser Index und die Teilreferenzen werden aus dem aktuellen Quellcode erzeugt. "
        . "Erfasst sind öffentliche Methoden einschließlich Rückgabetypen, deklarierte Enum-Fälle und globale Funktionen. "
        . "Private und geschützte Methoden sowie Laufzeitdaten werden ausgelassen. Geerbte Methoden erscheinen an ihrer Deklarationsstelle.\n\n"
        . "Neuerzeugung: `php docs/tools/generate-api-reference.php` im Repository-Stamm. "
        . "Prüfung ohne Schreiben: `php docs/tools/generate-api-reference.php --check`.\n\n"
        . "Die Tabellen sind ein Signatur- und Quellennachweis, keine Zusicherung von Seiteneffektfreiheit, Reifegrad oder freier Aufrufbarkeit interner Bausteine. "
        . "Verhalten und Beispiele stehen im [Handbuch](../README.md) und in der [Statusübersicht](../18-feature-status.md).\n\n"
        . "| Bereich | Klassen / Gruppen | Methoden / Funktionen |\n| --- | --- | --- |\n";
    $classCount = 0;
    $methodCount = 0;

    foreach ($groups as $key => [$title, $description]) {
        $items = $contents[$key];
        usort($items, static function (array $left, array $right) :int {
            $priority = ['GBDB' => 0, 'Auth' => 0, 'GDP' => 0, 'GreenQL' => 0, 'SecondServer' => 0];

            return (($priority[$left['name']] ?? 1) <=> ($priority[$right['name']] ?? 1))
                ?: strcmp($left['file'], $right['file']) ?: strcmp($left['name'], $right['name']);
        });
        $content = '# ' . $title . "\n\n" . $description . "\n\n[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)\n\n"
            . "Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.\n\n";
        $number = 0;

        foreach ($items as $item) {
            $source = '../../PHP/gbdb_framework/' . $item['file'];
            $content .= '## ' . $item['name'] . "\n\n" . $item['kind'] . ' · Quelle: [' . $item['file'] . ']('
                . $source . '#L' . $item['line'] . ")\n\n";

            if ($item['cases'] !== []) {
                $content .= 'Deklarierte Enum-Fälle: ' . implode(', ', array_map('codeCell', $item['cases'])) . ".\n\n";
            }

            if ($item['methods'] === []) {
                $content .= "Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.\n\n";
                continue;
            }

            $content .= "| Methode / Funktion | Deklarierte Signatur | Quelle |\n| --- | --- | --- |\n";

            foreach ($item['methods'] as $method) {
                $content .= '| ' . codeCell($method['name'] . '()') . ' | ' . codeCell($method['signature']) . ' | [Zeile '
                    . $method['line'] . '](' . $source . '#L' . $method['line'] . ") |\n";
                $number++;
            }

            $content .= "\n";
        }

        $outputs[$key . '.md'] = $content;
        $index .= '| [' . $title . '](' . $key . '.md) | ' . count($items) . ' | ' . $number . " |\n";
        $classCount += count($items);
        $methodCount += $number;
    }

    $index .= "\n## Namen nach Bereich\n\n";

    foreach ($groups as $key => [$title]) {
        $names = array_column($contents[$key], 'name');
        sort($names, SORT_STRING);
        $index .= '### ' . $title . "\n\n" . implode(', ', array_map('codeCell', $names)) . ".\n\n";
    }

    $outputs['all-public-methods.md'] = $index;
    $different = [];

    foreach ($outputs as $filename => $content) {
        $path = $destination . '/' . $filename;

        if (!is_file($path) || file_get_contents($path) !== $content) {
            $different[] = $filename;

            if (!$check && file_put_contents($path, $content) !== strlen($content)) {
                throw new RuntimeException('Referenz konnte nicht geschrieben werden: ' . $filename);
            }
        }
    }

    if ($check && $different !== []) {
        fwrite(STDERR, "Referenz veraltet oder fehlt: " . implode(', ', $different) . "\n");
        exit(1);
    }

    echo ($check ? 'Geprüft: ' : 'Erzeugt: ') . count($outputs) . ' Referenzdateien, ' . $classCount
        . ' Klassen/Gruppen, ' . $methodCount . " öffentliche Methoden/Funktionen.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
