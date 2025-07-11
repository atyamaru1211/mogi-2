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

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $commonAttendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->admin = Admin::where('email', 'admin1@example.com')->first();
        $this->user = User::where('email', 'general1@example.com')->first();

        $targetDate = Carbon::create(2025, 7, 10)->toDateString();
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $targetDate . ' 09:00:00'));

        $this->commonAttendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $targetDate,
            'clock_in_time' => Carbon::parse($targetDate . ' 09:00:00'),
            'clock_out_time' => Carbon::parse($targetDate . ' 18:00:00'),
            'total_rest_time' => '01:00:00',
            'note' => '',
        ]);
        Rest::create([
            'attendance_id' => $this->commonAttendance->id,
            'rest_start_time' => Carbon::parse($targetDate . ' 12:00:00'),
            'rest_end_time' => Carbon::parse($targetDate . ' 13:00:00'),
        ]);
        Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_can_view_selected_attendance_detail()
    {
        Carbon::setTestNow(Carbon::parse($this->commonAttendance->date));

        $this->actingAs($this->admin, 'admin');

        $response = $this->get('/attendance/' . $this->commonAttendance->id);
        $response->assertStatus(200);
        $response->assertViewIs('admin.detail');

        $response->assertSeeText($this->user->name);
        $response->assertSeeText(Carbon::parse($this->commonAttendance->date)->format('Y年'));
        $response->assertSeeText(Carbon::parse($this->commonAttendance->date)->format('m月d日'));
        $response->assertSee('<input class="time-input" type="time" name="clock_in_time" value="' . $this->commonAttendance->clock_in_time->format('H:i') . '">', false);
        $response->assertSee('<input class="time-input" type="time" name="clock_out_time" value="' . $this->commonAttendance->clock_out_time->format('H:i') . '">', false);

        $rests = $this->commonAttendance->rests()->orderBy('rest_start_time')->get();
        foreach ($rests as $index => $rest) {
            $response->assertSee('<input class="time-input" type="time" name="rests[' . $index . '][start_time]" value="' . ($rest->rest_start_time ? $rest->rest_start_time->format('H:i') : '') . '"', false);
            $response->assertSee('<input class="time-input" type="time" name="rests[' . $index . '][end_time]" value="' . ($rest->rest_end_time ? $rest->rest_end_time->format('H:i') : '') . '"', false);
        }
        $response->assertSeeText($this->commonAttendance->note);
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_ci_after_co_fails_validation_for_admin_correction()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->get("/attendance/{$this->commonAttendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('admin.detail');

        $response = $this->post('/stamp_correction_request', [
            'id' => $this->commonAttendance->id,
            'clock_in_time' => '17:00',
            'clock_out_time' => '09:00',
            'rests' => [],
            'note' => '出勤時間と退勤時間の逆転テスト（管理者）',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors(['clock_out_time']);
        $this->followRedirects($response)->assertSeeText('出勤時間もしくは退勤時間が不適切な値です');
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_rest_start_after_co_fails_validation_for_admin_correction()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->get("/attendance/{$this->commonAttendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('admin.detail');

        $response = $this->post('/stamp_correction_request', [
            'id' => $this->commonAttendance->id,
            'clock_in_time' => $this->commonAttendance->clock_in_time->format('H:i'),
            'clock_out_time' => $this->commonAttendance->clock_out_time->format('H:i'),
            'rests' => [
                [
                    'start_time' => '19:00',
                    'end_time' => '20:00',
                ],
            ],
            'note' => '休憩開始時間が退勤時間より後のテスト（管理者）',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('rests.0.start_time');
        $this->followRedirects($response)->assertSeeText('休憩時間が勤務時間外です');
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_rest_end_after_co_fails_validation_for_admin_correction()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->get("/attendance/{$this->commonAttendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('admin.detail');

        $response = $this->post('/stamp_correction_request', [
            'id' => $this->commonAttendance->id,
            'clock_in_time' => $this->commonAttendance->clock_in_time->format('H:i'),
            'clock_out_time' => $this->commonAttendance->clock_out_time->format('H:i'),
            'rests' => [
                [
                    'start_time' => '17:00',
                    'end_time' => '19:00',
                ],
            ],
            'note' => '休憩終了時間が退勤時間より後のテスト（管理者）',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('rests.0.end_time');
        $this->followRedirects($response)->assertSeeText('休憩時間が勤務時間外です');
    }

    // 備考欄が未入力の場合、エラーメッセージが表示される
    public function test_note_is_required_fails_validation_for_admin_correction()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->get("/attendance/{$this->commonAttendance->id}");
        $response->assertStatus(200);
        $response->assertViewIs('admin.detail');

        $response = $this->post('/stamp_correction_request', [
            'id' => $this->commonAttendance->id,
            'clock_in_time' => $this->commonAttendance->clock_in_time->format('H:i'),
            'clock_out_time' => $this->commonAttendance->clock_out_time->format('H:i'),
            'rests' => [
                [
                    'start_time' => $this->commonAttendance->rests->first()->rest_start_time->format('H:i'),
                    'end_time' => $this->commonAttendance->rests->first()->rest_end_time->format('H:i'),
                ],
            ],
            'note' => '',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('note');
        $this->followRedirects($response)->assertSeeText('備考を記入してください');
    }
}
