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
                    <dd class="item-value name-value">西　玲奈</dd>
                </div>
                <!--★日付-->
                <div class="detail-item">
                    <dt class="item-label">日付</dt>
                    <dd class="item-value date-value">
                        <span class="date-year">2023年</span>
                        <span class="date-month-day">6月1日</span>
                    </dd>
                </div>
                <!--★出勤・退勤-->
                <div class="detail-item">
                    <dt class="item-label">出勤・退勤</dt>
                    <dd class="item-value time-input-group">
                        <input class="time-input" type="time" value="09:00">
                        <span class="time-separator">～</span>
                        <input class="time-input" type="time" value="18:00">
                    </dd>
                </div>
                <!--★休憩-->
                <div class="detail-item">
                    <dt class="item-label">休憩</dt>
                    <dd class="item-value time-input-group">
                        <input class="time-input" type="time" value="12:00">
                        <span class="time-separator">～</span>
                        <input class="time-input" type="time" value="13:00">
                    </dd>
                </div>
                <!--★休憩2-->
                <div class="detail-item">
                    <dt class="item-label">休憩2</dt>
                    <dd class="item-value time-input-group">
                        <input class="time-input" type="time" value="" id="restStart2">
                        <span class="time-separator">～</span>
                        <input class="time-input" type="time" value="" id="restEnd2">
                    </dd>
                </div>
                <!--★備考-->
                <div class="detail-item">
                    <dt class="item-label">備考</dt>
                    <dd class="item-value">
                        <textarea class="note"></textarea>
                    </dd>
                </div>
            </dl>
        </div>
        <div class="submit-button">
            <button class="btn btn--medium" type="submit">修正</button>
        </div>
    </form>
</div>

{{-- ★JavaScriptコードを追加するセクション★ --}}
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const restStart2 = document.getElementById('restStart2');
        const restEnd2 = document.getElementById('restEnd2');

        // ページロード時にinput要素のvalueが空の場合、"--:--"が表示されないようにする
        // これには、input要素の表示をtextに切り替えるハックを使います
        if (restStart2.value === '') {
            restStart2.type = 'text';
            restStart2.placeholder = ''; // 必要であればプレースホルダーも設定しない
        }
        if (restEnd2.value === '') {
            restEnd2.type = 'text';
            restEnd2.placeholder = '';
        }

        // フォーカスが当たったときにtypeをtimeに戻す
        restStart2.addEventListener('focus', function() {
            this.type = 'time';
        });
        restEnd2.addEventListener('focus', function() {
            this.type = 'time';
        });

        // フォーカスが外れたときにvalueが空ならtypeをtextに戻す
        restStart2.addEventListener('blur', function() {
            if (this.value === '') {
                this.type = 'text';
                this.placeholder = '';
            }
        });
        restEnd2.addEventListener('blur', function() {
            if (this.value === '') {
                this.type = 'text';
                this.placeholder = '';
            }
        });
    });
</script>
@endsection

@endsection