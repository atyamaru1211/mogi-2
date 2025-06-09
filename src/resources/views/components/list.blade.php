@extends('layouts.app')<!--★-->

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/list.css')  }}">
@endsection

@section('content')

@include('components.header')
<div class="content">
    <h1 class="page-title">勤怠一覧</h1>
    <!--★月のページネーション-->
    <div class="navigation">
        <a class="nav-arrow" href="">前月</a>
        <span class="current-month"><img class="calendar-icon" src="{{ asset('img/nav.png') }}" alt="カレンダー">2023/06</span>
        <a class="nav-arrow" href="">翌月</a>
    </div>
    <!--★勤怠データテーブル-->
    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>06/01(木)</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="/attendance/1" class="detail-link">詳細</a></td>
            </tr>
            <tr>
                <td>06/02(金)</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="/attendance/2" class="detail-link">詳細</a></td>
            </tr>
            <tr>
                <td>06/06(火)</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>