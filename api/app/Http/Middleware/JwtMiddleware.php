<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['code' => 401, 'message' => '用户不存在'], 401);
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
