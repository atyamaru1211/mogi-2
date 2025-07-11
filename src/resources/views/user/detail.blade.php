@extends('layouts.app')

@section('title','勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/detail.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">勤怠詳細</h1>
    
    @if ($hasPendingCorrectionRequest)
        <div class="detail-card">
            <dl class="detail-list">
                <div class="detail-item">
                    <dt class="item-label">名前</dt>
                    <dd class="item-value name-value">{{ $attendance->user->name }}</dd>
                </div>
                <div class="detail-item">
                    <dt class="item-label">日付</dt>
                    <dd class="item-value date-value">
                        <span class="date-year">{{ $pendingRequest->requested_date->format('Y年') }}</span>
                        <span class="date-month-day">{{ $pendingRequest->requested_date->format('m月d日') }}</span>
                    </dd>
                </div>
                <div class="detail-item">
                    <dt class="item-label">出勤・退勤</dt>
                    <dd class="item-value time-display-group">
                        <span class="time-value">{{ $pendingRequest->requested_clock_in_time ? $pendingRequest->requested_clock_in_time->format('H:i') : '' }}</span>
                        <span class="time-separator">～</span>
                        <span class="time-value">{{ $pendingRequest->requested_clock_out_time ? $pendingRequest->requested_clock_out_time->format('H:i') : '' }}</span>
                    </dd>
                </div>
                @forelse ($pendingRequest->requestedRests as $index => $rest)
                <div class="detail-item">
                    <dt class="item-label">休憩{{ $index > 0 ? $index + 1 : '' }}</dt>
                    <dd class="item-value time-display-group">
                        <span class="time-value">{{ $rest->requested_rest_start_time ? $rest->requested_rest_start_time->format('H:i') : '' }}</span>
                        <span class="time-separator">～</span>
                        <span class="time-value">{{ $rest->requested_rest_end_time ? $rest->requested_rest_end_time->format('H:i') : '' }}</span>
                    </dd>
                </div>
                @empty
                @endforelse
                <div class="detail-item">
                    <dt class="item-label">備考</dt>
                    <dd class="item-value">
                        <span class="note-display-text">{{ $pendingRequest->requested_note ?? ''}}</span>
                    </dd>
                </div>
            </dl>
        </div>
        <div class="submit-button">
            <p class="pending-message">*承認待ちのため修正はできません</p>
        </div>

    @else
        <form action="/stamp_correction_request" method="post">
        @csrf
            <input type="hidden" name="id" value="{{ $attendance->id }}">

            <div class="detail-card">
                <dl class="detail-list">
                    <div class="detail-item">
                        <dt class="item-label">名前</dt>
                        <dd class="item-value name-value">{{ $attendance->user->name }}</dd>
                    </div>
                    <div class="detail-item">
                        <dt class="item-label">日付</dt>
                        <dd class="item-value date-value">
                            <span class="date-year">{{ $attendance->date->format('Y年') }}</span>
                            <span class="date-month-day">{{ $attendance->date->format('m月d日') }}</span>
                        </dd>
                    </div>
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
                </dl>
            </div>
            <div class="submit-button">
                <button class="btn btn--medium" type="submit">修正</button>
            </div>
        </form>
    @endif
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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