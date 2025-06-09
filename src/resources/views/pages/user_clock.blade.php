@extends('layouts.app')

@section('title', '勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/pages/user_clock.css')  }}">
@endsection

@section('content')

@include('components.header')
<div class="attendance-container">
    <div class="attendance-card">
        <div class="status-tag">勤務外</div>
        <p class="date">{{ date('Y年n月j日(D)') }}</p>
        <p class="time">08:00</p>
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
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.querySelector('.current-time').textContent = `${hours}:${minutes}`; // :${seconds} を追加しても良い
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>

@endsection