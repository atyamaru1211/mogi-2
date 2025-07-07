<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    // 勤務外の場合、勤怠ステータスが正しく表示される
    public function test_status_is_off_duty()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $response->assertSeeText('勤務外');
    }

    // 出勤中の場合、勤怠ステータスが正しく表示される
    public function test_status_is_working()
    {
        $user = User::where('email', 'general1@example.com')->first();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(1),
            'clock_out_time' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $response->assertSeeText('出勤中');
    }

    // 休憩中の場合、勤怠ステータスが正しく表示される
    public function test_status_is_on_rest()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(2),
            'clock_out_time' => null,
        ]);

        Rest::create([
            'attendance_id' => $attendance->id,
            'rest_start_time' => Carbon::now()->subMinutes(30),
            'rest_end_time' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $response->assertSeeText('休憩中');
    }

    // 退勤済の場合、勤怠ステータスが正しく表示される
    public function test_status_is_punched_out()
    {
        $user = User::where('email', 'general1@example.com')->first();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(8),
            'clock_out_time' => Carbon::now()->subHours(1),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $response->assertSeeText('退勤済');
    }
}
