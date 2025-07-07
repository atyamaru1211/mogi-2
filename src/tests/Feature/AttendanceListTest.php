<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $testDateMay;
    protected $testDateJune1;
    protected $testDateJune2;
    protected $testDateJuly;
    protected $weekdays;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->user = User::where('email', 'general1@example.com')->first();
        $this->testDateMay = Carbon::create(2025, 5, 10);
        $this->testDateJune1 = Carbon::create(2025, 6, 15);
        $this->testDateJune2 = Carbon::create(2025, 6, 16);
        $this->testDateJuly = Carbon::create(2025, 7, 1);
        $this->weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        //テスト日作成　5月分
        Carbon::setTestNow($this->testDateMay->copy()->setTime(8, 0, 0));
        $attendanceMay = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDateMay->toDateString(),
            'clock_in_time' => $this->testDateMay->copy()->setTime(8, 0, 0),
            'clock_out_time' => $this->testDateMay->copy()->setTime(16, 0, 0),
            'total_rest_time' => '00:45:00',
        ]);
        Rest::create([
            'attendance_id' => $attendanceMay->id,
            'rest_start_time' => $this->testDateMay->copy()->setTime(12, 0, 0),
            'rest_end_time' => $this->testDateMay->copy()->setTime(12, 45, 0),
        ]);

        //テスト日作成　6月分　1日目
        Carbon::setTestNow($this->testDateJune1->copy()->setTime(9, 0, 0));
        $attendanceJune1 = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDateJune1->toDateString(),
            'clock_in_time' => $this->testDateJune1->copy()->setTime(9, 0, 0),
            'clock_out_time' => $this->testDateJune1->copy()->setTime(17, 0, 0),
            'total_rest_time' => '01:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendanceJune1->id,
            'rest_start_time' => $this->testDateJune1->copy()->setTime(12, 0, 0),
            'rest_end_time' => $this->testDateJune1->copy()->setTime(13, 0, 0),
        ]);

        //テスト日作成　6月分　2日目
        Carbon::setTestNow($this->testDateJune2->copy()->setTime(9, 30, 0));
        $attendanceJune2 = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDateJune2->toDateString(),
            'clock_in_time' => $this->testDateJune2->copy()->setTime(9, 30, 0),
            'clock_out_time' => $this->testDateJune2->copy()->setTime(18, 0, 0),
            'total_rest_time' => '01:30:00',
        ]);
        Rest::create([
            'attendance_id' => $attendanceJune2->id,
            'rest_start_time' => $this->testDateJune2->copy()->setTime(12, 0, 0),
            'rest_end_time' => $this->testDateJune2->copy()->setTime(13, 30, 0),
        ]);

        //テスト日作成　7月分
        Carbon::setTestNow($this->testDateJuly->copy()->setTime(9, 0, 0));
        $attendanceJuly = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $this->testDateJuly->toDateString(),
            'clock_in_time' => $this->testDateJuly->copy()->setTime(9, 0, 0),
            'clock_out_time' => $this->testDateJuly->copy()->setTime(17, 0, 0),
            'total_rest_time' => '01:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendanceJuly->id,
            'rest_start_time' => $this->testDateJuly->copy()->setTime(12, 0, 0),
            'rest_end_time' => $this->testDateJuly->copy()->setTime(13, 0, 0),
        ]);
        Carbon::setTestNow();
    }

    // 自分が行った勤怠情報が全て表示されている
    public function test_displays_all_own_attendance()
    {
        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDateJune1->format('Y-m'));
        $response->assertStatus(200)->assertViewIs('components.list');

        $japaneseDayJune1 = $this->weekdays[$this->testDateJune1->dayOfWeek];
        $response->assertSeeText($this->testDateJune1->format('m/d') . '(' . $japaneseDayJune1 . ')');
        $response->assertSeeText($this->testDateJune1->copy()->setTime(9, 0, 0)->format('H:i'));
        $response->assertSeeText($this->testDateJune1->copy()->setTime(17, 0, 0)->format('H:i'));
        $response->assertSeeText('1:00');
        $response->assertSeeText('7:00');

        $japaneseDayJune2 = $this->weekdays[$this->testDateJune2->dayOfWeek];
        $response->assertSeeText($this->testDateJune2->format('m/d') . '(' . $japaneseDayJune2 . ')');
        $response->assertSeeText($this->testDateJune2->copy()->setTime(9, 30, 0)->format('H:i'));
        $response->assertSeeText($this->testDateJune2->copy()->setTime(18, 0, 0)->format('H:i'));
        $response->assertSeeText('1:30');
        $response->assertSeeText('7:00');
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示されている
    public function test_displays_current_month_on_load()
    {
        Carbon::setTestNow($this->testDateJune1);
        $response = $this->actingAs($this->user)->get('/attendance/list');
        $response->assertStatus(200)->assertViewIs('components.list');

        $response->assertSeeText($this->testDateJune1->format('Y/m'));

        $japaneseDayJune1 = $this->weekdays[$this->testDateJune1->dayOfWeek];
        $response->assertSeeText($this->testDateJune1->format('m/d') . '(' . $japaneseDayJune1 . ')');
        $response->assertSeeText($this->testDateJune1->copy()->setTime(9, 0, 0)->format('H:i'));
        $response->assertSeeText($this->testDateJune1->copy()->setTime(17, 0, 0)->format('H:i'));

        $japaneseDayJune2 = $this->weekdays[$this->testDateJune2->dayOfWeek];
        $response->assertSeeText($this->testDateJune2->format('m/d') . '(' . $japaneseDayJune2 . ')');
        $response->assertSeeText($this->testDateJune2->copy()->setTime(9, 30, 0)->format('H:i'));
        $response->assertSeeText($this->testDateJune2->copy()->setTime(18, 0, 0)->format('H:i'));
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_displays_previous_month_on_click()
    {
        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDateJune1->format('Y-m'));
        $response->assertStatus(200);
        $response->assertSeeText($this->testDateJune1->format('Y/m'));

        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDateMay->format('Y-m'));
        $response->assertStatus(200);
        $response->assertViewIs('components.list');

        $response->assertSeeText($this->testDateMay->format('Y/m'));

        $japaneseDayMay = $this->weekdays[$this->testDateMay->dayOfWeek];
        $response->assertSeeText($this->testDateMay->format('m/d') . '(' . $japaneseDayMay . ')');
        $response->assertSeeText($this->testDateMay->copy()->setTime(8, 0, 0)->format('H:i'));
        $response->assertSeeText($this->testDateMay->copy()->setTime(16, 0, 0)->format('H:i'));
    }

    // 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_displays_next_month_on_click()
    {
        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDateJune1->format('Y-m'));
        $response->assertStatus(200);
        $response->assertSeeText($this->testDateJune1->format('Y/m'));

        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDateJuly->format('Y-m'));
        $response->assertStatus(200);
        $response->assertViewIs('components.list');

        $response->assertSeeText($this->testDateJuly->format('Y/m'));

        $japaneseDayJuly = $this->weekdays[$this->testDateJuly->dayOfWeek];
        $response->assertSeeText($this->testDateJuly->format('m/d') . '(' . $japaneseDayJuly . ')');
        $response->assertSeeText($this->testDateJuly->copy()->setTime(9, 0, 0)->format('H:i'));
        $response->assertSeeText($this->testDateJuly->copy()->setTime(17, 0, 0)->format('H:i'));
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_can_view_daily_details()
    {
        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $this->testDateJune1->format('Y-m'));
        $response->assertStatus(200)->assertViewIs('components.list');
        $targetAttendance = Attendance::where('user_id', $this->user->id)
                                    ->whereDate('date', $this->testDateJune1->toDateString())
                                    ->first();

        $detailUrl = '/attendance/' . $targetAttendance->id;
        $response = $this->actingAs($this->user)->get($detailUrl);
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');

        $response->assertSeeText($this->testDateJune1->format('Y年'));
        $response->assertSeeText($this->testDateJune1->format('m月d日'));
        $response->assertSeeInOrder([
            '<input class="time-input" type="time" name="clock_in_time" value="' . $this->testDateJune1->copy()->setTime(9, 0, 0)->format('H:i') . '">',
            '<input class="time-input" type="time" name="clock_out_time" value="' . $this->testDateJune1->copy()->setTime(17, 0, 0)->format('H:i') . '">',
        ], false);
        $response->assertSeeInOrder([
            '<input class="time-input" type="time" name="rests[0][start_time]" value="' . $this->testDateJune1->copy()->setTime(12, 0, 0)->format('H:i') . '" id="restStart1">',
            '<input class="time-input" type="time" name="rests[0][end_time]" value="' . $this->testDateJune1->copy()->setTime(13, 0, 0)->format('H:i') . '" id="restEnd1">',
        ], false);
    }
}