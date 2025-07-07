<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_login_user_validate_email()
    {
        $response = $this->post('/login', [
            'email' => "",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_login_user_validate_password()
    {
        $response = $this->post('/login', [
            'email' => "general2@example.com",
            'password' => "",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_login_user_validate_user()
    {
        $response = $this->post('/login', [
            'email' => "test@example.com",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->first('email'));
    }
}
