<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin user
        $users = [
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@dict-boc.local',
                'password' => 'x7mkt_QWE5r2plvj',
                'role' => 'admin',
            ],
            [
                'username' => 'kmorbita',
                'name' => 'Kinard Khin Orbita',
                'email' => 'kmorbita@anflocor.com',
                'password' => 'b9qrv_ASD4t8mkxl',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['username' => $user['username']],
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                ]
            );
        }

        // Default settings
        $defaults = [
            'api_base_url' => '',
            'api_token' => '',
            'endpoint_discharge' => '',
            'endpoint_load' => '',
            'endpoint_release' => '',
            'endpoint_receive' => '',
            'auto_send_enabled' => '1',
            'auto_send_time' => '00:05',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
