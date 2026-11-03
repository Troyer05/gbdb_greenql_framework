# 09 – Oberfläche und Verwaltung

Der Einstieg ist `gbdb_ui.php`. Die eigentliche Oberfläche liegt unter `gbdb_framework/public/` und verwendet das Intranet-Design mit Sidebar, Suche, Hell-/Dunkelansicht und Systemhinweisen. Die vorhandenen Menünamen können von der gewählten UI-Sprache abhängen.

## Seiten und Werkzeuge

Aufruf etwa `gbdb_ui.php?tool=storage`. Die Auswahl wird serverseitig auf eine feste Liste begrenzt.

| `tool` | Aufgabe |
| --- | --- |
| `dashboard` | Übersicht |
| `greenql_v2` | Datenansicht und GreenQL Studio |
| `gql_scripts` | Skripte verwalten und ausführen |
| `users` | UI-Konten verwalten |
| `public_api` | API-Schlüssel, Module und Protokolle |
| `storage` | Aktives Speicherbackend, Spiegel und Umschaltung |
| `distributed` | GDP-Verwaltung |
| `updates` | Signierte Framework-Updates |
| `backup` | Sicherungen |
| `import_export` | Datenübernahme und Ausgabe |
| `monitoring` | Zustand und Diagnose |
| `optimize` | Wartung und Optimierung |
| `migration` | Datenmigration |
| `cryption` | Verschlüsselungswerkzeuge |
| `enterprise` | Erweiterte Engine- und Enterprise-Funktionen |
| `env` | Zentrale Framework-Konfiguration |
| `plugins` | Plugin-Verwaltung |
| `reinstall` | Neuinstallation beziehungsweise Reset |
| `php_exec` | PHP-Code ausführen |

`public/includes/table.php` und `public/includes/index.php` sind gesperrte Alt-Einstiege. Datenverwaltung läuft über den regulären Router und das Studio.

## Rollen und Zusatzrechte

| Rolle | Grundrechte |
| --- | --- |
| `admin` | Vollzugriff |
| `maintainer` | `read`, `write`, `structure`, `maintenance`, `jobs`, `backup` |
| `editor` | `read`, `write` |
| `viewer` | `read` |

`permissions` ergänzt die Rolle: leer bedeutet keine Zusatzrechte, `*` sämtliche Zusatzrechte, eine Liste nur die genannten Zusatzrechte. Rollenrechte werden durch eine engere Zusatzliste nicht entzogen.

`tools` erlaubt zusätzlich bestimmte Werkzeuge; das Feld ist keine allgemeine Liste von Verboten. Eine explizite Werkzeugfreigabe kann Seitenzugriff gewähren. Schreib-, Struktur- und Seitenaktionen prüfen weitere Rechte; eine sichtbare Seite ist deshalb nicht automatisch eine Freigabe jeder Operation. Instanz- und Datenbanklisten begrenzen den sichtbaren Datenbereich.

Nur Admins verwalten Admin-Konten oder vergeben die Rolle `admin`. Benutzerlisten der Anwendung über `Auth` sind davon unabhängig.

## Geschützte Werkzeuge

| Werkzeug | Freigabe |
| --- | --- |
| `storage`, `public_api`, `distributed`, `updates` | Nur Admins |
| `php_exec` | Admin und `UI.allow_php_exec = true` |
| `plugins` | Admin und `UI.allow_plugin_upload = true` |
| `reinstall` | Admin und `UI.allow_reinstall = true` |
| `env` | Admin immer; andere Konten nur mit `UI.allow_config_edit` und passendem Recht beziehungsweise Werkzeugzugriff |

Die vier Freigabeschalter lassen sich nicht über den Konfigurationseditor umstellen. Sie müssen direkt in `.framework.env.php` geändert werden. So kann eine erlaubte JSON-Konfigurationsänderung nicht selbst die PHP-Ausführung freischalten.

## Migration älterer Konten

Ältere Nicht-Admin-Konten mit leerem Rechtefeld hatten implizit Vollzugriff. Die einmalige Umstellung macht diesen Zugriff durch `permissions = *` ausdrücklich und vermerkt die betroffenen Konten. Admins sollten die Liste prüfen und Rechte gezielt begrenzen. Neue Konten mit leerem Feld erhalten nur ihre Rollenrechte.

## Entwicklung eigener UI-Seiten

`GreenQLUIv2Helper` bündelt Anmeldung, CSRF-Prüfung, Rechte und Datenzugriff. `gbdbui_layout_start()` und `gbdbui_layout_end()` aus `_shared.php` stellen das Layout bereit. Neue Werkzeuge benötigen Einträge in Router und Berechtigungslogik; eine neue PHP-Datei allein reicht nicht.

Formulare und direkte Aktionen serverseitig prüfen. Systeminstanzen und reservierte Namen nicht als normale Fachbereiche anbieten. Statische Dateien über den vorhandenen Asset-Einstieg ausliefern, ohne den ganzen Framework-Ordner öffentlich freizugeben.
