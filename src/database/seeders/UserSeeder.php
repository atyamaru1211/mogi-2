<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Hashファサードを使用
use App\Models\User; //★ Userモデルをuse
use Carbon\Carbon; // ★この行を追加★


class UserSeeder extends Seeder
{
    public function run()
    {
        // 一般ユーザー1
        User::create([
            'name' => '一般ユーザー1',
            'email' => 'general1@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => Carbon::now(), // ★この行を追加★
        ]);

        // 一般ユーザー2
        User::create([
            'name' => '一般ユーザー2',
            'email' => 'general2@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => Carbon::now(), // ★この行を追加★
        ]);
    }
}
