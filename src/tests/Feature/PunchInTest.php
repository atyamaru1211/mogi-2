<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PunchInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    // 出勤ボタンが正しく機能する
    public function test_punch_in_success()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $response->assertSee('<button class="btn btn--large" type="submit">出勤</button>', false);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'punch_in',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('user.clock');

        $response->assertSeeText('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_out_time' => null,
        ]);
    }

    // 出勤は一日一回のみできる
    public function test_punch_in_once_per_day()
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

        $response->assertDontSee('<input type="hidden" name="action" value="punch_in">', false);
        $response->assertDontSeeText('出勤');
    }

    // 出勤時刻が勤怠一覧画面で確認できる
    public function test_punch_in_time_on_list()
    {
        $user = User::where('email', 'general1@example.com')->first();
        Carbon::setTestNow(Carbon::create(2025, 7, 7, 9, 0, 0));
        $punchInTime = Carbon::now();
        $this->actingAs($user)->post('/attendance', [
            'action' => 'punch_in',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $punchInTime->toDateString(),
            'clock_in_time' => $punchInTime->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $punchInTime->format('Y-m'));
        $response->assertStatus(200);
        $response->assertViewIs('components.list');

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $japaneseDayOfWeek = $weekdays[$punchInTime->dayOfWeek];
        $expectedDateDisplay = $punchInTime->format('m/d') . '(' . $japaneseDayOfWeek . ')';
        $expectedClockInTime = $punchInTime->format('H:i');

        $response->assertSeeText($expectedDateDisplay);
        $response->assertSeeText($expectedClockInTime);

        Carbon::setTestNow();
    }
}
