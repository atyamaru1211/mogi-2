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
        <a class="tab-button {{ $activeTab === 'pending' ? 'active' : '' }}" href="/stamp_correction_request/list?tab=pending">承認待ち</a>
        <a class="tab-button {{ $activeTab === 'approved' ? 'active' : '' }}" href="/stamp_correction_request/list?tab=approved">承認済み</a>
    </div>
    
    <div class="tab-content active" id="requests">
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
                @forelse ($requests as $request)
                <tr>
                    <td>{{ $request->status === 'pending' ? '承認待ち' : ($request->status === 'approved' ? '承認済み' : '' ) }}</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ $request->requested_date->format('Y/m/d') }}</td>
                    <td>{{ $request->requested_note }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td><a class="detail-link" href="{{ $is_admin_view ? '/stamp_correction_request/approve/' . $request->id : '/attendance/' . $request->attendance_id }}">詳細</a></td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
