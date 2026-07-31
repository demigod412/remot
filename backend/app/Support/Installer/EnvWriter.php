<?php

namespace App\Support\Installer;

/**
 * Minimal .env reader/writer used by the installer.
 * Replaces existing keys in place, appends new ones, and quotes values that need it.
 */
class EnvWriter
{
    public static function set(array $values, ?string $path = null): void
    {
        $path = $path ?: base_path('.env');

        if (! file_exists($path)) {
            // Seed from the example template if .env is missing.
            $example = base_path('.env.example');
            file_put_contents($path, file_exists($example) ? file_get_contents($example) : '');
        }

        $content = file_get_contents($path);

        foreach ($values as $key => $value) {
            $line = $key.'='.self::format($value);

            if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $content)) {
                $content = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $content);
            } else {
                $content = rtrim($content, "\n")."\n".$line."\n";
            }
        }

        file_put_contents($path, $content);
    }

    private static function format(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        // Quote if it contains whitespace or special characters.
        if ($value === '' || preg_match('/\s|#|"|\'/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}
