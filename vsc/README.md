# GreenQL-Sprachunterstützung für VS Code

Version **2.1.2** gemäß `package.json`; vorausgesetzt wird VS Code **ab 1.90.0**. Die Erweiterung unterstützt GreenQL aus dem GBDB Framework; sie führt selbst keine Datenbankabfragen aus.

## Installation

Das mitgelieferte Paket heißt `greenql-language-2.1.2.vsix`. In VS Code über die Erweiterungsansicht **Install from VSIX…** auswählen. Alternativ aus diesem Verzeichnis:

```bash
code --install-extension ./greenql-language-2.1.2.vsix --force
```

Anschließend eine Datei mit Endung `.gql`, `.greenql`, `.gq`, `.gbdb` oder `.gdb` öffnen. Das mitgelieferte dunkle Theme heißt **greenQL Pro Ultra Semantic 2026** und lässt sich über die Theme-Auswahl aktivieren.

## Funktionen

- Syntax- und semantische Hervorhebung für Befehle, Typen, Variablen, Konstanten, Funktionen, Klassen und SRV-Aufrufe.
- Vorschläge, Signaturhilfe und Kurzerklärungen für bekannte Befehle und eingebaute Funktionen.
- Dokumentstruktur, Blockfaltung, Einrückung und Formatierung.
- Statische Diagnose unter anderem für Variablennamen, Typangaben, Klammern und bekannte Schreibfehler.
- Snippets für typische GreenQL-Abläufe.

Die Befehlspalette enthält diese tatsächlichen Befehlsnamen:

| Befehl | Aufgabe |
| --- | --- |
| `greenQL: Insert Mega Demo Script` | Umfangreiches Beispielschema einfügen |
| `greenQL: Show Built-ins Cheat Sheet` | Übersicht bekannter eingebauter Funktionen öffnen |
| `greenQL: Wrap Selection in OUTPUT` | Auswahl mit `OUTPUT` umgeben |
| `greenQL: Wrap Selection in LOG` | Auswahl mit `LOG` umgeben |

## Grenzen

Die Diagnose ist eine lokale statische Analyse. Sie kennt weder Datenbestand noch laufenden Instanzkontext, API-Rechte oder GDP-Policies. Eine fehlerfreie Anzeige ersetzt keine Prüfung des Skriptergebnisses durch GBDB. Besonders umfangreiche Demo-Skripte vor der Ausführung auf einer getrennten Instanz prüfen.

## Entwicklung

Quellcode: `src/extension.js`, Grammatik: `syntaxes/greenql.tmLanguage.json`, Snippets: `snippets/greenql.code-snippets`, Theme: `themes/greenql-bunt-color-theme.json`.

JavaScript prüfen:

```bash
node --check src/extension.js
```

Für ein neu gebautes VSIX-Paket müssen die in `package.json` definierten Entwicklungsabhängigkeiten verfügbar sein. Die mitgelieferte VSIX wurde bei dieser Dokumentationsüberarbeitung nur um die aktuelle README berichtigt; Manifest, Versionsnummer und Erweiterungscode bleiben unverändert. Künftige Änderungen an der README aktualisieren das Paket nicht automatisch. Das GBDB-Handbuch liegt im Repository unter `docs/README.md`; innerhalb eines separat installierten VSIX-Pakets ist dieses Handbuch nicht enthalten.
