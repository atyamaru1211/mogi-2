@extends('layouts.app')<!--★-->

@section('title','勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/detail.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">勤怠詳細</h1>
    <!--★修正成功メッセージ-->
    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif
    <!--★不要であれば消す-->
    @if (session('error'))
        <div class="alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="detail-card">
        <form action="{{ $is_for_approval ? '/stamp_correction_request/approve/' . $attendanceCorrectionRequest->id : '/admin/attendance/update/' . $attendance->id }}" method="post">
            @csrf
            <!--★承認処理ではPATCH不要。直接修正では必要-->
            @unless($is_for_approval)
                @method('patch')
            @endunless

            <!--★勤怠IDまたは修正申請IDを隠しフィールドで送信-->
            @if ($is_for_approval)
                <input type="hidden" name="correction_request_id" value="{{ $attendanceCorrectionRequest->id }}">
            @else
                <input type="hidden" name="id" value="{{ $attendance->id }}">
            @endif

            <dl class="detail-list">
                <!--★名前-->
                <div class="detail-item">
                    <dt class="item-label">名前</dt>
                    <dd class="item-value name-value">{{ $user->name ?? '不明なユーザー' }}</dd>
                </div>
                <!--★日付-->
                <div class="detail-item">
                    <dt class="item-label">日付</dt>
                    <dd class="item-value date-value">
                        <span class="date-year">{{ ($is_for_approval ? optional($attendanceCorrectionRequest->requested_date)->format('Y年') : optional($attendance->date)->format('Y年')) ?? '' }}</span>
                        <span class="date-month-day">{{ ($is_for_approval ? optional($attendanceCorrectionRequest->requested_date)->format('m月d日') : optional($attendance->date)->format('m月d日')) ?? '' }}</span>
                    </dd>
                </div>

                <!--★承認画面の場合-->
                @if ($is_for_approval)
                    <!--★申請内容のみ表示-->
                    <!--★出勤・退勤-->
                    <div class="detail-item">
                        <dt class="item-label">出勤・退勤</dt>
                        <dd class="item-value time-display-group">
                            <span class="time-value">{{ $attendanceCorrectionRequest->requested_clock_in_time ? $attendanceCorrectionRequest->requested_clock_in_time->format('H:i') : '' }}</span>
                            <span class="time-separator">～</span>
                            <span class="time-value">{{ $attendanceCorrectionRequest->requested_clock_out_time ? $attendanceCorrectionRequest->requested_clock_out_time->format('H:i') : '' }}</span>
                        </dd>
                    </div>
                    <!--★休憩-->
                    @foreach ($requestedRestsForApproval as $index => $rest)
                    <div class="detail-item">
                        <dt class="item-label">休憩{{ $index + 1 }}</dt>
                        <dd class="item-value time-display-group">
                            <span class="time-value">{{ $rest['start'] ?? '' }}</span>
                            <span class="time-separator">～</span>
                            <span class="time-value">{{ $rest['end'] ?? '' }}</span>
                        </dd>
                    </div>
                    @endforeach

                    <!--★備考-->
                    <div class="detail-item">
                        <dt class="item-label">備考</dt>
                        <dd class="item-value">
                            {{ $attendanceCorrectionRequest->requested_note ?? ''}}
                        </dd>
                    </div>
                @else
                    <!--★直接修正の場合-->
                    <!--★出勤・退勤-->
                    <div class="detail-item">
                        <dt class="item-label">出勤・退勤</dt>
                        <div class="item-content">
                            <dd class="item-value time-input-group">
                                <input class="time-input" type="time" name="clock_in_time" value="{{ old('clock_in_time', $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '') }}">
                                <span class="time-separator">～</span>
                                <input class="time-input" type="time" name="clock_out_time" value="{{ old('clock_out_time', $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '') }}">
                            </dd>
                            <p class="error-message">
                                @error('clock_in_time')
                                {{ $message }}
                                @enderror
                            </p>
                            <p class="error-message">
                                @error('clock_out_time')
                                {{ $message }}
                                @enderror
                            </p>
                        </div>
                    </div>
                    <!--★休憩-->
                    @foreach ($rests as $index => $rest)
                    <div class="detail-item" data-rest-index="{{ $index }}">
                        <dt class="item-label">休憩{{ $index > 0 ? $index + 1 : '' }}</dt>
                        <div class="item-content">
                            <dd class="item-value time-input-group">
                                <input class="time-input" type="time" name="rests[{{ $index }}][start_time]" value="{{ old('rests.'.$index.'.start_time', $rest['start']) }}" id="restStart{{ $index + 1 }}">
                                <span class="time-separator">～</span>
                                <input class="time-input" type="time" name="rests[{{ $index }}][end_time]" value="{{ old('rests.'.$index.'.end_time', $rest['end']) }}" id="restEnd{{ $index + 1 }}">
                            </dd>
                            <p class="error-message">
                                @error("rests.{$index}.start_time")
                                {{ $message }}
                                @enderror
                            </p>
                            <p class="error-message">
                                @error("rests.{$index}.end_time")
                                {{ $message }}
                                @enderror
                            </p>
                        </div>
                    </div>
                    @endforeach
                    <!--★備考-->
                    <div class="detail-item">
                        <dt class="item-label">備考</dt>
                        <div class="item-content">
                            <dd class="item-value">
                                <textarea class="note" name="note">{{ old('note', $attendance->note ?? '') }}</textarea>
                            </dd>
                            <p class="error-message">
                                @error('note')
                                {{ $message }}
                                @enderror
                            </p>
                        </div>
                    </div>
                @endif
            </dl>

            <div class="submit-button">
                @if ($is_for_approval)
                    @if ($attendanceCorrectionRequest->status === 'pending')
                        <button class="btn btn--medium" type="submit">承認</button>
                    @else
                        <button class="btn btn--medium" type="button" disabled>承認済み</button>
                    @endif
                @else
                    <button class="btn btn--medium" type="submit">修正</button>
                @endif
            </div>
        </form>
    </div>
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