<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private array $settingKeys = [
        'api_base_url',
        'endpoint_discharge',
        'endpoint_load',
        'endpoint_release',
        'endpoint_receive',
        'auto_send_enabled',
        'auto_send_time',
        'email_report_enabled',
        'email_report_recipients',
    ];

    public function index()
    {
        $settings = [];
        foreach ($this->settingKeys as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        $settings['api_token_set']           = ! empty(Setting::get('api_token'));
        $settings['email_smtp_password_set']  = ! empty(Setting::get('email_smtp_password'));

        return Inertia::render('Settings/Index', ['settings' => $settings]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'api_base_url'            => ['nullable', 'url'],
            'endpoint_discharge'      => ['nullable', 'url'],
            'endpoint_load'           => ['nullable', 'url'],
            'endpoint_release'        => ['nullable', 'url'],
            'endpoint_receive'        => ['nullable', 'url'],
            'api_token'               => ['nullable', 'string'],
            'auto_send_enabled'       => ['boolean'],
            'auto_send_time'          => ['nullable', 'date_format:H:i'],
            'email_report_enabled'    => ['boolean'],
            'email_report_recipients' => ['nullable', 'string'],
            'email_smtp_password'     => ['nullable', 'string'],
        ]);

        foreach ($this->settingKeys as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        if ($request->filled('api_token')) {
            Setting::set('api_token', Crypt::encryptString($request->api_token));
        }

        if ($request->filled('email_smtp_password')) {
            Setting::set('email_smtp_password', Crypt::encryptString($request->email_smtp_password));
        }

        return back()->with('success', 'Settings saved successfully.');
    }
}
