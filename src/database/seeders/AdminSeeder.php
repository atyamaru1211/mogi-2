<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // 管理者ユーザー1
        Admin::create([
            'name' => '管理者ユーザー1',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
        ]);

        // 管理者ユーザー2
        Admin::create([
            'name' => '管理者ユーザー2',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
