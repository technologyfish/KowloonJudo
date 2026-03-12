<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception): JsonResponse
    {
        // 验证失败
        if ($exception instanceof ValidationException) {
            $errors = collect($exception->errors())->flatten()->first();
            return response()->json(['code' => 422, 'message' => $errors], 422);
        }

        // 模型未找到
        if ($exception instanceof ModelNotFoundException) {
            return response()->json(['code' => 404, 'message' => '资源不存在'], 404);
        }

        // 权限不足
        if ($exception instanceof AuthorizationException) {
            return response()->json(['code' => 403, 'message' => '权限不足'], 403);
        }

        // HTTP 异常
        if ($exception instanceof HttpException) {
            return response()->json(
                ['code' => $exception->getStatusCode(), 'message' => $exception->getMessage() ?: '请求错误'],
                $exception->getStatusCode()
            );
        }

        // 其他异常
        $status = 500;
        $message = app()->environment('production') ? '服务器内部错误' : $exception->getMessage();

        return response()->json(['code' => $status, 'message' => $message], $status);
    }
}
