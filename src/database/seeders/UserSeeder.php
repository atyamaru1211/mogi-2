<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Hashファサードを使用
use App\Models\User; //★ Userモデルをuse
use Carbon\Carbon; // ★この行を追加★


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'テスト1',
            'email' => 'test1@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(), // ★この行を追加★
        ]);
    }
}
