# 16 – SQL-Fallback und Speicherumschaltung

Der optionale SQL-Fallback hält das **GBDB-Dateiformat** in SQL vor. Tabellenzeilen, Schema, Metadaten und GDP-Journal bleiben aus Sicht der Engine GBDB-Daten. SQL dient als alternativer physischer Speicher; es entsteht kein relationales Abbild jeder Anwendungstabelle.

## Voraussetzungen

`GBDBSQLStore` unterstützt **MySQL/MariaDB** über PDO/MySQL und **SQLite** über PDO/SQLite. Für MySQL werden InnoDB-Tabellen verwendet und geprüft. PostgreSQL ist in diesem Fallback nicht implementiert.

Im Bereich `FAILOVER` konfigurieren:

| Schlüssel | Bedeutung |
| --- | --- |
| `enabled` | Fallback einschalten; Standard `false` |
| `dsn`, `user`, `password` | Erreichbares SQL-Ziel mit passenden Rechten |
| `prefix` | Eindeutiges Tabellenpräfix je unabhängiger Installation |
| `connect_timeout` | Verbindungszeitlimit |
| `retry_seconds` | Abstand erneuter Verbindungsversuche nach Fehlern |

Das Präfix muss mit einem Buchstaben beginnen, darf danach Buchstaben, Zahlen und Unterstriche enthalten und höchstens 40 Zeichen lang sein. Der Speicher legt `<prefix>_files` und `<prefix>_meta` an. Er benötigt die dafür nötigen SQL-Rechte.

`FAILOVER_DEV` ist standardmäßig leer. Damit verwendet DEV dieselbe Fallback-Verbindung. Für Testkopien unbedingt ein eigenes Ziel oder Präfix setzen.

## Maßgeblicher Speicher

| Zustand | Verhalten |
| --- | --- |
| `mode = gbdb` | Primär die Dateien; Änderungen werden bei verfügbarem Fallback gespiegelt |
| SQL-Spiegel nicht aktuell | `sync_pending` beziehungsweise fehlende Bereitschaft; keine Zusage einer sicheren Umschaltung auf veraltete Daten |
| Dateispeicher fällt aus und SQL ist bereit | Die Laufzeit kann SQL als maßgeblichen Speicher aktivieren |
| `mode = sql` | SQL ist maßgeblich; Wiederherstellung des physischen Bestands erfolgt aus SQL |
| Rückkehr nach GBDB | Kontrollierter Abgleich und ausdrückliche Umschaltung |

Der Modus wird installationsweit gespeichert und durch Sperren geschützt. Solange SQL maßgeblich ist, nicht einfach den Fallback deaktivieren oder ein altes Dateibackup über die Installation kopieren. Der Code blockiert einen Start mit deaktiviertem Fallback und weiterhin aktivem SQL-Modus.

## Status und Bedienung

Admins finden die Steuerung unter **Speicher**. Aus PHP:

```php
$status = GBDB::backendStatus();
```

Wichtige Felder sind `mode`, `enabled`, `sql_available`, `sql_ready`, `sync_pending`, `generation`, `last_sync`, `last_error` und `reason`.

Geschützte administrative Aufrufe:

```php
$bericht = GBDB::synchronizeSQL();
$status = GBDB::switchBackend('sql');
$status = GBDB::switchBackend('gbdb');
```

Diese drei Zeilen sind **einzelne Verwaltungsaktionen**, keine automatisch auszuführende Startsequenz. `synchronizeSQL()` gleicht vom derzeit maßgeblichen Backend zum anderen ab. `switchBackend('gbdb')` kopiert den SQL-Stand zurück und ändert anschließend den Modus. Fehler sind zu behandeln; ausreichender freier Speicher und Schreibrechte bleiben erforderlich.

Über die Public API erfordern `gbdb.backend_status`, `gbdb.backend_switch` und `gbdb.backend_sync` das Recht `gbdb-backend`.

## Grenzen

- Die Spiegelung läuft innerhalb normaler PHP-Ausführungen; ohne Anfragen oder expliziten Aufruf arbeitet kein dauerhafter Hintergrundprozess.
- Ein Spiegel ist kein unabhängiges historisches Backup: Löschungen und fehlerhafte Änderungen können ebenfalls gespiegelt werden.
- Daten, lokaler Speicherstatus und Schlüssel gehören zusammen. Ein fremder oder alter SQL-Bestand darf nicht mit demselben Präfix eingebunden werden.
- Der SQL-Fallback macht die Anwendung nicht automatisch gegen den gleichzeitigen Ausfall von PHP, Schlüsseln, SQL und Dateispeicher verfügbar.
- Die direkte Klasse `SQL` und die `DatabaseBridge` sind andere Zugriffswege. Ihre Konfiguration ersetzt `FAILOVER` nicht.

GDP-Journal und Cursor liegen im gespiegelten Datenbestand. Dadurch nutzt GDP beim Backendwechsel denselben Speicherweg. Das ist keine Aussage über unbegrenzte Last oder eine vollständig geprüfte Kombination aller Ausfallszenarien.
