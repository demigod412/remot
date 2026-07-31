<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Support\Installer\EnvatoVerifier;
use App\Support\Installer\EnvWriter;
use App\Support\Installer\Requirements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    /* ----------------------------------------------------------- Steps */

    public function welcome()
    {
        return view('install.welcome');
    }

    public function requirements()
    {
        return view('install.requirements', [
            'groups' => Requirements::all(),
            'passes' => Requirements::passes(),
        ]);
    }

    public function license()
    {
        // Hard-stop: don't let buyers proceed past failed server requirements,
        // even by navigating straight to this URL.
        if (! Requirements::passes()) {
            return redirect()->route('install.requirements');
        }

        return view('install.license');
    }

    public function verifyLicense(Request $request)
    {
        $request->validate([
            'purchase_code'  => ['required', 'string'],
            'purchase_token' => ['required', 'string'],
        ]);

        $result = EnvatoVerifier::verify(
            $request->input('purchase_code'),
            $request->input('purchase_token'),
        );

        if (! $result['verified']) {
            return back()->withInput()->withErrors(['purchase_code' => $result['message']]);
        }

        session([
            'install.purchase_code'  => trim($request->input('purchase_code')),
            'install.purchase_token' => trim($request->input('purchase_token')),
            'install.license'        => $result,
            'install.step_license'   => true,
        ]);

        return redirect()->route('install.database')->with('status', $result['message']);
    }

    public function database()
    {
        abort_unless(session('install.step_license'), 403, 'Verify your purchase code first.');

        return view('install.database');
    }

    public function saveDatabase(Request $request)
    {
        abort_unless(session('install.step_license'), 403);

        $data = $request->validate([
            'db_host'     => ['required', 'string'],
            'db_port'     => ['required', 'string'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        // Test the connection on a throwaway connection name.
        config(['database.connections.install_probe' => [
            'driver'    => 'mysql',
            'host'      => $data['db_host'],
            'port'      => $data['db_port'],
            'database'  => $data['db_database'],
            'username'  => $data['db_username'],
            'password'  => $data['db_password'] ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]]);

        try {
            DB::connection('install_probe')->getPdo();
            DB::disconnect('install_probe');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'db_host' => 'Could not connect: '.$e->getMessage(),
            ]);
        }

        EnvWriter::set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $data['db_host'],
            'DB_PORT'       => $data['db_port'],
            'DB_DATABASE'   => $data['db_database'],
            'DB_USERNAME'   => $data['db_username'],
            'DB_PASSWORD'   => $data['db_password'] ?? '',
        ]);

        Artisan::call('config:clear');

        session(['install.step_database' => true]);

        return redirect()->route('install.settings');
    }

    public function settings()
    {
        abort_unless(session('install.step_database'), 403, 'Configure the database first.');

        return view('install.settings', [
            'appName' => config('app.name', 'Job Station'),
            'appUrl'  => rtrim(request()->getSchemeAndHttpHost(), '/'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        abort_unless(session('install.step_database'), 403);

        $data = $request->validate([
            'app_name'       => ['required', 'string', 'max:60'],
            'app_url'        => ['required', 'url'],
            'admin_name'     => ['required', 'string', 'max:60'],
            'admin_username' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_\.]+$/'],
            'admin_email'    => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'seed_demo'      => ['nullable', 'boolean'],
        ]);

        // 1) Persist app + license settings to .env
        EnvWriter::set([
            'APP_NAME'         => $data['app_name'],
            'APP_URL'          => rtrim($data['app_url'], '/'),
            'APP_ENV'          => 'production',
            'APP_DEBUG'        => false,
            'PURCHASE_CODE'    => session('install.purchase_code', ''),
            // Saved so the scheduled licence re-check can re-verify the buyer's purchase.
            'ENVATO_API_TOKEN' => session('install.purchase_token', ''),
        ]);

        Artisan::call('config:clear');

        // 2) Rotate the buyer's temporary setup key to a fresh, unique key for
        //    this installation. The marketplace package never ships a .env.
        Artisan::call('key:generate', ['--force' => true]);

        // 3) Migrate + seed baseline data
        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'app_name' => 'Migration/seed failed: '.$e->getMessage(),
            ]);
        }

        // 4) Replace the seeded default admin with the buyer's credentials
        try {
            $admin = \App\Models\Admin::query()->orderBy('id')->first();
            $payload = [
                'name'     => $data['admin_name'],
                'username' => $data['admin_username'],
                'email'    => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
            ];

            if ($admin) {
                $admin->update($payload);
                // Remove any other (default/demo) admins for safety.
                \App\Models\Admin::query()->where('id', '!=', $admin->id)->delete();
            } else {
                \App\Models\Admin::create($payload);
            }

            // Reflect the chosen brand name in site settings if present.
            if (class_exists(\App\Models\AppSetting::class) && \App\Models\AppSetting::query()->exists()) {
                \App\Models\AppSetting::query()->limit(1)->update(['site_name' => $data['app_name']]);
            }
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'admin_email' => 'Could not create the admin account: '.$e->getMessage(),
            ]);
        }

        // 4b) Optionally import demo/sample data (buyer opt-in on the form).
        //     Runs after the baseline seeders so its dependencies (categories,
        //     payment channels, etc.) already exist. Never block a successful
        //     install on demo data — surface a warning instead.
        $demoSeeded = false;
        if ($request->boolean('seed_demo')) {
            try {
                Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\DemoSeeder::class,
                    '--force' => true,
                ]);
                $demoSeeded = true;
                session(['install.demo_seeded' => true]);
            } catch (\Throwable $e) {
                session(['install.demo_warning' => 'Your site is installed, but demo data could not be imported: '.$e->getMessage()]);
            }
        }

        // 5) Write the install lock file. We persist the admin email, app URL and
        //    demo flag here too: regenerating APP_KEY above rotates the encryption
        //    key, which drops the install session on the next request, so the
        //    finished screen can't rely on the session for these values.
        $lock = [
            'app'          => 'Job Station',
            'version'      => config('jobstation.version', '1.0.0'),
            'purchase_code'=> session('install.purchase_code'),
            'license'      => session('install.license'),
            'admin_email'  => $data['admin_email'],
            'app_url'      => rtrim($data['app_url'], '/'),
            'demo_seeded'  => $demoSeeded,
            'installed_at' => now()->toIso8601String(),
        ];
        file_put_contents(storage_path('installed'), json_encode($lock, JSON_PRETTY_PRINT));

        Artisan::call('optimize:clear');

        session([
            'install.completed' => true,
            'install.admin_email' => $data['admin_email'],
            'install.app_url' => rtrim($data['app_url'], '/'),
        ]);

        return redirect()->route('install.finished');
    }

    public function finished()
    {
        abort_unless(session('install.completed') || is_app_installed(), 403);

        // The install session may have been dropped when APP_KEY was rotated
        // during setup, so fall back to the values persisted in the lock file.
        $lock = [];
        if (is_file($lockPath = storage_path('installed'))) {
            $lock = json_decode((string) file_get_contents($lockPath), true) ?: [];
        }

        return view('install.finished', [
            'adminEmail'  => session('install.admin_email') ?: ($lock['admin_email'] ?? ''),
            'appUrl'      => session('install.app_url') ?: ($lock['app_url'] ?? url('/')),
            'demoSeeded'  => (bool) (session('install.demo_seeded') ?: ($lock['demo_seeded'] ?? false)),
            'demoWarning' => session('install.demo_warning'),
        ]);
    }
}
