<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // 名前が未入力の場合、バリデーションメッセージが表示される
    public function test_register_user_validate_name()
    {
        $response = $this->post('/register', [
            'name' => "",
            'email' => "test@example.com",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');

        $errors = session('errors');
        $this->assertEquals('お名前を入力してください', $errors->first('name'));
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_register_user_validate_email()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザー",
            'email' => "",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される
    public function test_register_user_validate_password_under7()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザー",
            'email' => "test@example.com",
            'password' => "passwor",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードは8文字以上で入力してください', $errors->first('password'));
    }

    // パスワードが一致しない場合、バリデーションメッセージが表示される
    public function test_register_user_validate_confirm_password()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザー",
            'email' => "test@example.com",
            'password' => "password",
            'password_confirmation' => "password123",
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors('password_confirmation');

        $errors = session('errors');
        $this->assertEquals('パスワードと一致しません', $errors->first('password_confirmation'));
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_register_user_validate_password()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザー",
            'email' => "test@example.com",
            'password' => "",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    // フォームに内容が入力されていた場合、データが正常に保存される
    public function test_register_user()
    {
        $response = $this->post('/register', [
            'name' => "テストユーザー",
            'email' => "test@example.com",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertDatabaseHas(User::class, [
            'name' => "テストユーザー",
            'email' => "test@example.com",
        ]);
    }
}
