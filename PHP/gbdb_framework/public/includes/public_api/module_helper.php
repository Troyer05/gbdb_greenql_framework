<?php

class PublicAPI_ModuleHelper {
    public static function rights(array $actions, string $action = ""): array {
        if ($action == "") {
            return self::allRights($actions);
        }

        return $actions[$action]["rights"] ?? [];
    }

    public static function handle(string $class, array $actions, string $action, array $body, array $keyData): mixed {
        if (!isset($actions[$action])) {
            return [
                "status" => 404,
                "data" => "unknown action"
            ];
        }

        $method = (string) ($actions[$action]["method"] ?? "");

        if ($method == "" || !method_exists($class, $method)) {
            return [
                "status" => 500,
                "data" => "invalid action handler"
            ];
        }

        return $class::{$method}($body, $keyData);
    }

    public static function requireParams(array $body, array $params): bool|array {
        foreach ($params as $param) {
            if (!isset($body[$param])) {
                return [
                    "status" => 400,
                    "data" => "missing param: " . $param
                ];
            }
        }

        return true;
    }

    public static function str(array $body, string $key, string $default = ""): string {
        return (string) ($body[$key] ?? $default);
    }

    public static function arr(array $body, string $key): array {
        if (!isset($body[$key]) || !is_array($body[$key])) {
            return [];
        }

        return $body[$key];
    }

    public static function rightsFromKey(array $keyData): array {
        $rights = $keyData["rights"] ?? [];

        if (is_array($rights)) {
            return $rights;
        }

        $rights = trim((string) $rights);

        if ($rights == "") {
            return [];
        }

        $json = json_decode($rights, true);

        if (is_array($json)) {
            return $json;
        }

        $parts = explode(",", $rights);
        $out = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part != "") {
                $out[] = $part;
            }
        }

        return $out;
    }

    private static function allRights(array $actions): array {
        $rights = [];

        foreach ($actions as $action) {
            foreach (($action["rights"] ?? []) as $right) {
                if (!in_array($right, $rights)) {
                    $rights[] = $right;
                }
            }
        }

        return $rights;
    }
}