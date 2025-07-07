<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    // 現在の日時情報がUIと同じ形式で出力されている
    public function test_current_datetime_display()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $now = Carbon::now();
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $japaneseDay = $weekdays[$now->dayOfWeek];

        $expectedDate = $now->format('Y年n月j日') . '(' . $japaneseDay . ')';
        $expectedTime = $now->format('H:i');

        $response->assertSeeText($expectedDate);
        $response->assertSeeText($expectedTime);
    }
}
