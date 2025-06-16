@extends('layouts.app')<!--★-->

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/staff.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')

<div class="content">
    <h1 class="page-title">スタッフ一覧</h1>

    <table class="app-table staff-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            <!--★ダミーデータ-->
            <tr>
                <td>西　玲奈</td>
                <td>reina@email.com</td>
                <td><a class="detail-link" href="#">詳細</a></td>
            </tr>
            <tr>
                <td>山田 太郎</td>
                <td>taro.y@coachtech.com</td>
                <td><a href="#" class="detail-link">詳細</a></td>
            </tr>
        </tbody>
    </table>
</div>

@endsection