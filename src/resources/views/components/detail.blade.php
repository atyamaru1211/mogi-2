@extends('layouts.app')<!--★-->

@section('title','勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/detail.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">勤怠詳細</h1>
    <form action="/attendance/update" method="post">
        @csrf
        <div class="detail-card">
            <dl class="detail-list">
                <!--★名前-->
                <div class="detail-item">
                    <dt class="item-label">名前</dt>
                    <dd class="item-value name-value">{{ $attendance->user->name }}</dd>
                </div>
                <!--★日付-->
                <div class="detail-item">
                    <dt class="item-label">日付</dt>
                    <dd class="item-value date-value">
                        <span class="date-year">{{ $attendance->date->format('Y年') }}</span>
                        <span class="date-month-day">{{ $attendance->date->format('m月d日') }}</span>
                    </dd>
                </div>
                <!--★出勤・退勤-->
                <div class="detail-item">
                    <dt class="item-label">出勤・退勤</dt>
                    <dd class="item-value time-input-group">
                        <input class="time-input" type="time" name="clock_in_time" value="{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '' }}">
                        <span class="time-separator">～</span>
                        <input class="time-input" type="time" name="clock_out_time" value="{{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '' }}">
                    </dd>
                </div>
                <!--★休憩-->
                @foreach ($rests as $index => $rest)
                <div class="detail-item" data-rest-index="{{ $index }}">
                    <dt class="item-label">休憩{{ $index > 0 ? $index + 1 : '' }}</dt>
                    <dd class="item-value time-input-group">
                        <input class="time-input" type="time" name="rests[{{ $index }}][start_time]" value="{{ $rest['start'] }}" id="restStart{{ $index + 1 }}">
                        <span class="time-separator">～</span>
                        <input class="time-input" type="time" name="rests[{{ $index }}][end_time]" value="{{ $rest['end'] }}" id="restEnd{{ $index + 1 }}">
                    </dd>
                </div>
                @endforeach
                <!--★備考-->
                <div class="detail-item">
                    <dt class="item-label">備考</dt>
                    <dd class="item-value">
                        <textarea class="note" name="note">{{ $attendance->note ?? '' }}</textarea>
                    </dd>
                </div>
            </dl>
        </div>
        <div class="submit-button">
            <button class="btn btn--medium" type="submit">修正</button>
        </div>
    </form>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 休憩入力欄を動的に取得し、ゼロ埋めなし表示のロジックを適用
        document.querySelectorAll('.detail-item[data-rest-index] .time-input').forEach(input => {
            if (input.value === '') {
                input.type = 'text';
                input.placeholder = '';
            }

            input.addEventListener('focus', function() {
                this.type = 'time';
            });

            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.type = 'text';
                    this.placeholder = '';
                }
            });
        });
    });
</script>
@endsection

@endsection