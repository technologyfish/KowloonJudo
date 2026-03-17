<?php

namespace App\Http\Controllers;

use App\Models\CompetitionRule;
use App\Models\DictItem;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\WechatPayService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * 小程序端 - 比赛相关接口
 */
class CompetitionController extends Controller
{
    // 成人组（可加报无差组别）
    private array $adultGroups = ['成人(18岁以上)', '大师1(30岁以上)', '大师2(40岁以上)'];

    /**
     * 生成商户订单号（微信要求 6-32 位）
     * 使用 Registration 的 order_no（13位）作为商户订单号，前缀 KJ
     */
    private function makeOutTradeNo(Registration $reg): string
    {
        return 'KJ' . $reg->order_no;
    }

    /**
     * 从商户订单号中解析出 registration order_no
     */
    public static function parseOutTradeNo(string $outTradeNo): ?string
    {
        // KJ2603121123456 → 去掉前 2 位 KJ
        if (str_starts_with($outTradeNo, 'KJ')) {
            return substr($outTradeNo, 2);
        }
        return null;
    }

    /**
     * 获取最新生效比赛规则
     * GET /api/competition/rule
     */
    public function getLatestRule()
    {
        $rule = CompetitionRule::where('status', 1)
            ->orderByDesc('id')
            ->first();

        return $this->success($rule);
    }

