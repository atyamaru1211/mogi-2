@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/staff.css')  }}">
@endsection

@section('body_class', 'has-background')

@section('content')

<div class="content">
    <h1 class="page-title">スタッフ一覧</h1>

    <table class="app-table staff-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td><a class="detail-link" href="/admin/attendance/staff/{{ $user->id }}">詳細</a></td>
            </tr>
            @empty
            @endforelse
        </tbody>
    </table>
</div>

@endsection