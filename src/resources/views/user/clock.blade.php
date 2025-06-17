@extends('layouts.app')

@section('title', '勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/user/clock.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="attendance-container">
    <div class="attendance-card">
        <div class="status-tag">{{ $status }}</div>
        @php
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek = date('w');
            $japaneseDay = $weekdays[$dayOfWeek];
        @endphp
        <p class="date">{{ date('Y年n月j日') }}({{ $japaneseDay }})</p>
        <p class="time">{{ date('H:i') }}</p>

        <div class="buttons">
            <!--★退勤済の場合はボタンを表示しない-->
            @if ($status == '退勤済')
                <p class="message-after-punchout">お疲れさまでした。</p>
            @else
                <!--★出勤ボタン-->
                @if ($status == '勤務外')
                    <form action="/attendance" method="post">
                        @csrf
                        <input type="hidden" name="action" value="punch_in">
                        <button class="btn btn--large" type="submit">出勤</button>
                    </form>
                @endif
                <!--★退勤ボタン-->
                @if ($status == '出勤中')
                    <form action="/attendance" method="post">
                        @csrf
                        <input type="hidden" name="action" value="punch_out">
                        <button class="btn btn--large" type="submit">退勤</button>
                    </form>
                @endif
                <!--★休憩ボタン-->
                @if ($status == '出勤中')
                    <form action="/attendance" method="post">
                        @csrf
                        <input type="hidden" name="action" value="break_start">
                        <button class="btn btn--large rest" type="submit">休憩入</button>
                    </form>
                @endif
                <!--★休憩戻りボタン-->
                @if ($status == '休憩中')
                    <form action="/attendance" method="post">
                        @csrf
                        <input type="hidden" name="action" value="break_end">
                        <button class="btn btn--large rest" type="submit">休憩戻</button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.querySelector('.time').textContent = `${hours}:${minutes}`;
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>

@endsection