    /**
     * 提交报名表单，生成待支付订单
     * POST /api/competition/register
     */
    public function register(Request $request)
    {
        $rules = [
            'site_id'      => 'required|integer|exists:dict_items,id',
            'name_pinyin'  => 'sometimes|nullable|string|max:100',
            'name_cn'      => 'sometimes|nullable|string|max:50',
            'nationality'  => 'required|string|max:50',
            'gender'       => 'required|string',
            'id_type'      => 'required|string|in:id_card,passport',
            'id_card'      => 'sometimes|nullable|string|max:30',
            'passport_no'  => 'sometimes|nullable|string|max:50',
            'birthday'     => 'required|date_format:Y-m-d',
            'age_group'    => 'required|string|max:50',
            'belt_color'   => 'required|string|max:20',
            'weight_gi'    => 'sometimes|nullable|string|max:50',
            'weight_nogi'  => 'sometimes|nullable|string|max:50',
            'gi_open'      => 'sometimes|boolean',
            'nogi_open'    => 'sometimes|boolean',
            'team'         => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'email'        => 'required|email|max:100',
        ];

        $this->validate($request, $rules);

        // 姓名（拼音）和姓名（汉字）至少填一个
        $namePinyin = trim($request->input('name_pinyin', ''));
        $nameCn     = trim($request->input('name_cn', ''));
        if ($namePinyin === '' && $nameCn === '') {
            return $this->error('姓名（拼音）和姓名（汉字）至少填写一项', 422);
        }

        // 验证证件号码
        $idType = $request->input('id_type', 'id_card');
        if ($idType === 'id_card' && empty(trim($request->input('id_card', '')))) {
            return $this->error('请填写身份证号码', 422);
        }
        if ($idType === 'passport' && empty(trim($request->input('passport_no', '')))) {
            return $this->error('请填写护照号码', 422);
        }

        // 验证性别
        $gender = $request->input('gender');
        if (!in_array($gender, ['男', '女'], true)) {
            return $this->error('性别参数无效', 422);
        }

        // ── 出生年份 → 年龄组别校验 ────────────────────────────
        $birthday  = $request->input('birthday');
        $birthYear = (int) date('Y', strtotime($birthday));
        $ageGroup  = $request->input('age_group');

        $ageGroupMap = [
            '儿童组1(4-6岁)'    => [2020, 2022],
            '儿童组2(7-9岁)'    => [2017, 2019],
            '儿童组3(10-12岁)'  => [2014, 2016],
            '少年组(13-15岁)'   => [2011, 2013],
            '青年组(16-17岁)'   => [2009, 2010],
            '成人(18岁以上)'    => [1997, 2008],
            '大师1(30岁以上)'   => [1987, 1996],
            '大师2(40岁以上)'   => [0, 1986],
        ];

        // 判断出生年份是否匹配所选年龄组别（含降组规则）
        $allowed = false;
        if (isset($ageGroupMap[$ageGroup])) {
            [$minYear, $maxYear] = $ageGroupMap[$ageGroup];
            if ($birthYear >= $minYear && $birthYear <= $maxYear) {
                $allowed = true;
            }
        }
        // 特例：大师1（1987-1996）可降组选成人
        if (!$allowed && $ageGroup === '成人(18岁以上)' && $birthYear >= 1987 && $birthYear <= 1996) {
            $allowed = true;
        }
        // 特例：大师2（≤1986）可降组选大师1或成人
        if (!$allowed && in_array($ageGroup, ['大师1(30岁以上)', '成人(18岁以上)'], true) && $birthYear <= 1986) {
            $allowed = true;
        }

        if (!$allowed) {
            return $this->error('出生日期与所选年龄组别不匹配', 422);
        }

        // 至少选一种体重组别
        $weightGi   = $request->input('weight_gi', '');
        $weightNogi = $request->input('weight_nogi', '');
        if (empty($weightGi) && empty($weightNogi)) {
            return $this->error('请至少选择一种体重组别（道服或无道服）', 422);
        }
        $giOpen   = (bool) $request->input('gi_open', false);
        $nogiOpen = (bool) $request->input('nogi_open', false);
        $isAdult  = in_array($ageGroup, $this->adultGroups, true);

        // 非成人组不能选无差
        if (!$isAdult) {
            $giOpen   = false;
            $nogiOpen = false;
        }

        // 选了无差但没选对应体重
        if ($giOpen && empty($weightGi)) {
            return $this->error('加报道服无差组别需要先选择道服体重组别', 422);
        }
        if ($nogiOpen && empty($weightNogi)) {
            return $this->error('加报无道服无差组别需要先选择无道服体重组别', 422);
        }

        // ── 计算费用 ──────────────────────────────────────
        $fees        = Setting::getFees();
        $categoryFee = $fees['category_fee'];
        $openFee     = $fees['open_weight_fee'];

        $amount = 0;
        if (!empty($weightGi))   $amount += $categoryFee;
        if (!empty($weightNogi)) $amount += $categoryFee;
        if ($giOpen)             $amount += $openFee;
        if ($nogiOpen)           $amount += $openFee;

        // ── 生成套餐描述 ─────────────────────────────────
        $parts = [];
        if (!empty($weightGi)) {
            $parts[] = '道服';
            if ($giOpen) $parts[] = '道服无差';
        }
        if (!empty($weightNogi)) {
            $parts[] = '无道服';
            if ($nogiOpen) $parts[] = '无道服无差';
        }
        $packageLabel = implode(' + ', $parts);

        $user      = JWTAuth::parseToken()->authenticate();
        $genderNum = $gender === '男' ? 1 : 2;

        // 获取赛事站点信息（从字典表）
        $siteId   = $request->input('site_id');
        $site     = DictItem::findOrFail($siteId);

        $orderNo = Registration::generateOrderNo();

        $reg = Registration::create([
            'user_id'       => $user->id,
            'site_id'       => $site->id,
            'site_name'     => $site->label,
            'order_no'      => $orderNo,
            'name_pinyin'   => $request->input('name_pinyin', ''),
            'name_cn'       => $request->input('name_cn', ''),
            'nationality'   => $request->input('nationality'),
            'gender'        => $genderNum,
            'id_type'       => $idType,
            'id_card'       => $request->input('id_card', ''),
            'passport_no'   => $request->input('passport_no', ''),
            'birthday'      => $birthday,
            'age_group'     => $ageGroup,
            'belt_color'    => $request->input('belt_color'),
            'weight_gi'     => $weightGi ?: null,
            'weight_nogi'   => $weightNogi ?: null,
            'gi_open'       => $giOpen,
            'nogi_open'     => $nogiOpen,
            'team'          => $request->input('team'),
            'phone'         => $request->input('phone'),
            'email'         => $request->input('email'),
            'package_label' => $packageLabel,
            'amount'        => $amount,
            'pay_status'    => 'pending',
        ]);

        return $this->success([
            'order_id'      => $reg->id,
            'order_no'      => $orderNo,
            'package_label' => $packageLabel,
            'amount'        => $amount,
        ], '报名信息提交成功，请完成支付');
    }

