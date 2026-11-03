# 11 – Plugins und Produktanbindungen

Der Autoloader lädt die PHP-Dateien unter `gbdb_framework/plugins/`. Der aktuelle Quellstand enthält genau eine implementierte Produktanbindung und drei Klassen als Platzhalter.

| Klasse | Datei | Tatsächlicher Stand |
| --- | --- | --- |
| `MqrApi` | `plugins/museumqr.php` | MuseumQR-Leseaufrufe über cURL implementiert |
| `mRoot` | `plugins/mroot.php` | Leere Klasse, keine Lizenz- oder Update-Methoden |
| `ShareSuiteAPI` | `plugins/sharesuite.php` | Leere Klasse, keine produktiven API-Aufrufe |
| `EqrAPI` | `plugins/eventqr.php` | Leere Klasse, keine produktiven API-Aufrufe |

Konfigurationswerte allein machen die drei Platzhalter nicht funktionsfähig. Frühere Aussagen über vollständige Lizenzprüfung, Release-Installation oder produktive ShareSuite-/EventQR-Anbindungen treffen auf dieses Paket nicht zu.

## MuseumQR

`MQR.api_url` und `MQR.api_key` in der zentralen Vars-Datei einstellen. Der Wrapper sendet JSON per POST mit `auth_key` und dem gewünschten `get`-Wert. Die cURL-Erweiterung ist hier erforderlich; der Wrapper bietet keinen Stream-Ersatz.

| Methode | Inhalt |
| --- | --- |
| `MqrApi::getFeedback($item_id = '')` | Rückmeldungen, optional zu einem Objekt |
| `MqrApi::getObjects()` | Objekte |
| `MqrApi::getObject($oid)` | Einzelnes Objekt |
| `MqrApi::getSettings()` | Einstellungen |
| `MqrApi::getLangs()` | Sprachen |
| `MqrApi::getTours()` | Touren |

Die Antworten werden als Arrays aus der Gegenstelle übernommen. Bei Netzwerk- oder JSON-Fehlern erzeugt der Wrapper ein Fehlerarray. Nicht für alle Antworten ein einheitliches GBDB-Public-API-Format voraussetzen.

## Framework-Updates

Der eigenständige Framework-Updater liegt unter `gbdb_framework/updater/`. Er ist mit dem Autoloader und der UI verbunden und benötigt die leere `mRoot`-Klasse nicht. Verfahren, Vertrauensanker und Migrationen beschreibt die [Updater-Anleitung](../PHP/FRAMEWORK_UPDATER.md).

## Eigene Erweiterungen

Eigene Klassen mit eindeutigem Namen versehen, Eingaben prüfen und Zugangsdaten aus der lokalen Konfiguration lesen. PHP-Uploads sind ausführbarer Code; die UI erlaubt den Plugin-Upload nur Admins bei gesetztem `UI.allow_plugin_upload`.

Eigener Code bleibt beim Framework-Update grundsätzlich erhalten. Belegt er jedoch denselben Pfad wie eine neue Release-Datei, entsteht ein ausdrücklich zu behandelnder Konflikt. Vor Aktualisierungen eine Sicherung anlegen.
