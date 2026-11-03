<?php

declare(strict_types=1);

/**
 * Früherer, nicht mehr unterstützter Backend-Endpunkt.
 *
 * Er umging die SRV-Authentifizierung (Token per ungeschütztem SHA-256 des
 * statischen Schlüssels), erlaubte Backups und Dateiausführung an frei
 * wählbaren Pfaden und rief nicht existierende Srv-Methoden auf. Der
 * unterstützte SecondServer-Endpunkt ist backend.php im Projektstamm
 * (SRV/backend_api.php).
 */
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'ok' => false,
    'status' => 410,
    'data' => 'Dieser Endpunkt wurde entfernt. backend.php im Projektstamm verwenden.',
], JSON_UNESCAPED_UNICODE);
