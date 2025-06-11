@extends('layouts.app')<!--★-->

@section('title','会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/auth_form.css')  }}">
@endsection

@section('content')

<div class="page-auth-register">
    <form class="auth-form__form" action="/register" method="post">
        @csrf
        <h1 class="title">会員登録</h1>
        <label class="auth-form__label" for="name">名前</label>
        <input class="auth-form__input" type="text" name="name" id="name" value="{{ old('name') }}">
        <p class="error-message">
            @error('name')
            {{ $message }}
            @enderror
        </p>
        <label class="auth-form__label" for="mail">メールアドレス</label>
        <input class="auth-form__input" type="email" name="email" id="email" value="{{ old('email') }}">
        <p class="error-message">
            @error('email')
            {{ $message }}
            @enderror
        </p>
        <label class="auth-form__label" for="password">パスワード</label>
        <input class="auth-form__input" type="password" name="password" id="password">
        <p class="error-message">
            @error('password')
            {{ $message }}
            @enderror
        </p>
        <label class="auth-form__label" for="password_confirmation">パスワード確認</label>
        <input class="auth-form__input" type="password" name="password_confirmation" id="password_confirmation">
        <p class="error-message">
            @error('password_confirmation')
            {{ $message }}
            @enderror
        </p>
        <button class="btn btn--big">登録する</button>
        <a class="link" href="/login">ログインはこちらから</a>
    </form>
</div>
@endsection
