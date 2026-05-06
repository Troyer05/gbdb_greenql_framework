# GBDB / GreenQL SQL-Ersatz Roadmap

Stand: 2026-05-06

Diese Datei ist absichtlich ehrlich formuliert. Ein Feature gilt nur dann als produktiv, wenn es in Code, Doku und Tests belastbar vorhanden ist. Vorbereitete Funktionen werden nicht mehr als fertig dargestellt.

## Status-Legende

| Status | Bedeutung |
|---|---|
| `stable` | Produktiv nutzbar und dokumentiert. |
| `beta` | Nutzbar, aber noch nicht final genug fuer kritische Produktivdaten. |
| `experimental` | Technisch vorhanden, aber Architektur, Tests oder Fehlerverhalten sind noch nicht stabil. |
| `prepared` | Schnittstelle oder Platzhalter ist vorbereitet, aber keine fertige Implementierung. |
| `missing` | Noch nicht implementiert. |

## Interne Feature-Matrix

Die Matrix ist auch im Code ueber `GBDB::sqlErsatzFeatureMatrix()` abrufbar.

| Feature | Status | Produktiv? | Hinweis |
|---|---:|---:|---|
| GreenQL Basic PICK | stable | ja | Basisabfragen sind vorhanden. |
| Komplexes GreenQL-WHERE | beta | nein | AND, OR, NOT, Klammern, IN, BETWEEN, IS NULL, IS NOT NULL und LIKE werden als AST geparst. |
| Schema-Constraints | experimental | nein | Schema-Bausteine existieren teilweise; harte Engine-Pruefung muss noch vollstaendig in Insert/Update/API/GreenQL greifen. |
| Primary/Unique/Index-Meta | experimental | nein | Index- und Meta-Strukturen sind vorhanden, aber noch nicht vollstaendig produktionshart. |
| Query Planner / Index-Nutzung | experimental | nein | QueryPlan existiert, aktive Index-Lookups muessen weiter ausgebaut werden. |
| QueryBuilder 2.0 | prepared | nein | Komfort-API ist geplant/vorbereitet. |
| Joins | experimental | nein | Keine vollstaendige relationale SQL-Join-Engine. |
| Transaktionen / Recovery | experimental | nein | WAL/Transaktionsbausteine vorhanden; Crash-Tests fehlen. |
| DatabaseBridge | beta | nein | Gemeinsame Schnittstelle existiert, SQL-Zweige noch nicht komplett. |
| SQL-Kompatibilitaet | prepared | nein | Keine vollstaendige SQL-Engine; nur geplante Light-Kompatibilitaet. |
| SQL zu GreenQL Translator | prepared | nein | Vorbereitet, nicht fertig. |
| PDO-Like Adapter | prepared | nein | Vorbereitet, nicht fertig. |
| SQLite-Import | prepared | nein | Vorbereitet, Dry-Run/Report noch offen. |
| MySQL-Dump-Light-Import | missing | nein | Noch nicht implementiert. |
| Public API v1 | beta | nein | API vorhanden, Versionierung/Batch/Cursor/Audit fehlen noch. |
| Backup/Restore/PITR | experimental | nein | Backup/Restore vorhanden, PITR noch nicht final. |
| Benchmarks | missing | nein | Systematische Grenzen sind noch nicht gemessen. |

## Woche 1: Basis aufraeumen & Status trennen

Umgesetzt in diesem Patch:

- `prepareSqlCompatibilityLayer()` markiert die SQL-Kompatibilitaet als `prepared` und `production_ready=false`.
- `prepareSqlToGreenQLTranslator()` markiert den Translator als `prepared` und `production_ready=false`.
- `preparePdoLikeAdapter()` markiert den PDO-Like Adapter als `prepared` und `production_ready=false`.
- `prepareSQLiteImportAdapter()` markiert SQLite-Import als `prepared` und `production_ready=false`.
- `GBDB::sqlErsatzFeatureMatrix()` liefert die interne Feature-Matrix.
- `GBDB::sqlErsatzFeatureStatus($feature)` liefert einzelne Statuswerte.
- Adapter-Konfigurationen enthalten `visible_label`, `message` und `fake_ready_blocked=true`.

## Woche 2: Komplexes WHERE-System

Umgesetzt in diesem Patch:

- `GreenQL::parseWhere()` wurde von einer einfachen Regex auf einen rekursiven WHERE-Parser mit AST umgestellt.
- Unterstuetzt werden:
  - `AND`
  - `OR`
  - Klammern
  - `IN [1,2,3]`
  - `BETWEEN ... AND ...`
  - `IS NULL`
  - `IS NOT NULL`
  - `LIKE`
  - `NOT`
- `GreenQL::rowMatch()` wertet den AST rekursiv aus.
- Einfache alte Bedingungen bleiben rueckwaertskompatibel, weil `field`, `op` und `value` weiter gesetzt werden.

