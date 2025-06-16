<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function register(): void // <- このメソッド自体は残します
    {
        // ★★★ここにLogoutResponseの正しいバインディングを追加します★★★
        $this->app->singleton(
            LogoutResponseContract::class,
            \App\Http\Responses\LogoutResponse::class // 作成するカスタムLogoutResponseクラスをバインド
        );
    }


    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

    }
}
