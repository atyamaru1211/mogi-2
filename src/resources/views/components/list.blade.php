@extends('layouts.app')<!--★-->

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/list.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">
        @if (($is_admin_view ?? false) && ($userForDisplay ?? null))
            {{ $userForDisplay->name }}さんの勤怠
        @else
            勤怠一覧
        @endif
    </h1>
    <!--★月のページネーション-->
    <div class="navigation">
        <!--★前月へのリンク-->
        <a class="nav-arrow" href="{{ ($is_admin_view ?? false) && ($targetUserId ?? null)
            ? '/admin/attendance/staff/' . $targetUserId . '?month=' . $prevMonth->format('Y-m')
            : '/attendance/list?month=' . $prevMonth->format('Y-m') }}">
            <img class="arrow-icon--prev" src="{{ asset('img/arrow.png') }}" alt="左矢印">前月
        </a>
        <!--★現在の月を表示-->
        <span class="current">
            <img class="calendar-icon" src="{{ asset('img/nav.png') }}" alt="カレンダー">
            {{ $currentMonth->format('Y/m') }}
        </span>
        <!--★翌月へのリンク-->
        <a class="nav-arrow" href="{{ ($is_admin_view ?? false) && ($targetUserId ?? null)
            ? '/admin/attendance/staff/' . $targetUserId . '?month=' . $nextMonth->format('Y-m')
            : '/attendance/list?month=' . $nextMonth->format('Y-m') }}">翌月
            <img class="arrow-icon--next" src="{{ asset('img/arrow.png') }}" alt="右矢印">
        </a>
    </div>
    <!--★勤怠データテーブル-->
    <table class="app-table">
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
            @foreach ($daysInMonth as $date)
                @php
                    $attendance = $attendancesMap->get($date->format('Y-m-d'));
                @endphp
                <tr>
                    <td>{{ $date->format('m/d') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek] }})</td>
                    <td>{{ ($attendance && $attendance->clock_in_time) ? $attendance->clock_in_time->format('H:i') : '' }}</td>
                    <td>{{ ($attendance && $attendance->clock_out_time) ? $attendance->clock_out_time->format('H:i') : '' }}</td>
                    <td>{{ ($attendance && $attendance->formatted_rest_time) ? $attendance->formatted_rest_time : '' }}</td>
                    <td>{{ ($attendance && $attendance->actual_work_time) ? $attendance->actual_work_time : '' }}</td>
                    <td>
                        @if ($attendance)
                            <a href="/attendance/{{ $attendance->id }}" class="detail-link">詳細</a>
                        @else
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection