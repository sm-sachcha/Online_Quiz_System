<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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

        return view('master-admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string|timezone',
        ]);

        $this->updateEnvironmentFile([
            'APP_NAME' => '"' . $request->app_name . '"',
            'APP_URL' => $request->app_url,
            'APP_TIMEZONE' => $request->timezone,
        ]);

        Artisan::call('config:clear');

        return back()->with('success', 'System settings updated successfully.');
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
        $info = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_connection' => config('database.default'),
            'database_name' => config('database.connections.' . config('database.default') . '.database'),
            'server_os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];

        return view('master-admin.settings.info', compact('info'));
    }

    private function updateEnvironmentFile($data)
    {
        $envFile = base_path('.env');
        
        foreach ($data as $key => $value) {
            if (strpos(file_get_contents($envFile), $key) !== false) {
                file_put_contents($envFile, preg_replace(
                    '/^' . $key . '=.*/m',
                    $key . '=' . $value,
                    file_get_contents($envFile)
                ));
            } else {
                file_put_contents($envFile, PHP_EOL . $key . '=' . $value, FILE_APPEND);
            }
        }
    }
}