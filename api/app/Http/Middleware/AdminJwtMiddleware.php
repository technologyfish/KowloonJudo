<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AdminJwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            // 使用 admin guard 验证 token → 从 admins 表查找
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json(['code' => 401, 'message' => '管理员不存在或 Token 无效'], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['code' => 401, 'message' => 'Token 已过期，请重新登录'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['code' => 401, 'message' => 'Token 无效'], 401);
        } catch (Exception $e) {
            return response()->json(['code' => 401, 'message' => '未提供授权 Token'], 401);
        }

        return $next($request);
    }
}
