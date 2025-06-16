<header class="header"><!--★-->
    <div class="header__logo">
        <a href="/"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
    </div>
    @if (Auth::guard('web')->check() || Auth::guard('admin')->check())
        <!--★ユーザーが認証済の場合-->
        <nav class="header__nav">
            <ul>
                @if(Auth::guard('admin')->check())
                    <!--★管理者向けナビゲーション-->
                    <li><a href="/admin/attendance/list">勤怠一覧</a></li>
                    <li><a href="/admin/staff/list">スタッフ一覧</a></li>
                    <li><a href="/stamp_correction_request/list">申請一覧</a></li>
                    <li>
                        <form action="/admin/logout" method="post">
                            @csrf
                            <button class="header__logout" type="submit">ログアウト</button>
                        </form>
                    </li>
                @elseif(Auth::guard('web')->check())
                    <!--★一般ユーザーナビゲーション-->
                    <li><a href="/attendance">勤怠</a></li>
                    <li><a href="/attendance/list">勤怠一覧</a></li>
                    <li><a href="/stamp_correction_request/list">申請</a></li>
                    <li>
                        <form action="/logout" method="post">
                            @csrf
                            <button class="header__logout" type="submit">ログアウト</button>
                        </form>
                    </li>
                @endif
            </ul>
        </nav>
    @else
    @endif
</header>