<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestRest;
use Database\Seeders\UserSeeder;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Illuminate\Testing\TestResponse;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $attendance;
    protected $rest;
    protected $correctionRequest;

    // 各テストで共通して利用する修正内容
    protected $commonClockInTime = '08:30';
    protected $commonClockOutTime = '17:30';
    protected $commonNote = '共通の修正申請理由です';
    protected $commonRestStartTime = '12:00';
    protected $commonRestEndTime = '13:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->user = User::where('email', 'general1@example.com')->first();
        $this->admin = Admin::where('email', 'admin1@example.com')->first();

        Carbon::setTestNow(Carbon::create(2025, 6, 20, 9, 0, 0));
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 6, 20)->toDateString(),
            'clock_in_time' => Carbon::create(2025, 6, 20, 9, 0, 0),
            'clock_out_time' => Carbon::create(2025, 6, 20, 17, 0, 0),
            'total_rest_time' => '01:00:00',
        ]);
        $this->rest = Rest::create([
            'attendance_id' => $this->attendance->id,
            'rest_start_time' => Carbon::create(2025, 6, 20, 12, 0, 0),
            'rest_end_time' => Carbon::create(2025, 6, 20, 13, 0, 0),
        ]);

        // 【共通処理】ユーザーとしてログインし、勤怠修正申請を送信する
        $this->actingAs($this->user);

        $response = $this->post('/stamp_correction_request', [
            'id' => $this->attendance->id,
            'clock_in_time' => $this->commonClockInTime,
            'clock_out_time' => $this->commonClockOutTime,
            'rests' => [
                [
                    'id' => $this->rest->id,
                    'start_time' => $this->commonRestStartTime,
                    'end_time' => $this->commonRestEndTime,
                ],
            ],
            'note' => $this->commonNote,
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/attendance/' . $this->attendance->id);

        $this->correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $this->attendance->id)
                                                              ->where('user_id', $this->user->id)
                                                              ->where('status', 'pending')
                                                              ->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 修正申請処理が実行される
    public function test_correction_request_is_created_and_visible_to_admin()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/stamp_correction_request/approve/' . $this->correctionRequest->id);
        $response->assertStatus(200);
        $response->assertSeeText($this->user->name);
        $response->assertSeeText($this->commonClockInTime);
        $response->assertSeeText($this->commonClockOutTime);
        $response->assertSeeText($this->commonNote);

        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);
        $response->assertSeeText($this->user->name);
        $response->assertSeeText($this->commonNote);
        $response->assertSeeText('承認待ち');
        $response->assertSeeText($this->correctionRequest->requested_date->format('Y/m/d'));
    }

    // 「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_user_can_view_their_pending_requests()
    {
        $response = $this->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);
        $response->assertViewIs('components.request_list');
        $response->assertSeeText('承認待ち');

        $response->assertSeeText($this->commonNote);
        $response->assertSeeText($this->correctionRequest->requested_date->format('Y/m/d'));
        $response->assertSeeText('承認待ち');
    }

    // 「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_user_can_view_approved_requests_in_approved_tab()
    {
        $this->correctionRequest->update(['status' => 'approved']);
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $this->correctionRequest->id,
            'status' => 'approved',
        ]);

        $response = $this->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertViewIs('components.request_list');
        $response->assertSeeText('承認済み');

        $response->assertSeeText($this->commonNote);
        $response->assertSeeText($this->user->name);
        $response->assertSeeText($this->correctionRequest->requested_date->format('Y/m/d'));
        $response->assertSeeText('承認済み');
    }

    // 各申請の「詳細」を押下すると申請詳細画面に遷移する
    public function test_detail_link_navigates_to_correct_page()
    {
        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);
        $response->assertViewIs('components.request_list');

        $expectedUrl = '/attendance/' . $this->correctionRequest->attendance_id;
        $response->assertSee('<a class="detail-link" href="' . $expectedUrl . '">詳細</a>', false);

        $response = $this->get($expectedUrl);
        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細');
    }
}
