<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Rest;
use Database\Seeders\UserSeeder;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user1;
    protected $user2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->admin = Admin::where('email', 'admin1@example.com')->first();
        $this->user1 = User::where('email', 'general1@example.com')->first();
        $this->user2 = User::where('email', 'general2@example.com')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // その日になされた全ユーザーの勤怠情報が正確に確認できる
     public function test_admin_can_view_all_users_attendance_for_today()
    {
        $today = Carbon::today()->toDateString();
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d', $today)->setTime(9, 0, 0)); // テスト実行日を今日に固定

        $attendance1 = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $today,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '17:00:00',
            'total_rest_time' => '01:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendance1->id,
            'rest_start_time' => '12:00:00',
            'rest_end_time' => '13:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $this->user2->id,
            'date' => $today,
            'clock_in_time' => '09:30:00',
            'clock_out_time' => '18:00:00',
            'total_rest_time' => '01:30:00',
        ]);
        Rest::create([
            'attendance_id' => $attendance2->id,
            'rest_start_time' => '12:00:00',
            'rest_end_time' => '13:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendance2->id,
            'rest_start_time' => '15:00:00',
            'rest_end_time' => '15:30:00',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');
        $response->assertSeeText(Carbon::parse($today)->format('Y年m月d日') . 'の勤怠');
        $response->assertSeeText(Carbon::parse($today)->format('Y/m/d'));

        $response->assertSeeText($this->user1->name);
        $response->assertSeeText('09:00');
        $response->assertSeeText('17:00');
        $response->assertSeeText('1:00');
        $response->assertSeeText('7:00');

        $response->assertSeeText($this->user2->name);
        $response->assertSeeText('09:30');
        $response->assertSeeText('18:00');
        $response->assertSeeText('1:30');
        $response->assertSeeText('7:00');
    }

    // 遷移した際に現在の日付が表示される
    public function test_admin_attendance_list_shows_current_date()
    {
        $today = Carbon::today()->toDateString();
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d', $today)->setTime(9, 0, 0));

        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response->assertSeeText(Carbon::parse($today)->format('Y年m月d日') . 'の勤怠');
        $response->assertSeeText(Carbon::parse($today)->format('Y/m/d'));
    }

    // 「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_admin_can_view_previous_day_attendance()
    {
        $today = Carbon::create(2025, 7, 9, 9, 0, 0);
        Carbon::setTestNow($today);
        $yesterday = $today->copy()->subDay()->toDateString();

        $attendance1_yesterday = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $yesterday,
            'clock_in_time' => Carbon::parse($yesterday . ' 08:30:00'),
            'clock_out_time' => Carbon::parse($yesterday . ' 17:30:00'),
            'total_rest_time' => '01:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendance1_yesterday->id,
            'rest_start_time' => Carbon::parse($yesterday . ' 12:00:00'),
            'rest_end_time' => Carbon::parse($yesterday . ' 13:00:00'),
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response = $this->get('/admin/attendance/list?date=' . $yesterday);
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response->assertSeeText(Carbon::parse($yesterday)->format('Y年m月d日') . 'の勤怠'); // H1タグ内の日付
        $response->assertSeeText(Carbon::parse($yesterday)->format('Y/m/d'));

        $response->assertSeeText($this->user1->name);
        $response->assertSeeText('08:30');
        $response->assertSeeText('17:30');
        $response->assertSeeText('1:00');
        $response->assertSeeText('8:00');
    }

    // 「翌日」を押下した時に次の日の勤怠情報が表示される
}
