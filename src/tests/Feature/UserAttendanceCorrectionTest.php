<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestRest;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserAttendanceCorrectionTest extends TestCase
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
    }

    //Carbonのテスト時間をリセット
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_ci_after_co_fails_validation()
    {
        $this->actingAs($this->user);

        $response = $this->get("/attendance/{$this->attendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');

        $response = $this->post('/stamp_correction_request', [
            'id' => $this->attendance->id,
            'clock_in_time' => '17:00',
            'clock_out_time' => '09:00',
            'note' => '出勤時間と退勤時間の逆転テスト',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('clock_out_time');

        $this->followRedirects($response)->assertSeeText('出勤時間もしくは退勤時間が不適切な値です');
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_rest_start_after_co_fails_validation()
    {
        $this->actingAs($this->user);

        $response = $this->get("/attendance/{$this->attendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        
        $response = $this->post('/stamp_correction_request', [
            'id' => $this->attendance->id,
            'clock_in_time' => $this->attendance->clock_in_time->format('H:i'),
            'clock_out_time' => $this->attendance->clock_out_time->format('H:i'),
            'rests' => [
                [
                    'start_time' => $this->attendance->clock_out_time->addHour()->format('H:i'),
                    'end_time' => $this->attendance->clock_out_time->addHours(2)->format('H:i'),
                ],
            ],
            'note' => '休憩開始時間が退勤時間より後のテスト',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('rests.0.start_time');

        $this->followRedirects($response)->assertSeeText('休憩時間が勤務時間外です');
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_rest_end_after_co_fails_validation()
    {
        $this->actingAs($this->user);

        $response = $this->get("/attendance/{$this->attendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        
        $response = $this->post('/stamp_correction_request', [
            'id' => $this->attendance->id,
            'clock_in_time' => $this->attendance->clock_in_time->format('H:i'),
            'clock_out_time' => $this->attendance->clock_out_time->format('H:i'),
            'rests' => [
                [
                    'start_time' => Carbon::create(2025, 6, 20, 16, 0, 0)->format('H:i'),
                    'end_time' => $this->attendance->clock_out_time->addHours(1)->format('H:i'),
                ],
            ],
            'note' => '休憩終了時間が退勤時間より後のテスト',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('rests.0.end_time');

        $this->followRedirects($response)->assertSeeText('休憩時間が勤務時間外です');
    }

    // 備考欄が未入力の場合、エラーメッセージが表示される
    public function test_note_is_required_fails_validation()
    {
        $this->actingAs($this->user);

        $response = $this->get("/attendance/{$this->attendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('user.detail');
        
        $response = $this->post('/stamp_correction_request', [
            'id' => $this->attendance->id,
            'clock_in_time' => $this->attendance->clock_in_time->format('H:i'),
            'clock_out_time' => $this->attendance->clock_out_time->format('H:i'),
            'rests' => [
                [
                    'start_time' => $this->rest->rest_start_time->format('H:i'),
                    'end_time' => $this->rest->rest_end_time->format('H:i'),
                ],
            ],
            'note' => '',
            '_token' => csrf_token(),
        ]);
        
        $response -> assertSessionHasErrors('note');

        $this->followRedirects($response)->assertSeeText('備考を記入してください');
    }

}