<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 微信支付 v3 JSAPI 支付服务
 *
 * 完整流程：
 *   1. 后端调用微信统一下单 API → 获取 prepay_id
 *   2. 后端对支付参数签名 → 返回给前端
 *   3. 前端调用 uni.requestPayment → 用户在微信内完成支付
 *   4. 微信异步通知后端（payNotify）
 *   5. 前端主动查询支付结果
 *
 * 当关键配置缺失时，自动降级为 mock 模式。
 */
class WechatPayService
{
    /** @var string 小程序 appid */
    private string $appId;

    /** @var string 商户号 */
    private string $mchId;

    /** @var string API v3 密钥（用于回调解密） */
    private string $apiV3Key;

    /** @var string 商户私钥 PEM 文件的绝对路径 */
    private string $certKeyPath;

    /** @var string 商户证书序列号 */
    private string $certSerial;

    /** @var string 支付回调地址 */
    private string $notifyUrl;

    /** @var bool 是否为 mock 模式 */
    private bool $isMock;

    public function __construct()
    {
        $this->appId     = env('WX_APPID', '');
        $this->mchId     = env('WECHAT_PAY_MCH_ID', '');
        $this->apiV3Key  = env('WECHAT_PAY_KEY', '');
        $this->notifyUrl = env('WECHAT_PAY_NOTIFY_URL', '');

        // 证书路径：支持相对路径和绝对路径
        $certPath = env('WECHAT_PAY_CERT_PATH', '');
        if ($certPath && !str_starts_with($certPath, '/') && !preg_match('/^[A-Za-z]:/', $certPath)) {
            $certPath = base_path($certPath);
        }
        $this->certKeyPath = $certPath;

        // 证书序列号（在微信商户平台 → API安全 → API证书 中查看）
        $this->certSerial = env('WECHAT_PAY_CERT_SERIAL', '');

        // 关键配置缺失则降级为 mock 模式
        $this->isMock = empty($this->apiV3Key)
                     || empty($this->mchId)
                     || empty($this->certKeyPath)
                     || !file_exists($this->certKeyPath)
                     || empty($this->certSerial);

        Log::debug('[WechatPay] 初始化', [
            'mock'      => $this->isMock,
            'mchId'     => $this->mchId,
            'certPath'  => $this->certKeyPath,
            'certExist' => !empty($this->certKeyPath) && file_exists($this->certKeyPath),
            'serial'    => $this->certSerial ? '已配置' : '未配置',
        ]);
    }

    /**
     * 是否处于 mock 模式
     */
    public function isMockMode(): bool
    {
        return $this->isMock;
    }

    // ═══════════════════════════════════════════════════════════════
    //  统一下单 (JSAPI)
    // ═══════════════════════════════════════════════════════════════

    /**
     * 创建 JSAPI 支付订单，返回前端 uni.requestPayment 所需参数
     */
    public function createJsapiOrder(string $outTradeNo, float $amount, string $description, string $openid): array
    {
        if ($this->isMock) {
            return $this->mockCreateOrder($outTradeNo, $amount);
        }

        return $this->realCreateOrder($outTradeNo, $amount, $description, $openid);
    }

    // ─── 真实下单 ────────────────────────────────────────────────

    private function realCreateOrder(string $outTradeNo, float $amount, string $description, string $openid): array
    {
        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';

        $body = [
            'appid'        => $this->appId,
            'mchid'        => $this->mchId,
            'description'  => mb_substr($description, 0, 127),
            'out_trade_no' => $outTradeNo,
            'notify_url'   => $this->notifyUrl,
            'amount'       => [
                'total'    => (int) round($amount * 100), // 微信要求单位是分
                'currency' => 'CNY',
            ],
            'payer'        => [
                'openid' => $openid,
            ],
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);

        Log::info('[WechatPay] 发起下单', [
            'out_trade_no' => $outTradeNo,
            'amount_fen'   => (int) round($amount * 100),
            'openid'       => $openid,
        ]);

        $response = $this->requestV3('POST', $url, $jsonBody);

        if (!isset($response['prepay_id'])) {
            Log::error('[WechatPay] 下单失败', ['response' => $response, 'body' => $body]);
            throw new \RuntimeException('微信支付下单失败：' . ($response['message'] ?? json_encode($response)));
        }

        $prepayId = $response['prepay_id'];
        Log::info('[WechatPay] 下单成功', ['prepay_id' => $prepayId]);

        return $this->buildPayParams($prepayId);
    }

    /**
     * 生成前端 uni.requestPayment 所需参数并用商户私钥签名
     */
    private function buildPayParams(string $prepayId): array
    {
        $timeStamp = (string) time();
        $nonceStr  = bin2hex(random_bytes(16));
        $package   = 'prepay_id=' . $prepayId;

        // 签名字符串：appId\n时间戳\n随机串\npackage\n
        $message = $this->appId . "\n"
                 . $timeStamp . "\n"
                 . $nonceStr . "\n"
                 . $package . "\n";

        $privateKey = file_get_contents($this->certKeyPath);
        openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $paySign = base64_encode($signature);

        return [
            'timeStamp' => $timeStamp,
            'nonceStr'  => $nonceStr,
            'package'   => $package,
            'signType'  => 'RSA',
            'paySign'   => $paySign,
        ];
    }

    // ─── Mock 下单 ───────────────────────────────────────────────

