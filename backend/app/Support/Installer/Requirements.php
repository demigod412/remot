<?php

namespace App\Support\Installer;

/**
 * Server requirement checks for the Job Station installer.
 */
class Requirements
{
    public const MIN_PHP = '8.2.0';

    public static function php(): array
    {
        return [
            'label'   => 'PHP >= '.self::MIN_PHP,
            'current' => PHP_VERSION,
            'passed'  => version_compare(PHP_VERSION, self::MIN_PHP, '>='),
        ];
    }

    /** Required PHP extensions for Laravel 12 + Job Station. */
    public static function extensions(): array
    {
        $required = [
            'BCMath'    => 'bcmath',
            'Ctype'     => 'ctype',
            'cURL'      => 'curl',
            'DOM'       => 'dom',
            'Fileinfo'  => 'fileinfo',
            'JSON'      => 'json',
            'Mbstring'  => 'mbstring',
            'OpenSSL'   => 'openssl',
            'PDO'       => 'pdo',
            'PDO MySQL' => 'pdo_mysql',
            'Tokenizer' => 'tokenizer',
            'XML'       => 'xml',
            'GD'        => 'gd',
        ];

        $out = [];
        foreach ($required as $label => $ext) {
            $out[] = [
                'label'  => $label,
                'passed' => extension_loaded($ext),
            ];
        }

        return $out;
    }

    /** Writable paths required during and after installation. */
    public static function permissions(): array
    {
        $paths = [
            'storage/'             => storage_path(),
            'storage/framework/'   => storage_path('framework'),
            'storage/logs/'        => storage_path('logs'),
            'bootstrap/cache/'     => base_path('bootstrap/cache'),
            '.env'                 => base_path('.env'),
        ];

        $out = [];
        foreach ($paths as $label => $path) {
            $out[] = [
                'label'  => $label,
                'passed' => file_exists($path) && is_writable($path),
            ];
        }

        return $out;
    }

    public static function all(): array
    {
        return [
            'php'         => [self::php()],
            'extensions'  => self::extensions(),
            'permissions' => self::permissions(),
        ];
    }

    public static function passes(): bool
    {
        if (! self::php()['passed']) {
            return false;
        }
        foreach (array_merge(self::extensions(), self::permissions()) as $check) {
            if (! $check['passed']) {
                return false;
            }
        }

        return true;
    }
}
