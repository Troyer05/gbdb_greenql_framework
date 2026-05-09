<?php

class PublicAPI_Test {
    private const ACTIONS = [
        "hello" => [
            "method" => "hello",
            "rights" => ["read"]
        ]
    ];

    public static function name(): string {
        return "test";
    }

    public static function requiresRights(string $action = ""): array {
        return PublicAPI_ModuleHelper::rights(self::ACTIONS, $action);
    }

    public static function handle(string $action, array $body, array $keyData): mixed {
        return PublicAPI_ModuleHelper::handle(self::class, self::ACTIONS, $action, $body, $keyData);
    }

    private static function hello(array $body, array $keyData): array {
        return [
            "hello" => "world"
        ];
    }
}