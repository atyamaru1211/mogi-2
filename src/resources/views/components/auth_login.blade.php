@extends('layouts.app')<!--★-->

@section('title','ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/auth_form.css')  }}">
@endsection

@section('content')

<form class="auth-form__form" action="/login" method="post">
    @csrf
    <h1 class="title">ログイン</h1>
    <label class="auth-form__label" for="mail">メールアドレス</label>
    <input class="auth-form__input" type="email" name="email" id="email" value="{{ old('email') }}">
    <p class="error-message">
        <!--★-->
    </p>
    <label class="auth-form__label" for="password">パスワード</label>
    <input class="auth-form__input" type="password" name="password" id="password">
    <p class="error-message">
        <!--★-->
    </p>
    <button class="btn btn--big">ログインする</button>
    <a class="link" href="/register">会員登録はこちらから</a>
</form>
@endsection
