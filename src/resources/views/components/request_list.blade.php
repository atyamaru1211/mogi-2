@extends('layouts.app')<!--★-->

@section('title','申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/components/request_list.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')
<div class="content">
    <h1 class="page-title">申請一覧</h1>
    <div class="tabs">
        <button class="tab-button active" data-tab="pending">承認待ち</button>
        <button class="tab-button" data-tab="approved">承認済み</button>
    </div>
    
    <div class="tab-content active" id="pending">
        <table class="app-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                <!--★承認待ちのダミーデータ-->
                @for ($i = 0; $i < 10; $i++)
                <tr>
                    <td>承認待ち</td>
                    <td>西玲奈</td>
                    <td>2023/06/01</td>
                    <td>遅延のため</td>
                    <td>2023/06/02</td>
                    <td><a class="detail-link" href="#">詳細</a></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="tab-content" id="approved">
        <table class="app-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                <!--★承認済みのダミーデータ-->
                @for ($i = 0; $i < 3; $i++)
                <tr>
                    <td>承認済み</td>
                    <td>西玲奈</td>
                    <td>2023/05/20</td>
                    <td>早退のため</td>
                    <td>2023/05/21</td>
                    <td><a class="detail-link" href="#">詳細</a></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // すべてのタブボタンとコンテンツからactiveクラスを削除
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // クリックされたタブボタンにactiveクラスを追加
                button.classList.add('active');

                // 対応するタブコンテンツにactiveクラスを追加
                const targetTabId = button.dataset.tab;
                document.getElementById(targetTabId).classList.add('active');
            });
        });
    });
</script>
@endsection