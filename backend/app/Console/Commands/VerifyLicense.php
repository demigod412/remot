<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use App\Support\Installer\EnvatoVerifier;
use Illuminate\Console\Command;

class VerifyLicense extends Command
{
    protected $signature = 'license:verify
                            {code? : Purchase code to check (defaults to the installed one)}
                            {--save : Persist the result into the install lock file}';

    protected $description = 'Verify a CodeCanyon / Envato purchase code (live if a token is configured, otherwise by format)';

    public function handle(): int
    {
        $code = $this->argument('code') ?: LicenseService::purchaseCode();

        if (! $code) {
            $this->error('No purchase code provided and none is recorded from installation.');
            return self::FAILURE;
        }

        $this->line('Mode: '.(LicenseService::liveMode() ? '<info>live</info> (Envato token configured)' : '<comment>offline</comment> (format only)'));
        $this->line('Checking '.$this->maskCode($code).' ...');

        $result = EnvatoVerifier::verify($code);

        if ($this->option('save') && LicenseService::purchaseCode() === trim($code)) {
            LicenseService::persist($result);
            $this->line('<comment>Result saved to the install lock.</comment>');
        }

        $rows = [
            ['Verified',        $result['verified'] ? 'yes' : 'no'],
            ['Mode',            $result['mode'] ?? '-'],
            ['Buyer',           $result['buyer'] ?? '-'],
            ['Item',            $result['item'] ?? '-'],
            ['Item ID',         $result['item_id'] ?? '-'],
            ['License type',    $result['license_type'] ?? '-'],
            ['Supported until', $result['supported_until'] ?? '-'],
            ['Message',         $result['message'] ?? '-'],
        ];
        $this->table(['Field', 'Value'], $rows);

        if ($result['verified']) {
            $this->info('Purchase code accepted.');
            return self::SUCCESS;
        }

        $this->error('Purchase code rejected: '.$result['message']);
        return self::FAILURE;
    }

    private function maskCode(string $code): string
    {
        $code = trim($code);
        if (strlen($code) < 12) {
            return $code;
        }

        return substr($code, 0, 8).'-****-****-****-'.substr($code, -6);
    }
}
