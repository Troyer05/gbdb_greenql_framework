<?php
declare(strict_types=1);

require_once __DIR__ . "/greenql_base.trait.php";
require_once __DIR__ . "/greenql_parser.trait.php";
require_once __DIR__ . "/greenql_io.trait.php";
require_once __DIR__ . "/greenql_rows.trait.php";
require_once __DIR__ . "/greenql_runtime.trait.php";
require_once __DIR__ . "/greenql_execution.trait.php";

/**
 * Zentraler GreenQL-Compiler.
 * Alte GreenQLv2-Namen bleiben als Alias erhalten, nach außen soll nur noch GreenQL genutzt werden.
 */
class GreenQL {
    private static string $driver = "GBDB";
    private static string $instance = "";
    private static string $defaultLogFile = "";

    use GreenQL_BaseTrait;
    use GreenQL_ParserTrait;
    use GreenQL_IoTrait;
    use GreenQL_RowsTrait;
    use GreenQL_RuntimeTrait;
    use GreenQL_ExecutionTrait;
}

if (!class_exists("GreenQLv2", false)) { class_alias("GreenQL", "GreenQLv2"); }

if (!class_exists("GreenQLv3", false)) { class_alias("GreenQL", "GreenQLv3"); }

if (!class_exists("GreenQLv4", false)) { class_alias("GreenQL", "GreenQLv4"); }
