@extends('layouts.app')<!--★-->

@section('title', '勤怠一覧')

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">2023年6月1日の勤怠</h1>

    <div class="navigation">
        <a class="nav-arrow" href=""><img class="arrow-icon--prev" src="{{ asset('img/arrow.png') }}" alt="左矢印">前日</a>
        <span class="current"><img class="calendar-icon" src="{{ asset('img/nav.png') }}" alt="カレンダー">2023/06/01</span>
        <a class="nav-arrow" href="">翌日<img class="arrow-icon--next" src="{{ asset('img/arrow.png') }}" alt="右矢印"></a>
    </div>

    <table class="app-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <!--★ダミーデータ-->
            <tr>
                <td>山田　太郎</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a class="detail-link" href="#">詳細</a></td>
            </tr>
            <tr>
                <td>西 怜奈</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="" class="detail-link">詳細</a></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection