<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * 成功响应
     */
    protected function success($data = null, string $message = 'success', int $code = 200)
    {
        return response()->json([
            'code'    => 0,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * 失败响应
     */
    protected function error(string $message = 'error', int $code = 400, $data = null)
    {
        return response()->json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * 分页响应
     */
    protected function paginate($paginator, string $message = 'success')
    {
        return response()->json([
            'code'    => 0,
            'message' => $message,
            'data'    => [
                'data'         => $paginator->items(),
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
