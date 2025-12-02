<?php

namespace App\MessagePipeline\Consumer;

use App\MessagePipeline\Consumer\Attributes\Consumes;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ConsumerDiscovery
{
    protected static ?string $scanPath = null;

    protected static ?string $baseNamespace = null;

    public static function setScanPath(string $path, string $namespace): void
    {
        self::$scanPath      = rtrim($path, '/');
        self::$baseNamespace = trim($namespace, '\\');
    }

    public static function reset(): void
    {
        self::$scanPath      = null;
        self::$baseNamespace = null;
    }

    /**
     * Automatically scan and register all Consumers.
     */
    public static function discover(): void
    {
        $path      = self::$scanPath      ?? app_path('MessagePipeline/Handlers');
        $namespace = self::$baseNamespace ?? 'App\\MessagePipeline\\Handlers';

        if (! File::exists($path)) {
            return;
        }

        $files = File::allFiles($path);
        foreach ($files as $file) {
            $className = self::fileToClass($file->getRealPath(), $path, $namespace);

            if (! $className || ! class_exists($className)) {
                continue;
            }

            $ref = new ReflectionClass($className);
            // skip abstract classes
            if ($ref->isAbstract()) {
                continue;
            }

            // search #[Consumes] attributes
            $attributes = $ref->getAttributes(Consumes::class);
            foreach ($attributes as $attr) {
                /** @var Consumes $instance */
                $instance = $attr->newInstance();
                $patterns = is_array($instance->patterns) ? $instance->patterns : [$instance->patterns];
                foreach ($patterns as $pattern) {
                    ConsumerRegistry::register($pattern, $className);
                }
            }
        }
    }

    /**
     * Convert file path to class name.
     */
    protected static function fileToClass(string $path, string $scanPath, string $baseNamespace): ?string
    {
        $relative = ltrim(str_replace($scanPath, '', $path), '/');
        $class    = str_replace(['/', '.php'], ['\\', ''], $relative);

        return $baseNamespace . '\\' . $class;
    }
}
