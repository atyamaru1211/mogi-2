<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Hashファサードを使用
use App\Models\Admin; //★ Adminモデルをuse

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::create([
            'name' => '管理者1',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password456'),
        ]);
    }
}