    /**
     * 创建微信支付订单
     * POST /api/competition/pay/create
     */
    public function createPayOrder(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        $reg  = Registration::where('id', $request->input('order_id'))
            ->where('user_id', $user->id)
            ->where('pay_status', 'pending')
            ->firstOrFail();

        // ── 调用微信支付 JSAPI 下单 ────────────────────────────
        $wxPay     = new WechatPayService();
        $outTradeNo = $this->makeOutTradeNo($reg);

        $payParams = $wxPay->createJsapiOrder(
            $outTradeNo,                                          // 商户订单号（6-32位）
            (float)  $reg->amount,                               // 金额（元）
            '九龙柔道报名 - ' . ($reg->package_label ?: '比赛报名'),  // 商品描述
            $user->openid                                        // 支付者 openid
        );

        // 保存 prepay_id
        $reg->update(['wx_prepay_id' => $payParams['package']]);

        Log::info('支付订单创建', [
            'order_id' => $reg->id,
            'amount'   => $reg->amount,
            'mock'     => $wxPay->isMockMode(),
        ]);

        return $this->success($payParams, '支付订单创建成功');
    }

    /**
     * 查询支付结果（前端支付后主动查询，防回调丢失）
     * GET /api/competition/pay/query?order_id=xxx
     */
    public function queryPayResult(Request $request)
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $orderId = $request->input('order_id');
        $reg     = Registration::where('id', $orderId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // 已支付直接返回
        if ($reg->pay_status === 'paid') {
            return $this->success(['pay_status' => 'paid'], '已支付');
        }

        // 未支付时主动查询微信
        $wxPay = new WechatPayService();

        if ($wxPay->isMockMode()) {
            // Mock 模式：前端调了 requestPayment 就算成功，直接标记已支付
            $reg->update([
                'pay_status' => 'paid',
                'paid_at'    => Carbon::now(),
            ]);
            return $this->success(['pay_status' => 'paid'], '已支付（模拟）');
        }

        // 真实模式：主动向微信查询订单状态
        try {
            $result = $wxPay->queryOrder($this->makeOutTradeNo($reg));
            if (($result['trade_state'] ?? '') === 'SUCCESS') {
                $reg->update([
                    'pay_status'        => 'paid',
                    'wx_transaction_id' => $result['transaction_id'] ?? '',
                    'paid_at'           => Carbon::now(),
                ]);
                return $this->success(['pay_status' => 'paid'], '已支付');
            }
        } catch (\Exception $e) {
            Log::warning('主动查询微信订单失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }

        return $this->success(['pay_status' => $reg->pay_status]);
    }

    /**
     * 微信支付异步回调（服务端通知）
     * POST /api/competition/pay/notify
     *
     * 微信支付 v3 回调 body 格式：
     * {
     *   "id": "...",
     *   "event_type": "TRANSACTION.SUCCESS",
     *   "resource": { "ciphertext": "...", "nonce": "...", "associated_data": "..." }
     * }
     *
     * Mock 模式下接受明文 { out_trade_no, transaction_id }
     */
    public function payNotify(Request $request)
    {
        $body = $request->getContent();
        $data = json_decode($body, true);

        Log::info('微信支付回调收到', ['body' => $data]);

        $wxPay = new WechatPayService();

        if ($wxPay->isMockMode()) {
            // ── Mock 模式：直接使用明文数据 ────────────────────
            $orderData = $data;
        } else {
            // ── 真实模式：解密 resource ────────────────────────
            if (($data['event_type'] ?? '') !== 'TRANSACTION.SUCCESS') {
                return response()->json(['code' => 'SUCCESS', 'message' => 'OK']);
            }

            $orderData = $wxPay->decryptNotification($data);

            if (!$orderData) {
                Log::error('微信支付回调解密失败');
                return response()->json(['code' => 'FAIL', 'message' => '解密失败'], 400);
            }
        }

        $outTradeNo    = $orderData['out_trade_no'] ?? null;
        $transactionId = $orderData['transaction_id'] ?? '';
        $tradeState    = $orderData['trade_state'] ?? 'SUCCESS';

        if ($outTradeNo && $tradeState === 'SUCCESS') {
            // 从商户订单号中解析出 order_no
            $orderNo = self::parseOutTradeNo($outTradeNo);

            $updated = Registration::where('order_no', $orderNo)
                ->where('pay_status', 'pending')
                ->update([
                    'pay_status'        => 'paid',
                    'wx_transaction_id' => $transactionId,
                    'paid_at'           => Carbon::now(),
                ]);

            Log::info('支付回调处理完成', [
                'out_trade_no' => $outTradeNo,
                'order_no'     => $orderNo,
                'updated'      => $updated,
            ]);
        }

        return response()->json(['code' => 'SUCCESS', 'message' => 'OK']);
    }

    /**
     * 取消订单（仅待支付的订单可取消）
     * POST /api/competition/cancel
     */
    public function cancelOrder(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        $reg  = Registration::where('id', $request->input('order_id'))
            ->where('user_id', $user->id)
            ->where('pay_status', 'pending')
            ->firstOrFail();

        $reg->update(['pay_status' => 'cancelled']);

        Log::info('用户取消订单', ['order_id' => $reg->id, 'user_id' => $user->id]);

        return $this->success(null, '订单已取消');
    }

    /**
     * 用户申请退款（仅已支付订单可申请）
     * POST /api/competition/refund
     */
    public function requestRefund(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        $reg  = Registration::where('id', $request->input('order_id'))
            ->where('user_id', $user->id)
            ->where('pay_status', 'paid')
            ->firstOrFail();

        $reg->update(['pay_status' => 'refund_pending']);

        Log::info('用户申请退款', ['order_id' => $reg->id, 'user_id' => $user->id]);

        return $this->success(null, '退款申请已提交，请等待处理');
    }

    /**
     * 获取单个订单详情
     * GET /api/competition/order-detail?id=xxx
     */
    public function orderDetail(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $id   = $request->input('id');
        $r    = Registration::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return $this->success([
            'id'            => $r->id,
            'order_no'      => $r->order_no,
            'site_id'       => $r->site_id,
            'site_name'     => $r->site_name,
            'name_pinyin'   => $r->name_pinyin,
            'name_cn'       => $r->name_cn,
            'gender'        => $r->gender_text,
            'birthday'      => $r->birthday ? $r->birthday->format('Y-m-d') : null,
            'nationality'   => $r->nationality,
            'id_type'       => $r->id_type ?? 'id_card',
            'id_card'       => $r->id_card,
            'passport_no'   => $r->passport_no ?? '',
            'age_group'     => $r->age_group,
            'belt_color'    => $r->belt_color,
            'weight_gi'     => $r->weight_gi,
            'weight_nogi'   => $r->weight_nogi,
            'gi_open'       => $r->gi_open,
            'nogi_open'     => $r->nogi_open,
            'team'          => $r->team,
            'phone'         => $r->phone,
            'email'         => $r->email,
            'package_label' => $r->package_label,
            'amount'        => $r->amount,
            'pay_status'    => $r->pay_status,
            'paid_at'       => $r->paid_at ? $r->paid_at->format('Y-m-d H:i:s') : null,
            'created_at'    => $r->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取当前用户订单列表（支持状态筛选 + 分页）
     * GET /api/competition/orders?status=pending&page=1&per_page=10
     */
    public function myOrders(Request $request)
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $status  = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 10);
        $page    = (int) $request->input('page', 1);

        $query = Registration::where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($status === 'after_sale') {
            // 售后 tab：申请退款中 + 已退款
            $query->whereIn('pay_status', ['refund_pending', 'refunded']);
        } elseif ($status !== 'all') {
            $query->where('pay_status', $status);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $perPage;
        $items  = $query->offset($offset)->limit($perPage)->get()
            ->map(function ($r) {
                return [
                    'id'            => $r->id,
                    'order_no'      => $r->order_no,
                    'site_id'       => $r->site_id,
                    'site_name'     => $r->site_name,
                    'name_pinyin'   => $r->name_pinyin,
                    'name_cn'       => $r->name_cn,
                    'gender'        => $r->gender_text,
                    'birthday'      => $r->birthday ? $r->birthday->format('Y-m-d') : null,
                    'age_group'     => $r->age_group,
                    'belt_color'    => $r->belt_color,
                    'weight_gi'     => $r->weight_gi,
                    'weight_nogi'   => $r->weight_nogi,
                    'gi_open'       => $r->gi_open,
                    'nogi_open'     => $r->nogi_open,
                    'team'          => $r->team,
                    'package_label' => $r->package_label,
                    'amount'        => $r->amount,
                    'pay_status'    => $r->pay_status,
                    'paid_at'       => $r->paid_at ? $r->paid_at->format('Y-m-d H:i') : null,
                    'created_at'    => $r->created_at?->format('Y-m-d H:i'),
                ];
            });

        return $this->success([
            'list'     => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'has_more' => ($offset + $perPage) < $total,
        ]);
    }
}
