<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Carbon $testDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->user = User::where('email', 'general1@example.com')->first();
        $this->testDate = Carbon::create(2025, 7, 7);
        Carbon::setTestNow($this->testDate->copy()->setTime(9, 0, 0));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    /**
     * 認証済ユーザーとして指定された勤怠アクションを実行、
     * リダイレクト後の勤怠画面のレスポンスを返す
    */
    private function performAttendanceActionAndGetPage(string $action): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($this->user)->post('/attendance', [
            'action' => $action,
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');
        return $this->actingAs($this->user)->get('/attendance');
    }

    // 休憩ボタンが正しく機能する
    public function test_rest_start_success()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate->toDateString(),
            'clock_in_time' => $this->testDate->copy()->setTime(8, 0, 0),
            'clock_out_time' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200)->assertViewIs('user.clock');
        $response->assertSeeText('出勤中');
        $response->assertSee('<button class="btn btn--large rest" type="submit">休憩入</button>', false);

        Carbon::setTestNow($this->testDate->copy()->setTime(9, 30, 0));
        $restStartTime = Carbon::now();
        $followRedirectResponse = $this->performAttendanceActionAndGetPage('rest_start');

        $followRedirectResponse->assertSeeText('休憩中');

        $this->assertDatabaseHas('rests', [
            'attendance_id' => Attendance::where('user_id', $this->user->id)
                                        ->whereDate('date', $this->testDate->toDateString())
                                        ->first()->id,
            'rest_start_time' => $restStartTime->toDateTimeString(),
            'rest_end_time' => null,
        ]);
    }

    // 休憩は一日に何回でもできる
    // 休憩戻は一日に何回でもできる
    public function test_multiple_rests_possible()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate->toDateString(),
            'clock_in_time' => $this->testDate->copy()->setTime(9, 0, 0),
            'clock_out_time' => null,
        ]);

        //1回目の休憩開始
        Carbon::setTestNow($this->testDate->copy()->setTime(9, 30, 0));
        $response = $this->performAttendanceActionAndGetPage('rest_start');
        $response->assertSeeText('休憩中')->assertSee('<button class="btn btn--large rest" type="submit">休憩戻</button>', false);
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start_time' => '2025-07-07 09:30:00',
            'rest_end_time' => null,
        ]);


        //1回目の休憩終了
        Carbon::setTestNow($this->testDate->copy()->setTime(10, 0, 0));
        $response = $this->performAttendanceActionAndGetPage('rest_end');
        $response->assertSeeText('出勤中')->assertSee('<button class="btn btn--large rest" type="submit">休憩入</button>', false);
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start_time' => '2025-07-07 09:30:00',
            'rest_end_time' => '2025-07-07 10:00:00',
        ]);

        //2回目の休憩開始
        Carbon::setTestNow($this->testDate->copy()->setTime(10, 30, 0));
        $response = $this->performAttendanceActionAndGetPage('rest_start');
        $response->assertSeeText('休憩中')->assertSee('<button class="btn btn--large rest" type="submit">休憩戻</button>', false);
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start_time' => '2025-07-07 10:30:00',
            'rest_end_time' => null,
        ]);

        $response->assertSee('<button class="btn btn--large rest" type="submit">休憩戻</button>', false);
    }

    // 休憩戻ボタンが正しく機能する
    public function test_rest_end_success()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate->toDateString(),
            'clock_in_time' => $this->testDate->copy()->setTime(9, 0, 0),
            'clock_out_time' => null,
        ]);

        Carbon::setTestNow($this->testDate->copy()->setTime(9, 30, 0));
        $response = $this->performAttendanceActionAndGetPage('rest_start');
        $response->assertSeeText('休憩中')->assertSee('<button class="btn btn--large rest" type="submit">休憩戻</button>', false);
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start_time' => '2025-07-07 09:30:00',
            'rest_end_time' => null,
        ]);

        Carbon::setTestNow($this->testDate->copy()->setTime(10, 0, 0));
        $response = $this->performAttendanceActionAndGetPage('rest_end');
        $response->assertSeeText('出勤中')->assertSee('<button class="btn btn--large rest" type="submit">休憩入</button>', false);
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start_time' => '2025-07-07 09:30:00',
            'rest_end_time' => '2025-07-07 10:00:00',
        ]);
    }

    // 休憩時刻が勤怠一覧画面で確認できる
    public function test_rest_time_on_list()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDate->toDateString(),
            'clock_in_time' => $this->testDate->copy()->setTime(9, 0, 0),
            'clock_out_time' => null,
            'total_rest_time' => null,
        ]);

        Carbon::setTestNow($this->testDate->copy()->setTime(12, 0, 0));
        $this->actingAs($this->user)->post('/attendance', [
            'action' => 'rest_start',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        Carbon::setTestNow($this->testDate->copy()->setTime(13, 0, 0));
        $this->actingAs($this->user)->post('/attendance', [
            'action' => 'rest_end',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        Carbon::setTestNow($this->testDate->copy()->setTime(18, 0, 0));
        $this->actingAs($this->user)->post('/attendance', [
            'action' => 'punch_out',
            '_token' => csrf_token(),
        ])->assertRedirect('/attendance');

        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDate->format('Y-m'));
        $response->assertStatus(200)->assertViewIs('components.list');

        $expectedRestDuration = '1:00';
        $response->assertSeeText($expectedRestDuration);
    }
}
