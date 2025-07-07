<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceDetailForUserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;
    protected $rest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);

        $this->user = User::where('email', 'general1@example.com')->first();

        Carbon::setTestNow(Carbon::create(2025, 6, 15, 9, 0, 0));
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 6, 15)->toDateString(),
            'clock_in_time' => Carbon::create(2025, 6, 15, 9, 0, 0),
            'clock_out_time' => Carbon::create(2025, 6, 15, 17, 0, 0),
            'total_rest_time' => '01:00:00',
        ]);
        $this->rest = Rest::create([
            'attendance_id' => $this->attendance->id,
            'rest_start_time' => Carbon::create(2025, 6, 15, 12, 0, 0),
            'rest_end_time' => Carbon::create(2025, 6, 15, 13, 0, 0),
        ]);
        Carbon::setTestNow();
    }

    // 勤怠詳細画面の「名前」がログインユーザーの指名になっている
    public function test_displays_user_name()
    {
        $response = $this->actingAs($this->user)->get('/attendance/' . $this->attendance->id);
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        $response->assertSeeText($this->user->name);
    }

    // 勤怠詳細画面の「日付」が選択した日付になっている
    public function test_displays_selected_date()
    {
        $response = $this->actingAs($this->user)->get('/attendance/' . $this->attendance->id);
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        $response->assertSeeText($this->attendance->date->format('Y年'));
        $response->assertSeeText($this->attendance->date->format('m月d日'));
    }

    // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_displays_clock_in_out_times()
    {
        $response = $this->actingAs($this->user)->get('/attendance/' . $this->attendance->id);
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        $response->assertSee('value="' . $this->attendance->clock_in_time->format('H:i') . '"', false);
        $response->assertSee('value="' . $this->attendance->clock_out_time->format('H:i') . '"', false);
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_displays_rest_times()
    {
        $response = $this->actingAs($this->user)->get('/attendance/' . $this->attendance->id);
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        $response->assertSee('value="' . $this->rest->rest_start_time->format('H:i') . '"', false);
        $response->assertSee('value="' . $this->rest->rest_end_time->format('H:i') . '"', false);
    }
}