    private function mockCreateOrder(string $outTradeNo, float $amount): array
    {
        Log::info('[WechatPay MOCK] 模拟下单', [
            'out_trade_no' => $outTradeNo,
            'amount'       => $amount,
        ]);

        $timeStamp = (string) time();
        $nonceStr  = bin2hex(random_bytes(16));
        $prepayId  = 'wx_mock_' . $outTradeNo . '_' . $timeStamp;

        return [
            'timeStamp' => $timeStamp,
            'nonceStr'  => $nonceStr,
            'package'   => 'prepay_id=' . $prepayId,
            'signType'  => 'RSA',
            'paySign'   => 'mock_sign_' . md5($outTradeNo . $timeStamp),
            'mock'      => true,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  退款
    // ═══════════════════════════════════════════════════════════════

    /**
     * 发起退款
     *
     * @param  string $outTradeNo     商户订单号
     * @param  float  $refundAmount   退款金额（元）
     * @param  float  $totalAmount    原订单金额（元）
     * @param  string $transactionId  微信交易流水号（可选）
     * @return array  退款结果
     */
    public function refund(string $outTradeNo, float $refundAmount, float $totalAmount, string $transactionId = ''): array
    {
        if ($this->isMock) {
            Log::info('[WechatPay MOCK] 模拟退款', [
                'out_trade_no' => $outTradeNo,
                'refund_amount' => $refundAmount,
            ]);
            return [
                'status'          => 'SUCCESS',
                'out_trade_no'    => $outTradeNo,
                'out_refund_no'   => 'mock_refund_' . $outTradeNo,
                'mock'            => true,
            ];
        }

        $url = 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds';

        $outRefundNo = 'R' . $outTradeNo . '_' . time();

        $body = [
            'out_trade_no'  => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'reason'        => '管理员退款',
            'amount'        => [
                'refund'   => (int) round($refundAmount * 100),
                'total'    => (int) round($totalAmount * 100),
                'currency' => 'CNY',
            ],
        ];

        // 如果有微信交易号，优先用
        if ($transactionId) {
            $body['transaction_id'] = $transactionId;
            unset($body['out_trade_no']);
        }

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);

        Log::info('[WechatPay] 发起退款', [
            'out_trade_no'  => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'refund_fen'    => (int) round($refundAmount * 100),
        ]);

        $response = $this->requestV3('POST', $url, $jsonBody);

        if (!isset($response['status'])) {
            Log::error('[WechatPay] 退款失败', ['response' => $response]);
            throw new \RuntimeException('微信退款失败：' . ($response['message'] ?? json_encode($response)));
        }

        Log::info('[WechatPay] 退款结果', $response);

        return $response;
    }

    // ═══════════════════════════════════════════════════════════════
    //  回调验签 & 解密
    // ═══════════════════════════════════════════════════════════════

    /**
     * 解密微信支付 v3 回调通知中的 resource 字段
     */
    public function decryptNotification(array $notification): ?array
    {
        if ($this->isMock) {
            return $this->mockDecrypt($notification);
        }

        $resource = $notification['resource'] ?? null;
        if (!$resource) return null;

        $tagLength  = 16;
        $cipherData = substr(base64_decode($resource['ciphertext']), 0, -$tagLength);
        $tag        = substr(base64_decode($resource['ciphertext']), -$tagLength);
        $nonce      = $resource['nonce'];
        $aad        = $resource['associated_data'] ?? '';

        $decrypted = openssl_decrypt(
            $cipherData,
            'aes-256-gcm',
            $this->apiV3Key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $aad
        );

        if ($decrypted === false) {
            Log::error('[WechatPay] 回调解密失败', ['resource' => $resource]);
            return null;
        }

        return json_decode($decrypted, true);
    }

    private function mockDecrypt(array $notification): ?array
    {
        return $notification;
    }

    // ═══════════════════════════════════════════════════════════════
    //  主动查询订单
    // ═══════════════════════════════════════════════════════════════

    /**
     * 主动查询微信支付订单状态
     */
    public function queryOrder(string $outTradeNo): array
    {
        if ($this->isMock) {
            return ['trade_state' => 'SUCCESS', 'mock' => true];
        }

        $url = "https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/{$outTradeNo}"
             . "?mchid={$this->mchId}";

        return $this->requestV3('GET', $url);
    }

    // ═══════════════════════════════════════════════════════════════
    //  微信支付 v3 HTTP 请求
    // ═══════════════════════════════════════════════════════════════

    /**
     * 向微信支付 v3 API 发送签名请求
     */
    private function requestV3(string $method, string $url, string $body = ''): array
    {
        $timestamp = time();
        $nonce     = bin2hex(random_bytes(16));

        // 解析 URL path
        $urlParts = parse_url($url);
        $urlPath  = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');

        // 构造签名串
        $message = strtoupper($method) . "\n"
                 . $urlPath . "\n"
                 . $timestamp . "\n"
                 . $nonce . "\n"
                 . $body . "\n";

        // RSA-SHA256 签名
        $privateKey = file_get_contents($this->certKeyPath);
        openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($signature);

        $authorization = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%d",serial_no="%s"',
            $this->mchId, $nonce, $sign, $timestamp, $this->certSerial
        );

        // cURL 请求
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: KowloonJudo/1.0 PHP/' . PHP_VERSION,
                'Authorization: ' . $authorization,
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            Log::error('[WechatPay] 网络请求失败', ['error' => $error, 'url' => $url]);
            throw new \RuntimeException('微信支付网络请求失败：' . $error);
        }

        curl_close($ch);

        $result = json_decode($response, true) ?: [];

        Log::debug('[WechatPay] API 响应', [
            'method'    => $method,
            'url'       => $url,
            'http_code' => $httpCode,
            'response'  => $result,
        ]);

        if ($httpCode >= 400) {
            Log::error('[WechatPay] API 返回错误', [
                'http_code' => $httpCode,
                'response'  => $result,
                'url'       => $url,
            ]);
        }

        return $result;
    }
}
