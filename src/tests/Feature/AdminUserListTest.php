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

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user1;
    protected $user2;

    protected $testDateCurrentMonth;
    protected $testDatePreviousMonth;
    protected $testDateNextMonth;

    protected $attendanceForDetailTest; 

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->admin = Admin::where('email', 'admin1@example.com')->first();
        $this->user1 = User::where('email', 'general1@example.com')->first();
        $this->user2 = User::where('email', 'general2@example.com')->first();

        Carbon::setTestNow(Carbon::create(2025, 7, 15, 9, 0, 0));

        $this->testDateCurrentMonth = Carbon::today();
        $this->testDatePreviousMonth = Carbon::create(2025, 6, 10);
        $this->testDateNextMonth = Carbon::create(2025, 8, 20);

        $attendanceCurrentMonth = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $this->testDateCurrentMonth->toDateString(),
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '17:00:00',
            'total_rest_time' => '01:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendanceCurrentMonth->id,
            'rest_start_time' => '12:00:00',
            'rest_end_time' => '13:00:00',
        ]);

        $attendancePreviousMonth = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $this->testDatePreviousMonth->toDateString(),
            'clock_in_time' => '09:30:00',
            'clock_out_time' => '18:00:00',
            'total_rest_time' => '01:30:00',
        ]);
        Rest::create([
            'attendance_id' => $attendancePreviousMonth->id,
            'rest_start_time' => '12:00:00',
            'rest_end_time' => '13:30:00',
        ]);

        $attendanceNextMonth = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => $this->testDateNextMonth->toDateString(),
            'clock_in_time' => '10:00:00',
            'clock_out_time' => '19:00:00',
            'total_rest_time' => '02:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendanceNextMonth->id,
            'rest_start_time' => '13:00:00',
            'rest_end_time' => '15:00:00',
        ]);

        $this->attendanceForDetailTest = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => Carbon::create(2025, 7, 10)->toDateString(),
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'total_rest_time' => '01:00:00',
            'note' => '詳細テスト用の備考',
        ]);
        Rest::create([
            'attendance_id' => $this->attendanceForDetailTest->id,
            'rest_start_time' => '12:00:00',
            'rest_end_time' => '13:00:00',
        ]);
    }

    // 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_view_all_general_users_name_and_email()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);
        $response->assertViewIs('admin.staff');

        $response->assertSeeText($this->user1->name);
        $response->assertSeeText($this->user1->email);

        $response->assertSeeText($this->user2->name);
        $response->assertSeeText($this->user2->email);
    }

    // ユーザーの勤怠情報が正しく表示される
    public function test_admin_can_view_specific_users_attendance_details()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id);
        $response->assertStatus(200);
        $response->assertViewIs('components.list');

        $response->assertSeeText($this->user1->name . 'さんの勤怠');
        $response->assertSeeText($this->testDateCurrentMonth->format('Y/m'));

        $response->assertSeeText('07/15');
        $response->assertSeeText('火');
        $response->assertSeeText('09:00');
        $response->assertSeeText('17:00');
        $response->assertSeeText('1:00');
        $response->assertSeeText('7:00');
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_can_navigate_to_previous_month_via_button()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id);
        $response->assertStatus(200);
        $response->assertViewIs('components.list');
        $response->assertSeeText('前月');

        $expectedPreviousMonthUrl = '/admin/attendance/staff/' . $this->user1->id . '?month=' . $this->testDatePreviousMonth->format('Y-m');
        $response = $this->get($expectedPreviousMonthUrl);
        $response->assertStatus(200);
        $response->assertViewIs('components.list');

        $response->assertSeeText($this->testDatePreviousMonth->format('Y/m'));

        $response->assertSeeText('06/10');
        $response->assertSeeText('火');
        $response->assertSeeText('09:30');
        $response->assertSeeText('18:00');
        $response->assertSeeText('1:30');
        $response->assertSeeText('7:00');
    }

    //「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_admin_can_navigate_to_next_month_via_button()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id);
        $response->assertStatus(200);
        $response->assertViewIs('components.list');
        $response->assertSeeText('翌月');

        $expectedNextMonthUrl = '/admin/attendance/staff/' . $this->user1->id . '?month=' . $this->testDateNextMonth->format('Y-m');
        $response = $this->get($expectedNextMonthUrl);
        $response->assertStatus(200);
        $response->assertViewIs('components.list');

        $response->assertSeeText($this->testDateNextMonth->format('Y/m'));

        $response->assertSeeText('08/20');
        $response->assertSeeText('水');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
        $response->assertSeeText('2:00');
        $response->assertSeeText('7:00');
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_admin_can_navigate_to_attendance_detail_page()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/admin/attendance/staff/' . $this->user1->id . '?month=' . $this->attendanceForDetailTest->date->format('Y-m'));
        $response->assertStatus(200);
        $response->assertViewIs('components.list');
        $response->assertSee('<a href="/attendance/' . $this->attendanceForDetailTest->id . '" class="detail-link">詳細</a>', false);

        $response = $this->get('/attendance/' . $this->attendanceForDetailTest->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.detail');
        $response->assertSeeText('勤怠詳細');
    }
}
