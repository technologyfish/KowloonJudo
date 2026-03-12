<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class WxAuthController extends Controller
{
    /**
     * 微信小程序登录
     * POST /api/auth/wx-login
     * Body: { code: string, nickname?: string, avatar?: string }
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|string',
        ]);

        $code = $request->input('code');

        // ── 1. 用 code 换取 openid & session_key ──────────────────
        $client   = new Client(['timeout' => 10]);
        $response = $client->get('https://api.weixin.qq.com/sns/jscode2session', [
            'query' => [
                'appid'      => env('WX_APPID'),
                'secret'     => env('WX_SECRET'),
                'js_code'    => $code,
                'grant_type' => 'authorization_code',
            ],
        ]);

        $wxData = json_decode($response->getBody()->getContents(), true);

        if (!empty($wxData['errcode']) && $wxData['errcode'] !== 0) {
            return $this->error(
                '微信登录失败：' . ($wxData['errmsg'] ?? '未知错误'),
                400
            );
        }

        $openid = $wxData['openid'] ?? null;

        if (!$openid) {
            return $this->error('未获取到 openid', 400);
        }

        // ── 2. 查找或创建用户（openid 唯一标识，无需 unionid）─────
        // 新用户默认昵称：微信用户 + 随机6位字母数字，如 微信用户a3f9k2
        $randomSuffix    = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
        $defaultNickname = '微信用户' . $randomSuffix;

        $user = User::firstOrCreate(
            ['openid' => $openid],
            [
                'nickname' => $defaultNickname,
                'avatar'   => '',
                'status'   => 1,
            ]
        );

        // ── 3. 生成 JWT Token ─────────────────────────────────────
        $token = JWTAuth::fromUser($user);

        return $this->success([
            'token'       => $token,
            'is_new_user' => $user->wasRecentlyCreated,
            'user'        => [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
                'phone'    => $user->phone,
                'gender'   => $user->gender,
                'birthday' => $user->birthday ? $user->birthday->format('Y-m-d') : null,
            ],
        ], '登录成功');
    }
}
