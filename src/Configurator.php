<?php

declare(strict_types=1);

namespace HeikoHardt\Typo3EnvConfigurator;

final class Configurator
{
    private const PREFIX = 'TYPO3__';

    private const KNOWN_TYPES = [
        'INT', 'INTEGER', 'FLOAT', 'DOUBLE', 'BOOL', 'BOOLEAN', 'ARRAY', 'JSON', 'NULL',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function fromEnvironment(): array
    {
        $result = [];

        foreach (getenv() as $key => $value) {
            if (!str_starts_with($key, self::PREFIX)) {
                continue;
            }

            try {
                $config = self::envStringToArray(substr($key, strlen(self::PREFIX)), $value);
                $result = array_replace_recursive($result, $config);
            } catch (\Throwable $e) {
                trigger_error(
                    sprintf('Konnte Env-Var "%s" nicht parsen: %s', $key, $e->getMessage()),
                    E_USER_WARNING
                );
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function envStringToArray(string $key, string $value): array
    {
        $path = explode('__', $key);
        $result = self::parseValue($value);

        foreach (array_reverse($path) as $segment) {
            $result = [$segment => $result];
        }

        return $result;
    }

    private static function parseValue(string $value): mixed
    {
        if (!str_contains($value, ':')) {
            return $value;
        }

        [$type, $raw] = explode(':', $value, 2);
        $type = strtoupper($type);

        if (!in_array($type, self::KNOWN_TYPES, true)) {
            return $value;
        }

        return match ($type) {
            'INT', 'INTEGER' => (int)$raw,
            'FLOAT', 'DOUBLE' => (float)$raw,
            'BOOL', 'BOOLEAN' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'ARRAY' => array_map('trim', explode(',', $raw)),
            'JSON' => json_decode($raw, true, 512, JSON_THROW_ON_ERROR),
            'NULL' => null,
        };
    }
}
