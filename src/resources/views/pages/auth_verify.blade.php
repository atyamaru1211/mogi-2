@extends('layouts.app')<!--★-->

@section('title', 'メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/auth_form.css')  }}">
@endsection

@section('content')
<div class="verify-notice">
    <div class="verify-notice__inner">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        <p class="verify-notice__heading">登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。</p>
        <div class="verify-notice__button-container">
            <a class="verify-notice__button" href="/mailhog">認証はこちらから</a>
        </div>
        <form class="verify-notice__resend-form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <input type="hidden" name="email" value="{{ session('registered_email') }}"> 
            <button class="verify-notice__resend-link" type="submit">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection
