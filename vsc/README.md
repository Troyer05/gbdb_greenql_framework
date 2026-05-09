# greenQL Language Support Pro 2.0.0

Revolutionierte VSCode-Erweiterung für greenQL / GreenQLv2 im greenbucket® GBDB Framework.

## Highlights

- Semantic Highlighting zusätzlich zur TextMate-Grammatik
- Stark getrennte Farben für Commands, Runtime-Built-ins, User-Funktionen, Klassen, Typen, Variablen, Konstanten, SRV/SecondServer-Bridge und gefährliche Befehle
- Ultra-buntes Dark Theme: `greenQL Pro Ultra Semantic 2026`
- IntelliSense für GreenQL Commands, Clauses, Runtime-Funktionen und Kontext-Snippets
- Signature Help für wichtige Built-ins wie `param()`, `ENV()`, `fusion()`, `loadPattern()`, `get_data()`, `add_data()`, `fulltext_search()` usw.
- Hover-Erklärungen für Keywords, Built-ins, Typen und Clauses
- Document Symbols für Klassen, Funktionen, Variablen, META, ROOT und BRANCH
- Folding für `{ ... }` Blöcke
- Diagnostics für kaputte Variablennamen, unbekannte Typen, konstante Überschreibungen, Klammerfehler, nackte Werte, falsche Runtime-Aufrufe und unbekannte Commands
- Quick Fixes für `DECALRE`/`DELACE` → `DECLARE` und nackte Variablennamen
- Formatierung für Einrückungen
- Command Palette Aktionen:
  - `greenQL: Insert Mega Demo Script`
  - `greenQL: Show Built-ins Cheat Sheet`
  - `greenQL: Wrap Selection in OUTPUT`
  - `greenQL: Wrap Selection in LOG`

## Installation

```bash
code --install-extension greenql-language-2.0.0.vsix --force
```

Danach Theme auswählen:

```text
Preferences: Color Theme → greenQL Pro Ultra Semantic 2026
```
