<?php

namespace App\Services;

use App\Support\Installer\EnvatoVerifier;

/**
 * Reads and refreshes the CodeCanyon license recorded at install time.
 *
 * The installer writes the verified license into the lock file at
 * storage/installed. This service reads it back, exposes a normalised view for
 * the admin License screen, and can re-verify the stored purchase code against
 * Envato on demand (or on a schedule).
 */
class LicenseService
{
    public static function lockPath(): string
    {
        return storage_path('installed');
    }

    /** Raw decoded lock file contents. */
    public static function lock(): array
    {
        $path = self::lockPath();
        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    public static function purchaseCode(): ?string
    {
        $code = self::lock()['purchase_code'] ?? config('jobstation.purchase_code');

        return $code ? trim((string) $code) : null;
    }

    /** Whether live verification is configured (a seller token is present). */
    public static function liveMode(): bool
    {
        return filled(config('jobstation.envato.token'));
    }

    /**
     * Normalised license view for display.
     *
     * @return array{
     *   purchase_code:?string, masked:?string, verified:bool, mode:string,
     *   buyer:?string, item:?string, license_type:?string,
     *   supported_until:?string, verified_at:?string, checked_at:?string
     * }
     */
    public static function current(): array
    {
        $lock    = self::lock();
        $license = is_array($lock['license'] ?? null) ? $lock['license'] : [];
        $code    = self::purchaseCode();

        return [
            'purchase_code'   => $code,
            'masked'          => self::mask($code),
            'verified'        => (bool) ($license['verified'] ?? ($code !== null)),
            'mode'            => $license['mode'] ?? ($license['online'] ?? false ? 'live' : 'offline'),
            'buyer'           => $license['buyer'] ?? null,
            'item'            => $license['item'] ?? null,
            'license_type'    => $license['license_type'] ?? null,
            'supported_until' => $license['supported_until'] ?? null,
            'verified_at'     => $license['verified_at'] ?? ($lock['installed_at'] ?? null),
            'checked_at'      => $lock['license_checked_at'] ?? ($lock['installed_at'] ?? null),
        ];
    }

    /**
     * Re-verify the stored purchase code against Envato and persist the result.
     *
     * @return array The fresh EnvatoVerifier result.
     */
    public static function reverify(): array
    {
        $code = self::purchaseCode();

        if (! $code) {
            return EnvatoVerifier::verify(''); // yields an "invalid format" result
        }

        $result = EnvatoVerifier::verify($code);
        self::persist($result);

        return $result;
    }

    /** Write a verification result back into the lock file. */
    public static function persist(array $license): void
    {
        $path = self::lockPath();
        if (! is_file($path)) {
            return;
        }

        $lock = self::lock();
        $lock['license']             = $license;
        $lock['license_checked_at']  = now()->toIso8601String();

        file_put_contents($path, json_encode($lock, JSON_PRETTY_PRINT));
    }

    private static function mask(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        // Show the first and last block of the UUID, mask the middle.
        if (preg_match('/^([a-f0-9]{8})-.*-([a-f0-9]{12})$/i', $code, $m)) {
            return $m[1].'-****-****-****-'.substr($m[2], -6);
        }

        return substr($code, 0, 4).'••••'.substr($code, -4);
    }
}
