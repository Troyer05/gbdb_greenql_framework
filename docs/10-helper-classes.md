# 10 – Hilfsklassen

Die Klassen unter `core/` werden vom Autoloader geladen. Vollständige Signaturen stehen in der [Referenz](99-api-reference/core-helper-methods.md). Bei produktbezogenen Netzwerkaufrufen ist zusätzlich die jeweilige externe API maßgeblich; hier ist der mitgelieferte Wrapper beschrieben.

| Klasse | Verwendung | Besonderheit |
| --- | --- | --- |
| `Http` | HTTP GET/POST, JSON-Antworten, Weiterleitungen und Mailanbindung | GET/POST liefern Text oder `false`; JSON selbst auswerten |
| `Validate` | Pflichtfelder, E-Mail, Zahlen, Längen und Muster | `required()` liefert `bool`, keine Liste fehlender Felder |
| `FileTool` | Text-/JSON-Dateien, Verzeichnisse, Kopien und Sicherungen | Pfade und Rechte im Anwendungscode begrenzen |
| `Json` | JSON kodieren, dekodieren und Elemente bearbeiten | `decode()` verwendet standardmäßig keine assoziativen Arrays |
| `Cookie`, `Session` | Anfrageübergreifender Zustand | Vor der Ausgabe verwenden, soweit Header gesetzt werden |
| `Cache` | GBDB-bezogener Tabellen-/Abfragecache | Keine allgemeine Schlüssel-Wert-API mit beliebiger TTL annehmen |
| `Crypt` | Framework-Verschlüsselung | Vorhandenen Schlüssel und Format erhalten |
| `Format` | Zeichenketten und Bezeichner aufbereiten | Bereinigung ersetzt keine fachliche Validierung oder Autorisierung |
| `Route` | Kleiner HTTP-Router mit Middleware | Für Apps mit überschaubarer Routenstruktur |
| `SQL` | Direkter PDO-Zugriff über `SQL`-Konfiguration | Getrennt vom GBDB-Dateispiegel in SQL |
| `DatabaseBridge` | Umschaltung zwischen vorhandenen GBDB-/SQL-Zweigen | Laut Statusmatrix Beta; nicht als vollständige SQL-Kompatibilität betrachten |
| `Tools` | IDs, Token, Passwörter, Netzwerk- und QR-Helfer | `generateId()` liefert eine Zahl; keine globale UUID-Zusage |
| `Converter` | Konvertierungsfunktionen | Vor Veränderungen eine Sicherung anlegen |
| `ReCaptcha` | Prüfung eines reCAPTCHA-Ergebnisses | Konfigurierte Schlüssel und Netzwerkzugriff erforderlich |
| `Ref`, `Time`, `GetForm` | Referenzen, Zeitdarstellung und Formularhilfen | Exakte Schreibweise und Parameter in der Referenz nachlesen |
| `GBDBQueryBuilder` | Aufbau von GBDB-Abfragen | Komfort-API laut Statusmatrix noch nicht final |
| `GBDBStorage` | Ergänzende Speicherhelfer | Für reguläre Anwendungsdaten die öffentliche `GBDB`-Fassade verwenden |

## Validierung

```php
if (!Validate::required($_POST, ['benutzername', 'email'])
    || !Validate::email((string) ($_POST['email'] ?? ''))) {
    Http::jsonResponse(['fehler' => 'Eingaben unvollständig oder ungültig'], 400);
}
```

Das Beispiel setzt passende skalare Formulareingaben voraus. Bei unstrukturiertem JSON zuerst die Datentypen prüfen und erst danach konvertieren.

## HTTP und JSON

```php
$antwort = Http::get('https://example.com/api');

if ($antwort !== false) {
    $daten = Json::decode($antwort, true);
}
```

Die Adresse ist ein Platzhalter, kein Framework-Testdienst. Netzwerkfehler, Statuscodes und unerwartete Antwortformate müssen für die jeweilige Integration behandelt werden.

## Routing

```php
Route::get('/', static function () :void {
    echo 'Startseite';
});

Route::post('/speichern', static function () :array {
    return ['ok' => true];
});

Route::dispatch();
```

Anmeldung, CSRF-Prüfung und fachliche Berechtigungen für schreibende Routen ergänzen. Der Router erzeugt dadurch keinen vollständigen Anwendungs-Installer.

## Zwischenspeicher

Für einen eigenen kurzlebigen Ergebniswert eignet sich beispielsweise `GBDB::queryResultCache($key, $callback, $ttl, $tags)`. Cache-Schlüssel und Tags müssen den Instanz-/Mandantenbezug enthalten. Nach Änderungen relevante Tags mit `cacheInvalidateTag()` invalidieren. Die `Cache`-Klasse hat einen anderen, GBDB-bezogenen Vertrag.
