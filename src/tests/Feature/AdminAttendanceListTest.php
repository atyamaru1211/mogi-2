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
    protected $testDateToday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->admin = Admin::where('email', 'admin1@example.com')->first();
        $this->user1 = User::where('email', 'general1@example.com')->first();
        $this->user2 = User::where('email', 'general2@example.com')->first();

        Carbon::setTestNow(Carbon::create(2025, 7, 15, 9, 0, 0));
        $this->testDateToday = Carbon::today();

        Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $this->testDateToday->toDateString(),
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '17:00:00',
            'total_rest_time' => '01:00:00',
        ]);

        Attendance::create([
            'user_id' => $this->user2->id,
            'date' => $this->testDateToday->toDateString(),
            'clock_in_time' => '09:30:00',
            'clock_out_time' => '18:00:00',
            'total_rest_time' => '01:30:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // その日になされた全ユーザーの勤怠情報が正確に確認できる
     public function test_admin_can_view_all_users_attendance_for_today()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response->assertSeeText($this->testDateToday->format('Y年m月d日') . 'の勤怠');
        $response->assertSeeText($this->testDateToday->format('Y/m/d'));

        $response->assertSeeText($this->user1->name);
        $response->assertSeeText('09:00');
        $response->assertSeeText('17:00');

        $response->assertSeeText($this->user2->name);
        $response->assertSeeText('09:30');
        $response->assertSeeText('18:00');
    }

    // 遷移した際に現在の日付が表示される
    public function test_admin_attendance_list_shows_current_date()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response->assertSeeText($this->testDateToday->format('Y年m月d日') . 'の勤怠');
        $response->assertSeeText($this->testDateToday->format('Y/m/d'));
    }

    // 「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_admin_can_view_previous_day_attendance()
    {
        $previousDay = $this->testDateToday->copy()->subDay();

        Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $previousDay->toDateString(),
            'clock_in_time' => '08:30:00',
            'clock_out_time' => '17:30:00',
            'total_rest_time' => '01:00:00',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=' . $previousDay->toDateString());
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response->assertSeeText($previousDay->format('Y年m月d日') . 'の勤怠');
        $response->assertSeeText($previousDay->format('Y/m/d'));

        $response->assertSeeText($this->user1->name);
        $response->assertSeeText('08:30');
        $response->assertSeeText('17:30');
    }

    // 「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_admin_can_view_next_day_attendance()
    {
        $nextDay = $this->testDateToday->copy()->addDay();

        Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $nextDay->toDateString(),
            'clock_in_time' => '09:15:00',
            'clock_out_time' => '17:45:00',
            'total_rest_time' => '01:00:00',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=' . $nextDay->toDateString());
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance');

        $response->assertSeeText($nextDay->format('Y年m月d日') . 'の勤怠');
        $response->assertSeeText($nextDay->format('Y/m/d'));

        $response->assertSeeText($this->user1->name);
        $response->assertSeeText('09:15');
        $response->assertSeeText('17:45');
    }
}
