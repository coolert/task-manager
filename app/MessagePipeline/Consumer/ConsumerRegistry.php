<?php

namespace App\MessagePipeline\Consumer;

class ConsumerRegistry
{
    /** @var array<string, string> */
    protected static array $handlers = [];

    public static function register(string $pattern, string $consumerClass): void
    {
        self::$handlers[$pattern] = $consumerClass;
    }

    public static function resolve(string $routingKey): ?string
    {
        $exact          = [];
        $singleWildcard = [];
        $multiWildcard  = [];

        foreach (self::$handlers as $pattern => $handler) {
            if ($routingKey === $pattern) {
                $exact[] = $handler;

                continue;
            }

            $regex = self::patternToRegex($pattern);
            if (! preg_match($regex, $routingKey)) {
                continue;
            }

            if (str_contains($pattern, '#')) {
                $multiWildcard[] = $handler;
            } elseif (str_contains($pattern, '*')) {
                $singleWildcard[] = $handler;
            }
        }

        if (! empty($exact)) {
            return $exact[0];
        }

        if (! empty($singleWildcard)) {
            return $singleWildcard[0];
        }

        if (! empty($multiWildcard)) {
            return $multiWildcard[0];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$handlers;
    }

    public static function patternToRegex(string $pattern): string
    {
        // task.* -> task\.[^.]+
        // task.# -> task\..*
        // *.created -> [^.]+\.created
        // *.# -> [^.]+\..*

        $parts      = explode('.', $pattern);
        $regexParts = [];

        foreach ($parts as $part) {
            if ($part === '*') {
                $regexParts[] = '[^.]+';
            } elseif ($part === '#') {
                $regexParts[] = '.*';
            } else {
                $regexParts[] = preg_quote($part, '/');
            }
        }

        return '/^' . implode('\.', $regexParts) . '$/';
    }

    public static function clear(): void
    {
        self::$handlers = [];
    }
}
