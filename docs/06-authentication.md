# 06 – Anmeldung und Benutzer

GBDB unterscheidet **Anwendungsbenutzer** über `Auth` und **Verwaltungskonten** der GBDB-Oberfläche über `GreenQLUIv2Helper`. Ihre Speicher, Rollen und Anmeldeabläufe sind getrennt.

## Anwendungsanmeldung

Die öffentliche Klasse `Auth` liegt in `runtime/auth_facade.php` und nutzt die interne `GBDBAuthEngine` aus `core/auth.php`. Sie bindet zusammengehörige Speicheroperationen in die Laufzeit ein.

Die Struktur lässt sich ohne Anmeldeprüfung vorbereiten:

```php
$struktur = Auth::createStructure();
```

Registrierung, zum Beispiel in einem bereits validierten Formularhandler:

```php
$ergebnis = Auth::user_registration(
    username: $benutzername,
    email: $email,
    passwordAsPlain: $passwort,
    verifyMail: true
);
```

Hier kommen die Variablen aus der eigenen Eingabeprüfung. Für E-Mail-Bestätigung und 2FA muss die Mailanbindung funktionieren. Die Konfiguration enthält dafür unter anderem `AUTH` und `MAIL.api_url`.

Anmeldung:

```php
$anmeldung = Auth::login($benutzernameOderEmail, $passwort);

if (($anmeldung['ok'] ?? false) !== true) {
    // Die Anwendungsoberfläche zeigt eine passende Fehlermeldung.
}
```

Anmeldung und andere Auth-Aktionen können Cookies setzen oder weiterleiten. Sie müssen daher vor der Ausgabe von HTML erfolgen. Für eine 2FA-Anmeldung auch den im Ergebnis genannten Folgeschritt auswerten; ein begonnener Ablauf ist noch keine abgeschlossene Sitzung.

## Wichtige Methoden

| Methode | Aufgabe |
| --- | --- |
| `Auth::createStructure()` | Auth-Speicher vorbereiten |
| `Auth::user_registration(…)` | Anwendungskonto anlegen |
| `Auth::login($usernameOrEmail, $passwordPlainText)` | Anmeldung beginnen beziehungsweise abschließen |
| `Auth::init()` | Aktuelle Sitzung prüfen und bereinigte Benutzerdaten liefern; kann bei fehlender Sitzung beenden oder weiterleiten |
| `Auth::logout($error = '', $jwt = '')` | Sitzung beenden |
| `Auth::get_user($uid)` | Anwendungskonto lesen |
| `Auth::getUsers($limit)` | Konten lesen |
| `Auth::get_jwt_user($jwt)` | Benutzer zu einem Sitzungstoken ermitteln |
| `Auth::edit_user(…)` | Kontodaten mit der vollständigen Parametersignatur ändern |
| `Auth::delete_user($uid)` | Konto löschen |
| `Auth::verify_email($token)` | E-Mail-Bestätigung verarbeiten |
| `Auth::resendVerification($usernameOrEmail)` | Bestätigung erneut anstoßen |
| `Auth::verify_2fa_code($code)` | 2FA-Schritt prüfen |
| `Auth::hashPass($password)` | Passwort über die geerbte Hash-Funktion verarbeiten |

Die vollständigen Signaturen stehen in der [Hilfsklassenreferenz](99-api-reference/core-helper-methods.md). Frühere Beispiele mit `newUser()`, `me()`, `check()`, `editUser()` oder `verifyEmail()` bilden die aktuelle lokale `Auth`-API nicht ab. Einige ähnlich benannte Methoden gehören zum SecondServer-Client.

## Sitzungstoken und Ablaufzeiten

Die Implementierung nennt Cookie und Token-Tabelle `jwt`. Daraus folgt keine standardisierte dreiteilige JWT-Darstellung für externe Bibliotheken: Token als undurchsichtigen Sitzungswert behandeln und über die Framework-Methoden prüfen.

Benutzer, Sitzungstoken, E-Mail-Bestätigungen und 2FA liegen in der konfigurierten Auth-Instanz. `AUTH.jwt_expires_in_minutes`, `mail_verify_token_expires_in_minutes` und `2fa_code_expires_in_minutes` steuern die zugehörigen Laufzeiten. Öffentliche Anmeldeseiten müssen im vorgesehenen Ausnahmenmodell berücksichtigt werden, bevor `Auth::init()` als Schutz aufgerufen wird.

Die Mailvorlagen liegen unter:

- `gbdb_framework/public/includes/mail_templates/verify_mail.html`
- `gbdb_framework/public/includes/mail_templates/tfa_mail.html`

## UI-Konten

Die Verwaltung speichert ihre Konten in einer internen Systeminstanz. Die erste Anlage benötigt den [Setup-Token](02-installation-and-bootstrapping.md); danach verwalten berechtigte Konten Benutzer über die Oberfläche.

| Rolle | Grundrechte |
| --- | --- |
| `admin` | Vollzugriff einschließlich geschützter Verwaltungswerkzeuge |
| `maintainer` | Lesen, Schreiben, Struktur, Wartung, Aufträge und Sicherungen |
| `editor` | Lesen und Schreiben |
| `viewer` | Lesen |

Zusatzrechte und Werkzeuglisten ergänzen das Rollenmodell. Leere Zusatzrechte bedeuten bei neuen Konten nur die Rollenrechte. Die einmalige Migration älterer Konten mit vorher implizitem Vollzugriff ist in der [UI-Anleitung](09-ui-and-admin.md) beschrieben.

UI-2FA ist aus der 2FA-Funktion der Anwendungsanmeldung nicht automatisch ableitbar. Die vorhandene UI-Anmeldung prüft Benutzername und Passwort; weitere Schutzmaßnahmen müssen für diese Oberfläche gesondert eingerichtet werden.
