<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $unverifiedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);

        $this->unverifiedUser = User::create([
            'name' => '未認証ユーザー',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);
    }

    // 会員登録後、認証メールが送信される
    public function test_user_receives_verification_email_after_registration()
    {
        Mail::fake();
        Notification::fake();

        $userData = [
            'name' => '新規登録ユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->assertNull($user->email_verified_at);

        $response->assertRedirect(route('verification.notice'));

        $response->assertSessionHas('unauthenticated_user');
        $this->assertEquals($user->id, session('unauthenticated_user')->id);
    }

    // メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_can_navigate_to_mailhog_from_verification_notice_screen()
    {
        session(['unauthenticated_user' => $this->unverifiedUser]); 

        $response = $this->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSeeText('登録していただいたメールアドレスに認証メールを送付しました。');

        $navigateToMailhogResponse = $this->get('/mailhog');
        $navigateToMailhogResponse->assertRedirect('http://localhost:8025');
    }

    // メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
    public function test_email_verification_completes_and_redirects_to_attendance_screen()
    {
        Event::fake();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->unverifiedUser->id, 'hash' => sha1($this->unverifiedUser->getEmailForVerification())]
        );

        Session::put('unauthenticated_user', $this->unverifiedUser);

        $response = $this->get($verificationUrl);

        $this->unverifiedUser->refresh();
        $this->assertNotNull($this->unverifiedUser->email_verified_at, 'ユーザーのメールが認証されていません。');

        Event::assertDispatched(Verified::class, function ($event) {
            return $event->user->id === $this->unverifiedUser->id;
        });

        $this->assertFalse(Session::has('unauthenticated_user'), 'セッションにunauthenticated_userが残っています。');
        
        $response->assertRedirect('/attendance');
    }
}
