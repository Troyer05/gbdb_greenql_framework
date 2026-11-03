<?php

declare(strict_types=1);

/**
 * Früherer Tabelleneditor ohne Anmeldung und CSRF-Schutz (Löschen per GET).
 * Er wird nicht mehr verwendet; das GreenQL Studio in gbdb_ui.php ersetzt ihn.
 * Die Datei bleibt als Platzhalter, damit ein Update alte Kopien überschreibt.
 */
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo 'Entfernt. Bitte gbdb_ui.php verwenden.';
