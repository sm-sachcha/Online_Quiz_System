<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'timezone' => config('app.timezone'),
        ];

        // Get all available timezones
        $timezones = timezone_identifiers_list();

        return view('master-admin.settings.index', compact('settings', 'timezones'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
        ]);

        try {
            // Update .env file
            $this->updateEnvironmentFile([
                'APP_NAME' => '"' . $request->app_name . '"',
                'APP_URL' => $request->app_url,
                'APP_TIMEZONE' => $request->timezone,
            ]);

            // Clear all caches to apply changes
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            // Update config values in memory
            config(['app.name' => $request->app_name]);
            config(['app.url' => $request->app_url]);
            config(['app.timezone' => $request->timezone]);

            // Set PHP default timezone
            date_default_timezone_set($request->timezone);

            $message = "System settings updated successfully!<br>Timezone changed to: <strong>" . $request->timezone . "</strong>";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function maintenance()
    {
        return view('master-admin.settings.maintenance');
    }

    public function toggleMaintenance(Request $request)
    {
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            $message = 'Application is now live.';
        } else {
            Artisan::call('down');
            $message = 'Application is now in maintenance mode.';
        }

        return back()->with('success', $message);
    }

    public function cache()
    {
        return view('master-admin.settings.cache');
    }

    public function clearCache(Request $request)
    {
        $request->validate([
            'type' => 'required|in:all,config,route,view,cache,events'
        ]);

        switch ($request->type) {
            case 'all':
                Artisan::call('optimize:clear');
                break;
            case 'config':
                Artisan::call('config:clear');
                break;
            case 'route':
                Artisan::call('route:clear');
                break;
            case 'view':
                Artisan::call('view:clear');
                break;
            case 'cache':
                Artisan::call('cache:clear');
                break;
            case 'events':
                Artisan::call('event:clear');
                break;
        }

        return back()->with('success', ucfirst($request->type) . ' cache cleared successfully.');
    }

    public function logs()
    {
        $logFile = storage_path('logs/laravel.log');
        
        $logs = [];
        if (File::exists($logFile)) {
            $logs = array_slice(file($logFile), -100);
        }

        return view('master-admin.settings.logs', compact('logs'));
    }

    public function clearLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return back()->with('success', 'Logs cleared successfully.');
    }

    public function info()
    {
        $timezone = config('app.timezone');
        
        $info = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_connection' => config('database.default'),
            'database_name' => config('database.connections.' . config('database.default') . '.database'),
            'server_os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => $timezone,
            'php_timezone' => date_default_timezone_get(),
            'current_time' => now()->format('Y-m-d H:i:s'),
            'current_time_utc' => now()->utc()->format('Y-m-d H:i:s'),
        ];

        return view('master-admin.settings.info', compact('info'));
    }

    private function updateEnvironmentFile($data)
    {
        $envFile = base_path('.env');
        
        foreach ($data as $key => $value) {
            $content = file_get_contents($envFile);
            
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= PHP_EOL . "{$key}={$value}";
            }
            
            file_put_contents($envFile, $content);
        }
    }
}