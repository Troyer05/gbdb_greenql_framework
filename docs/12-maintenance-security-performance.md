# 12 – Betrieb, Sicherung und Leistung

## Zusammengehörige Sicherungen

Eine nutzbare Wiederherstellung benötigt den Datenbestand und seine Schlüssel. Mindestens gemeinsam sichern:

- Den tatsächlich konfigurierten Datenordner einschließlich Schema, Skripten, Sicherungen und gegebenenfalls `.gdp`.
- `.config/.framework.env.php` und `.config/.runtime/`.
- Eigene Module, Plugins und benötigte Anwendungseinstiege.
- Den Updater-Arbeitsordner `.gbdb_updater/`, insbesondere den bestätigten Vertrauensstand.

Bei aktivem SQL-Speicher auch dessen maßgeblichen Bestand berücksichtigen. Eine bloße Kopie des physischen GBDB-Ordners kann in diesem Zustand veraltet sein. Eine Sicherung erst als einsatzfähig behandeln, nachdem die Wiederherstellung auf einer getrennten Kopie geprüft wurde.

Vor Änderungen an Schema, Verschlüsselung, Massendaten oder Runtime-Zielen sichern. UI-Sicherungen und der Updater ersetzen nicht automatisch ein vollständiges Backup der gesamten Anwendung.

## Diagnose und Reparatur

1. Schreibzugriffe möglichst anhalten und den aktuellen Zustand kopieren.
2. Aktives Backend über `GBDB::backendStatus()` ermitteln.
3. Diagnose, etwa `dbHealth()`, `tableStats()` und `repairReport()`, auswerten.
4. Nur die betroffenen Indizes, WAL-/Journaldateien oder Beziehungen reparieren.
5. Datenbestand und fachliche Ergebnisse auf einer Kopie prüfen und anschließend den regulären Betrieb wieder freigeben.

Nicht alle Diagnosefunktionen sind rein lesend; einige aktualisieren Metadaten. Ein pauschaler Aufruf sämtlicher Reparaturmethoden ist kein Ersatz für eine Ursachenanalyse.

## Anfragegesteuerte Wartung

Methoden wie `autoMaintenance()`, `autoBackup()` und `maintenanceScheduler()` werden durch einen Aufruf ausgeführt. Sie erzeugen allein keinen dauerhaft laufenden Dienst. Bei Shared Hosting Wartung über die UI oder eine passende geschützte Anwendungsausführung anstoßen. GDP-Embedded-Synchronisation und automatische Update-Planung benötigen ebenfalls eingehende Anfragen; ohne Aktivität gibt es keinen festen Ausführungszeitpunkt.

## Zugriff und Protokolle

Framework-, Daten-, Laufzeit- und Updater-Arbeitsordner dürfen keine Dateien direkt an externe HTTP-Anfragen ausliefern. Apache-Sperren funktionieren nur bei tatsächlich ausgewerteter `.htaccess`; für andere Server gleichwertige Regeln konfigurieren.

Die UI schützt Aktionen durch Anmeldung, Rechte und CSRF-Prüfung. Public-API-Schlüssel erhalten nur benötigte Rechte. Ein `gbdb-read`-Schlüssel ist ohne eigenes Modul nicht automatisch an eine einzelne Kundentabelle gebunden. PHP Exec, Plugin-Upload und Re-Install sind besonders weitreichend und bleiben Admin-Werkzeuge mit zusätzlichem Konfigurationsschalter.

Protokolle können personenbezogene Informationen und bei der Public API Schlüsselwerte enthalten. Sie weder öffentlich bereitstellen noch ungeprüft in ein Repository übernehmen. Laufzeitdaten und Schlüssel werden bei einer reinen Dokumentationsüberarbeitung nicht entfernt.

## Speicherbedarf und Abfragen

`page()` und `cursor()` begrenzen die Antwortgröße, lesen aber intern zunächst die gesamte Tabelle. `streamRows()` lädt den Bestand ebenfalls vor der Verarbeitung. Auch Filterung und Textsuche können vollständige Tabellenlesevorgänge erfordern. Datenmenge und PHP-Speicherlimit für den eigenen Einsatz messen.

Indizes gezielt für häufige Abfragen anlegen und mit tatsächlichen Laufzeiten vergleichen. Die Engine kennzeichnet Teile von Indexpflege, Abfrageplanung, Constraints und Transaktionen noch als experimentell. Eine dokumentierte Methode garantiert keine aktive Optimierung jedes Abfragepfads.

## Cache mit Mandantenbezug

```php
$werte = GBDB::withInstance('beispiel', static function () :mixed {
    return GBDB::queryResultCache('beispiel:aktive-produkte', static function () :mixed {
        return GBDB::getData('shop', 'produkte', true, 'aktiv', '1');
    }, 60, ['beispiel:produkte']);
});

GBDB::withInstance('beispiel', static function () :void {
    GBDB::cacheInvalidateTag('beispiel:produkte');
});
```

Nach einer fachlichen Änderung die zugehörigen Cache-Tags invalidieren. Instanznamen in Schlüsseln und Tags vermeiden eine unbeabsichtigte Vermischung anwendungsseitiger Cache-Bezeichnungen.

## GDP und Update-Wiederherstellung

Ein GDP-Re-Snapshot kann lokale Tabellen ersetzen; dafür sind Bestätigung und vorherige Sicherung vorgesehen. Journal oder Schlüssel nicht zur schnellen Fehlerbehebung löschen. Verfahren stehen in der [GDP-Anleitung](../PHP/GDP.md).

Der Framework-Updater hält den Vertrauensstand außerhalb normaler Code-Backups aufrecht, damit eine Wiederherstellung keinen alten Vertrauenszustand freigibt. Ein Code-Rollback ist nicht automatisch eine Rücknahme bereits ausgeführter externer Aktionen oder verteilter Änderungen. [Updater-Grenzen](../PHP/FRAMEWORK_UPDATER.md) beachten.
