<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PunchOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    // 退勤ボタンが正しく機能する
    public function test_punch_out_success()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $testDate = Carbon::create(2025, 7, 7);

        $clockInTime = $testDate->copy()->setTime(9, 0, 0);
        Carbon::setTestNow($clockInTime);
        Attendance::create([
            'user_id' => $user->id,
            'date' => $testDate->toDateString(),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200)->assertViewIs('user.clock');
        $response->assertSeeText('出勤中');
        $response->assertSee('<button class="btn btn--large" type="submit">退勤</button>', false);

        $punchOutTime = $testDate->copy()->setTime(17, 0, 0);
        Carbon::setTestNow($punchOutTime);
        $this->actingAs($user)->post('/attendance', [
            'action' => 'punch_out',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        $followRedirectResponse = $this->actingAs($user)->get('/attendance');

        $followRedirectResponse->assertSeeText('退勤済');
        $followRedirectResponse->assertDontSee('<button class="btn btn--large" type="submit">退勤</button>', false);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $testDate->toDateString(),
            'clock_out_time' => $punchOutTime->toDateTimeString(),
        ]);

        Carbon::setTestNow();
    }

    // 退勤時刻が勤怠一覧画面で確認できる
    public function test_punch_out_time_on_list()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $testDate = Carbon::create(2025, 6, 25);

        $clockInTime = $testDate->copy()->setTime(9, 0, 0);
        Carbon::setTestNow($clockInTime);
        Attendance::create([
            'user_id' => $user->id,
            'date' => $testDate->toDateString(),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => null,
            'total_rest_time' => null,
        ]);

        $punchOutTime = $testDate->copy()->setTime(17, 0, 0);
        Carbon::setTestNow($punchOutTime);
        $this->actingAs($user)->post('/attendance', [
            'action' => 'punch_out',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $testDate->format('Y-m'));
        $response->assertStatus(200)->assertViewIs('components.list');

        $expectedPunchOutTime = $punchOutTime->format('H:i');
        $expectedClockInTime = $clockInTime->format('H:i');

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $japaneseDay = $weekdays[$testDate->dayOfWeek];
        $expectedDateText = $testDate->format('m/d') . '(' . $japaneseDay . ')';

        $response->assertSeeText($expectedDateText);
        $response->assertSeeText($expectedClockInTime);
        $response->assertSeeText($expectedPunchOutTime);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $testDate->toDateString(),
            'clock_out_time' => $punchOutTime->toDateTimeString(),
        ]);

        Carbon::setTestNow();
    }
}
