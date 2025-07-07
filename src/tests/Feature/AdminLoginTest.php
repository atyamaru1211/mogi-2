<?php

namespace Tests\Feature;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
    }    

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_login_admin_validate_email()
    {
        $response = $this->post('/admin/login', [
            'email' => "",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_login_admin_validate_password()
    {
        $response = $this->post('/admin/login', [
            'email' => "admin2@example.com",
            'password' => "",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_login_admin_validate_user()
    {
        $response = $this->post('/admin/login', [
            'email' => "testtest@example.com",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->first('email'));
    }
}
