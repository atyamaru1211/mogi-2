@extends('layouts.app')<!--★-->

@section('title', isset($is_admin) && $is_admin ? '管理者ログイン' : 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/auth/auth_form.css')  }}">
@endsection

@section('content')

<form class="auth-form__form" action="{{ isset($is_admin) && $is_admin ? '/admin/login' : '/login' }}" method="post">
    @csrf
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif
    <h1 class="title">{{ isset($is_admin) && $is_admin ? '管理者ログイン' : 'ログイン' }}</h1>
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
    <button class="btn btn--big">{{ isset($is_admin) && $is_admin ? '管理者ログインする' : 'ログインする' }}</button>
    @if (! (isset($is_admin) && $is_admin))
        <a class="link" href="/register">会員登録はこちらから</a>
    @endif
</form>
@endsection
