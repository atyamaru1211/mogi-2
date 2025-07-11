@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/attendance.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">{{ $currentDate->format('Y年m月d日') }}の勤怠</h1>

    <div class="navigation">
        <a class="nav-arrow" href="/admin/attendance/list?date={{  $prevDate->format('Y-m-d') }}">
            <img class="arrow-icon--prev" src="{{ asset('img/arrow.png') }}" alt="左矢印">前日
        </a>
        <span class="current">
            <img class="calendar-icon" src="{{ asset('img/nav.png') }}" alt="カレンダー">{{ $currentDate->format('Y/m/d') }}
        </span>
        <a class="nav-arrow" href="/admin/attendance/list?date={{ $nextDate->format('Y-m-d') }}">翌日
            <img class="arrow-icon--next" src="{{ asset('img/arrow.png') }}" alt="右矢印">
        </a>
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
            @forelse ($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '' }}</td>
                <td>{{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '' }}</td>
                <td>{{ $attendance->formatted_rest_time }}</td>
                <td>{{ $attendance->formatted_work_time }}</td>
                <td><a class="detail-link" href="/attendance/{{ $attendance->id }}">詳細</a></td>
            </tr>
            @empty
            @endforelse
        </tbody>
    </table>
</div>
@endsection