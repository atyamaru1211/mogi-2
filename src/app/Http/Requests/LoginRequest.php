<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

//★デフォルトのログイン機能には上述のFortifyLoginRequestクラスが引数として渡されているため、異なるクラスを呼び出すとエラーが出力される。
//そのため、デフォルトにある上述のFortifyLoginRequestクラスを継承する必要がある
class LoginRequest extends FortifyLoginRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'string', 'max:255', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.string' => 'メールアドレスは文字列で入力してください',
            'email.max' => 'メールアドレスは255文字以内で入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.string' => 'パスワードは文字列で入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.max' => 'パスワードは255文字以内で入力してください',
        ];
    }
}
