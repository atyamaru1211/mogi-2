@extends('layouts.app')

@section('title', '勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/pages/user_clock.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="attendance-container">
    <div class="attendance-card">
        <div class="status-tag">勤務外</div>
        @php
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek = date('w');
            $japaneseDay = $weekdays[$dayOfWeek];
        @endphp
        <p class="date">{{ date('Y年n月j日') }}({{ $japaneseDay }})</p>
        <p class="time">{{ date('H:i') }}</p>
        <div class="buttons">
            <button class="btn btn--large">出勤</button>
        </div>
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.querySelector('.time').textContent = `${hours}:${minutes}`;
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>

@endsection