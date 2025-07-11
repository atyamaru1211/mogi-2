<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use App\Models\Rest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestRest;
use Database\Seeders\UserSeeder;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user1;
    protected $user2;
    protected $attendance1;
    protected $pendingRequest1;
    protected $pendingRequest2;
    protected $approvedRequest;
    protected $initialRestForAttendance1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->admin = Admin::where('email', 'admin1@example.com')->first();
        $this->user1 = User::where('email', 'general1@example.com')->first();
        $this->user2 = User::where('email', 'general2@example.com')->first();

        Carbon::setTestNow(Carbon::create(2025, 7, 10, 9, 0, 0));

        $this->attendance1 = Attendance::create([
            'user_id' => $this->user1->id, 'date' => '2025-07-10',
            'clock_in_time' => '09:00:00', 'clock_out_time' => '17:00:00', 'total_rest_time' => '01:00:00',
        ]);
        $this->initialRestForAttendance1 = Rest::create([
            'attendance_id' => $this->attendance1->id, 'rest_start_time' => '12:00:00', 'rest_end_time' => '13:00:00',
        ]);

        $this->pendingRequest1 = AttendanceCorrectionRequest::create([
            'user_id' => $this->user1->id, 'attendance_id' => $this->attendance1->id, 'requested_date' => '2025-07-10',
            'requested_clock_in_time' => '09:15:00', 'requested_clock_out_time' => '17:30:00',
            'requested_note' => '出勤時刻と休憩時間の修正依頼です。', 'status' => 'pending', 'request_type' => 'clock_in_out',
        ]);
        AttendanceCorrectionRequestRest::create([
            'attendance_correction_request_id' => $this->pendingRequest1->id, 'requested_rest_start_time' => '12:15:00', 'requested_rest_end_time' => '13:15:00',
        ]);
        AttendanceCorrectionRequestRest::create([
            'attendance_correction_request_id' => $this->pendingRequest1->id, 'requested_rest_start_time' => '15:00:00', 'requested_rest_end_time' => '15:30:00',
        ]);

        $this->pendingRequest2 = AttendanceCorrectionRequest::create([
            'user_id' => $this->user2->id,
            'attendance_id' => Attendance::create([
                'user_id' => $this->user2->id, 'date' => '2025-07-11', 'clock_in_time' => '09:30:00', 'clock_out_time' => '18:00:00', 'total_rest_time' => '01:00:00',
            ])->id,
            'requested_date' => '2025-07-11', 'requested_clock_in_time' => '09:45:00', 'requested_clock_out_time' => '18:15:00',
            'requested_note' => '退勤時刻の修正依頼です。', 'status' => 'pending', 'request_type' => 'clock_in_out',
        ]);

        $this->approvedRequest = AttendanceCorrectionRequest::create([
            'user_id' => $this->user1->id,
            'attendance_id' => Attendance::create([
                'user_id' => $this->user1->id, 'date' => '2025-07-12', 'clock_in_time' => '10:00:00', 'clock_out_time' => '19:00:00', 'total_rest_time' => '01:00:00',
            ])->id,
            'requested_date' => '2025-07-12', 'requested_clock_in_time' => '10:00:00', 'requested_clock_out_time' => '19:00:00',
            'requested_note' => '承認済みの申請です。', 'status' => 'approved', 'request_type' => 'clock_in_out',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 承認待ちの修正申請が全て表示されている
    public function test_admin_can_view_all_pending_correction_requests()
    {
        $this->actingAs($this->admin, 'admin')
             ->get('/stamp_correction_request/list?tab=pending')
             ->assertStatus(200)
             ->assertViewIs('components.request_list')
             ->assertSee('承認待ち')
             ->assertSeeText($this->pendingRequest1->user->name)
             ->assertSeeText($this->pendingRequest2->user->name);
    }

    // 承認済の修正申請が全て表示されている
    public function test_admin_can_view_all_approved_correction_requests()
    {
        $this->actingAs($this->admin, 'admin')
             ->get('/stamp_correction_request/list?tab=approved')
             ->assertStatus(200)
             ->assertViewIs('components.request_list')
             ->assertSee('承認済み')
             ->assertSeeText($this->approvedRequest->user->name);
    }

    // 修正申請の詳細内容が正しく表示されている
    public function test_admin_can_view_correction_request_detail()
    {
        $response = $this->actingAs($this->admin, 'admin')
                         ->get('/stamp_correction_request/approve/' . $this->pendingRequest1->id);

        $response->assertStatus(200)
                 ->assertViewIs('admin.detail')
                 ->assertSee('勤怠詳細')
                 ->assertSeeText($this->pendingRequest1->user->name)
                 ->assertSeeText($this->pendingRequest1->requested_date->format('Y年'))
                 ->assertSeeText($this->pendingRequest1->requested_date->format('m月d日'))
                 ->assertSeeText($this->pendingRequest1->requested_clock_in_time->format('H:i'))
                 ->assertSeeText($this->pendingRequest1->requested_clock_out_time->format('H:i'))
                 ->assertSeeText($this->pendingRequest1->requested_note);
        
        foreach ($this->pendingRequest1->fresh()->requestedRests as $rest) {
            $response->assertSeeText($rest->requested_rest_start_time->format('H:i'))
                     ->assertSeeText($rest->requested_rest_end_time->format('H:i'));
        }
    }

    // 修正申請の承認処理が正しく行われる
    public function test_admin_can_approve_correction_request_and_attendance_is_updated()
    {
        $originalAttendance = $this->attendance1->fresh();
        $requestedRestsCount = $this->pendingRequest1->fresh()->requestedRests->count();

        $this->actingAs($this->admin, 'admin')
             ->post('/stamp_correction_request/approve/' . $this->pendingRequest1->id)
             ->assertRedirect('/stamp_correction_request/approve/' . $this->pendingRequest1->id)
             ->assertSessionHas('success', '修正申請を承認しました');

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $this->pendingRequest1->id, 'status' => 'approved',
        ]);

        $updatedAttendance = $originalAttendance->fresh();
        $this->assertEquals($this->pendingRequest1->requested_clock_in_time->format('H:i:s'), $updatedAttendance->clock_in_time->format('H:i:s'));
        $this->assertEquals($this->pendingRequest1->requested_clock_out_time->format('H:i:s'), $updatedAttendance->clock_out_time->format('H:i:s'));
        $this->assertEquals($this->pendingRequest1->requested_note, $updatedAttendance->note);

        $this->assertDatabaseMissing('rests', [
            'attendance_id' => $this->attendance1->id,
            'rest_start_time' => $this->initialRestForAttendance1->rest_start_time,
            'rest_end_time' => $this->initialRestForAttendance1->rest_end_time,
        ]);
        
        foreach ($this->pendingRequest1->fresh()->requestedRests as $requestedRest) {
            $this->assertDatabaseHas('rests', [
                'attendance_id' => $this->attendance1->id,
                'rest_start_time' => $requestedRest->requested_rest_start_time,
                'rest_end_time' => $requestedRest->requested_rest_end_time,
            ]);
        }
        $this->assertEquals($requestedRestsCount, Rest::where('attendance_id', $this->attendance1->id)->count());
    }
}