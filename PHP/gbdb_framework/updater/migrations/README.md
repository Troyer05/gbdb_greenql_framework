# Framework-Migrationen

Migrationen sind PHP-Dateien in diesem Ordner. Sie geben ein Array mit einer aufrufbaren `up`-Funktion und optional einer `down`-Funktion zurück:

```php
<?php

declare(strict_types=1);

return [
    'up' => static function () :void {
        // Zielinstanz ausdrücklich wählen und die konkrete Schemaänderung prüfen.
    },
    'down' => static function () :void {
        // Optional: bereits ausgeführte Änderungen gezielt zurücknehmen.
    },
];
```

Die Vorlage zeigt nur das Dateiformat und enthält noch keine Schemaänderung. Dateinamen beginnen zweckmäßig mit einem Datum, etwa `2026_11_15_kunden_spalte.php`. Bereits veröffentlichte IDs nicht für andere Migrationen wiederverwenden.

## Manifest und Ablauf

Der separate Release-Manager muss die Migration mit `id`, `file` und `since` im signierten Release-Manifest aufführen. Die ID entspricht im vorgesehenen Paketverfahren dem Dateinamen ohne `.php`.

1. Der Installer ermittelt anhand von Ausgangsversion und `state/migrations.json`, welche Einträge noch anstehen.
2. Vor Änderungen sichert er im Wartungsmodus den physischen Datenordner.
3. Eine Folgeanfrage lädt den neuen Code, führt die Selbstprüfung aus und startet danach die Migrationen in Manifestreihenfolge.
4. Nach erfolgreichem Durchlauf werden die IDs im Zustand gespeichert.
5. Bei einer Exception versucht der Ablauf die `down`-Funktionen bereits erfolgreich gelaufener Migrationen und den Code-/Datenrollback. Die `down`-Funktion der gerade fehlgeschlagenen Migration ist kein zugesicherter Aufräumpfad.

Die Einträge verhindern im regulären erfolgreichen Ablauf Wiederholungen. Bei einem Prozessabbruch zwischen einer fachlichen Nebenwirkung und dem Speichern des Migrationszustands ist jedoch keine allgemeine Genau-einmal-Garantie gegeben. Migrationen möglichst wiederholbar gestalten und Voraussetzungen sowie Rückgaben prüfen.

## Grenzen

Der automatische Sicherungspfad erfasst den physischen GBDB-Datenordner. Aktiven SQL-Fallback, GDP-Replikation und fremde Nebenwirkungen gesondert berücksichtigen. E-Mails, externe APIs oder Änderungen auf einem anderen Knoten werden durch eine lokale Dateiwiederherstellung nicht automatisch zurückgenommen.

Vollständiges Verfahren: [Framework-Updater](../../../FRAMEWORK_UPDATER.md). Öffentliche Installationsmethoden stehen in der [Referenz](../../../../docs/99-api-reference/updater-methods.md).
