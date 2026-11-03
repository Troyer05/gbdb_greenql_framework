# 08 – SecondServer, SRV und SrvP

SecondServer verbindet eine PHP-Anwendung mit einem entfernten GBDB-Backend. Der öffentliche Client heißt **`SecondServer`**. `SrvP` ist eine historische Bezeichnung des Bereichs, keine zusätzlich registrierte Clientklasse.

## Bestandteile

| Bestandteil | Pfad relativ zu `PHP/gbdb_framework/` |
| --- | --- |
| Client `SecondServer` | `core/srvp.php` |
| Dispatcher `Srv` | `SRV/Srv.php` |
| Serveraktionen `SrvFunctions` | `SRV/SrvFunctions.php` |
| Auth-Adapter `SrvAuth` | `SRV/srv_auth.php` |
| Serverstart | `SRV/backend_api.php`, erreichbar über `PHP/backend.php` |
| Aufträge und Hilfsklasse `SRVJob` | `SRV/srv_modules/` |

## Konfiguration

Im Bereich `SRVP` der zentralen Vars-Datei auf dem Client eine vollständige URL eintragen, beispielsweise `https://backend.example.com/backend.php`, und einen zufälligen gemeinsamen Schlüssel mit mindestens **32 Zeichen** setzen. Auf dem Backend denselben `static_key` verwenden. Ohne ausreichend langen Schlüssel antwortet das Backend mit 503.

Wenn `SRVP.ip` nicht mit `.php` endet, hängt der Client `/backend.php` an. Das alte Feld `ssl` ersetzt keine vollständige HTTP-/HTTPS-URL; das Schema der URL bestimmt den Transport. HTTPS verwenden.

## Ablauf und Antwort

1. Der Client fordert mit `do = start_session(srvp.init)` und `static_auth` ein kurzlebiges Token an.
2. Der Client sendet die gewünschte Anweisung mit `tmp_token` **und erneut mit `static_auth`**.
3. Der Server prüft Zugangsdaten und Token, wendet die Instanz-Sandbox an und ruft die erlaubte Aktion auf.
4. Die Antwort enthält `ok`, numerischen `status` und `data`; Clientfehler können stattdessen `ok`, `message` und `data` enthalten.

Der statische Schlüssel wird also nicht nur bei der Tokenanforderung übertragen. Das temporäre Token ist kein Ersatz für TLS.

Der Server begrenzt POST-Inhalte auf 4 MiB und verwendet eine Ratenbegrenzung. Interne Instanzen sind für externe Instanzwahl gesperrt. Innerhalb der erlaubten Schnittstelle hat ein statischer Schlüssel dennoch weitreichenden Zugriff.

## Clientbeispiele

```php
$erreichbar = SecondServer::ping();
$treiber = SecondServer::driver();
$anmeldung = SecondServer::login($benutzername, $passwort);
$benutzer = SecondServer::getUser($uid);
$auftraege = SecondServer::listJobs();
$auftrag = SecondServer::startJob('Test', ['name' => 'Max']);
```

`ping()` liefert einen booleschen Wert; die meisten übrigen Methoden liefern Arrays. Das Beispiel verwendet die vorhandene Klasse `SRVJob_Test`. Der Auftragsname entspricht dem Klassensuffix; Dateiname und Auftragsname sind nicht automatisch identisch.

Weitere Clientmethoden unterstützen Registrierung, Abmeldung, E-Mail-Bestätigung und 2FA. Genaue Signaturen: [SecondServer-Referenz](99-api-reference/srv-methods.md).

## Eigene Aufträge

Eine PHP-Datei im Ordner `SRV/srv_modules/` kann eine Klasse mit Präfix `SRVJob_` und öffentlicher statischer Methode `__start_job()` bereitstellen:

```php
<?php

class SRVJob_Status {
    public static function __start_job() :array {
        $geraet = SRVJob::getString('geraet', 'unbekannt');

        return SRVJob::returnOk('Status gelesen', ['geraet' => $geraet]);
    }
}
```

Der Dispatcher stellt die Parameter über `SRVJob` bereit. Aufruf: `SecondServer::startJob('Status', ['geraet' => 'kiosk-1'])`. Eingaben im Auftrag selbst fachlich prüfen.

Die Methode läuft im aktuellen Backend-Aufruf. `startJob()` startet dadurch keinen dauerhaften Hintergrundprozess und umgeht keine Webspace-Zeitlimits. Aufwendige Abläufe müssen entsprechend aufgeteilt oder über eine eigene geeignete Ausführung gesteuert werden.

## Abgrenzung zu GDP

SecondServer überträgt entfernte Anweisungen. GDP repliziert Änderungen zwischen eigenständigen Datenbeständen und regelt Schreibautorität, Offline-Betrieb und Konflikte. Eine SecondServer-Verbindung erzeugt keine automatische Replikation.
