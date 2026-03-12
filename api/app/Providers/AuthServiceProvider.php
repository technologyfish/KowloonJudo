<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 注册策略
    }

    public function register()
    {
        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->header('Authorization')) {
                $token = str_replace('Bearer ', '', $request->header('Authorization'));
                try {
                    return \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                } catch (\Exception $e) {
                    return null;
                }
            }
            return null;
        });
    }
}