### Beispiele

```greenql
PICK * FROM users WHERE active = 1 AND role = "admin"
PICK * FROM users WHERE role = "admin" OR role = "dev"
PICK * FROM users WHERE id IN [1,2,3]
PICK * FROM users WHERE created BETWEEN "2026-01-01" AND "2026-12-31"
PICK * FROM users WHERE deleted IS NULL
PICK * FROM users WHERE NOT (role = "guest")
PICK * FROM users WHERE email LIKE "%@example.com"
```

## Offene Roadmap Woche 3 bis 16

### Woche 3: Harte Constraints

- Schema um `required`, `unique`, `primary`, `type`, `enum`, `min`, `max`, `regex`, `default`, `nullable` erweitern.
- Constraint-Pruefung in `insertData()`, `editData()`, Public API Insert/Update, GreenQL `SEED` und `RESHAPE` einbauen.
- Einheitliche Fehler: `constraint_failed`, `duplicate_key`, `required_missing`, `invalid_type`, `invalid_enum`.

### Woche 4: Primary Key, Unique Index und Auto-ID

- Tabellen-Meta mit `primary_key`, `auto_increment`, `next_id`, `id_strategy` finalisieren.
- `nextID()` mit Locking und parallelen Writes haerten.
- Unique-/Primary-Index automatisch pflegen.
- Optional UUID/ULID/eigene PK-Spalten vorbereiten.

### Woche 5: Index-Nutzung aktiv machen

- Fullscan-Stellen pruefen: `GBDB::getData()`, GreenQL `PICK`, QueryBuilder, Public API Filter.
- Index-Lookup fuer `=`, `IN`, `unique`, `primary` einbauen.
- Query Planner mit Strategie, genutztem Index und Fullscan-Warnung erweitern.

### Woche 6: QueryBuilder 2.0

- Komfortmethoden wie `select`, `where`, `orWhere`, `whereIn`, `whereBetween`, `first`, `count`, `insertGetId`, `paginate` ueber Query-Engine fuehren.

### Woche 7: Joins verbessern

- INNER/LEFT JOIN pruefen.
- Aliase, mehrere Joins, Feldauswahl, Join + WHERE/SORT/LIMIT und Index-Nutzung umsetzen.

### Woche 8: Transaktionen und Crash-Recovery

- Mehrtabellen-/Mehrbase-/Parallelprozess-Tests.
- Crash mitten im Commit simulieren.
- WAL-Recovery, Rollback und Savepoints testen.
- Isolation dokumentieren.

### Woche 9: DatabaseBridge fertigstellen

- SQL-Zweige fuer Table-/Column-/Transaction-/Index-Operationen finalisieren.
- Mapping, Prefixe, Rueckgaben und Fehler normalisieren.

### Woche 10: SQL-Kompatibilitaet v1

- Light-SQL-Parser fuer SELECT/INSERT/UPDATE/DELETE/CREATE/DROP/ALTER.
- Prepared `?` und Named Parameter vorbereiten.
- Kein MySQL-1:1-Klon.

### Woche 11: SQL-/SQLite-Migration

- SQLite-Import finalisieren.
- MySQL-Dump-Light vorbereiten.
- Typen, Keys, Indexe, Daten, Dry-Run, Report und Zeilenvergleich einbauen.

### Woche 12: Public API v1 stabilisieren

- `/api/v1/...`, Fehlercodes, Batch-Endpunkte, Cursor-Pagination, Rate-Limit, Audit-Log und Doku.

### Woche 13: Backup, Restore und PITR

- Backup-Manifest, Restore in Temp-Instanz, Checksums, Rotation, verschluesselte Backups, PITR ueber WAL, Restore-Dry-Run.

### Woche 14: Performance-Tests und Benchmarks

- 1k/10k/100k Rows messen.
- Insert/Update/Delete/Query/Join/Export/Backup/Speicher/Locks dokumentieren.

### Woche 15: Dokumentation

- Core-, GreenQL-, QueryBuilder-, Constraints-, Index-, Transaction-, Migration-, API-, Backup-, Security- und Performance-Doku erweitern.

### Woche 16: Stabilisierung und RC1

- Test-Suite, Beispiele, alte Doku, API-Kontrakte, Installer/Update-System, Release Notes und RC1-Test abschliessen.

## Produktziel

GBDB soll kein MySQL-Klon werden. Das bessere Ziel ist:

> GBDB ist ein filebasierter PHP-App-Database-Layer mit GreenQL, optionaler SQL-Light-Kompatibilitaet und starker Integration in das greenbucket Framework.

Der wichtigste technische Block bleibt Woche 1 bis 5: WHERE, Constraints, Keys und Indexe.
