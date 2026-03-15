<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionRule;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 后台管理 - 比赛规则 & 报名记录
 */
class CompetitionRuleController extends Controller
{
    // ─── 比赛规则 CRUD ────────────────────────────────────────

    /**
     * 规则列表
     * GET /api/admin/competition/rules
     */
    public function ruleIndex()
    {
        $list = CompetitionRule::orderByDesc('id')->get();
        return $this->success($list);
    }

    /**
     * 规则详情
     * GET /api/admin/competition/rules/{id}
     */
    public function ruleShow($id)
    {
        return $this->success(CompetitionRule::findOrFail($id));
    }

    /**
     * 创建规则
     * POST /api/admin/competition/rules
     */
    public function ruleStore(Request $request)
    {
        $this->validate($request, [
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        $rule = CompetitionRule::create([
            'title'     => $request->input('title'),
            'summary'   => $request->input('summary', ''),
            'content'   => $request->input('content'),
            'rule_date' => $request->input('created_at', now()->toDateString()),
            'status'    => 1,
        ]);

        return $this->success($rule, '创建成功');
    }

    /**
     * 更新规则
     * PUT /api/admin/competition/rules/{id}
     */
    public function ruleUpdate(Request $request, $id)
    {
        $rule = CompetitionRule::findOrFail($id);

        $this->validate($request, [
            'title'   => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
        ]);

        $rule->update([
            'title'     => $request->input('title', $rule->title),
            'summary'   => $request->input('summary', $rule->summary),
            'content'   => $request->input('content', $rule->content),
            'rule_date' => $request->input('created_at', $rule->rule_date),
        ]);

        return $this->success($rule, '更新成功');
    }

    /**
     * 删除规则
     * DELETE /api/admin/competition/rules/{id}
     */
    public function ruleDestroy($id)
    {
        CompetitionRule::findOrFail($id)->delete();
        return $this->success(null, '删除成功');
    }

    // ─── 报名记录管理 ─────────────────────────────────────────

    /**
     * 报名记录列表（分页 + 搜索）
     * GET /api/admin/competition/registrations
     */
    public function registrationIndex(Request $request)
    {
        $keyword       = $request->input('keyword');
        $payStatus     = $request->input('pay_status');
        $confirmStatus = $request->input('confirm_status');
        $ageGroup      = $request->input('age_group');
        $pageSize      = (int) $request->input('pageSize', 20);

        $query = Registration::with('user')->orderByDesc('id');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name_cn',     'like', "%{$keyword}%")
                  ->orWhere('name_pinyin','like', "%{$keyword}%")
                  ->orWhere('phone',      'like', "%{$keyword}%")
                  ->orWhere('team',       'like', "%{$keyword}%")
                  ->orWhere('order_no',   'like', "%{$keyword}%");
            });
        }
        if ($payStatus)     $query->where('pay_status', $payStatus);
        if ($confirmStatus) $query->where('confirm_status', $confirmStatus);
        if ($ageGroup)      $query->where('age_group', $ageGroup);

        $paginator = $query->paginate($pageSize);

        // 格式化
        $items = collect($paginator->items())->map(fn($r) => [
            'id'             => $r->id,
            'order_no'       => $r->order_no,
            'name_cn'        => $r->name_cn,
            'name_pinyin'    => $r->name_pinyin,
            'nationality'    => $r->nationality,
            'gender'         => $r->gender == 1 ? '男' : '女',
            'id_card'        => $r->id_card,
            'birthday'       => $r->birthday ? $r->birthday->format('Y-m-d') : null,
            'age_group'      => $r->age_group,
            'belt_color'     => $r->belt_color,
            'weight_gi'      => $r->weight_gi,
            'weight_nogi'    => $r->weight_nogi,
            'gi_open'        => $r->gi_open,
            'nogi_open'      => $r->nogi_open,
            'team'           => $r->team,
            'phone'          => $r->phone,
            'email'          => $r->email,
            'package_label'  => $r->package_label,
            'amount'         => $r->amount,
            'pay_status'     => $r->pay_status,
            'confirm_status' => $r->confirm_status ?? 'pending',
            'wx_transaction_id' => $r->wx_transaction_id,
            'created_at'     => $r->created_at?->format('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'data'         => $items,
            'total'        => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'last_page'    => $paginator->lastPage(),
        ]);
    }

    /**
     * 导出报名数据 CSV（仅已确认的记录）
     * GET /api/admin/competition/registrations/export
     */
    public function registrationExport(Request $request)
    {
        $query = Registration::orderByDesc('id');

        // 默认只导出已确认的，可通过 ?all=1 导出全部
        if (!$request->input('all')) {
            $query->where('confirm_status', 'confirmed');
        }

        $regs = $query->get();

        $headers = ['ID','订单号','姓名(汉字)','姓名(拼音)','国籍','性别','出生日期','年龄组别','带色',
                    '体重(道服)','道服无差','体重(无道服)','无道服无差','战队','手机号','邮箱',
                    '套餐','金额','支付状态','审核状态','报名时间'];

        $payStatusMap     = ['pending' => '待支付', 'paid' => '已支付', 'cancelled' => '已取消', 'refund_pending' => '申请退款中', 'refunded' => '已退款'];
        $confirmStatusMap = ['pending' => '未通过', 'confirmed' => '已通过'];

        $rows = $regs->map(fn($r) => [
            $r->id,
            (string) ($r->order_no ?? ''),
            (string) ($r->name_cn ?? ''),
            (string) ($r->name_pinyin ?? ''),
            (string) ($r->nationality ?? ''),
            $r->gender == 1 ? '男' : '女',
            $r->birthday ? $r->birthday->format('Y-m-d') : '',
            (string) ($r->age_group ?? ''),
            (string) ($r->belt_color ?? ''),
            (string) ($r->weight_gi ?? ''),
            $r->gi_open ? '是' : '否',
            (string) ($r->weight_nogi ?? ''),
            $r->nogi_open ? '是' : '否',
            (string) ($r->team ?? ''),
            (string) ($r->phone ?? ''),
            (string) ($r->email ?? ''),
            (string) ($r->package_label ?? ''),
            (string) ($r->amount ?? 0),
            $payStatusMap[$r->pay_status] ?? $r->pay_status,
            $confirmStatusMap[$r->confirm_status ?? 'pending'] ?? ($r->confirm_status ?? ''),
            $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '',
        ])->toArray();

        // UTF-8 BOM（让 Excel 正确识别中文）
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                $row
            )) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="registrations_' . date('Ymd') . '.csv"',
        ]);
    }

    // ─── 确认报名 ─────────────────────────────────────────────

    /**
     * 确认报名记录
     * PUT /api/admin/competition/registrations/{id}/confirm
     */
    public function registrationConfirm($id)
    {
        $reg = Registration::findOrFail($id);

        // 切换审核状态
        $newStatus = $reg->confirm_status === 'confirmed' ? 'pending' : 'confirmed';
        $reg->update(['confirm_status' => $newStatus]);

        $label = $newStatus === 'confirmed' ? '审核通过' : '取消审核';
        Log::info("管理员{$label}", ['registration_id' => $id]);

        return $this->success(null, $label . '成功');
    }

    /**
     * 批量确认报名记录
     * PUT /api/admin/competition/registrations/batch-confirm
     */
    public function registrationBatchConfirm(Request $request)
    {
        $this->validate($request, ['ids' => 'required|array', 'ids.*' => 'integer']);

        $ids = $request->input('ids');
        $count = Registration::whereIn('id', $ids)
            ->where('pay_status', 'paid')
            ->where('confirm_status', 'pending')
            ->update(['confirm_status' => 'confirmed']);

        Log::info('管理员批量确认报名', ['ids' => $ids, 'count' => $count]);

        return $this->success(['count' => $count], "已确认 {$count} 条记录");
    }

    // ─── 退款 ─────────────────────────────────────────────────

    /**
     * 退款
     * PUT /api/admin/competition/registrations/{id}/refund
     */
    public function registrationRefund($id)
    {
        $reg = Registration::findOrFail($id);

        if (!in_array($reg->pay_status, ['paid', 'refund_pending'], true)) {
            return $this->error('只有已支付或申请退款中的订单才能退款', 422);
        }

        // 调用微信退款 API
        try {
            $wxPay      = new \App\Services\WechatPayService();
            $outTradeNo = 'KJ' . $reg->order_no;
            $wxPay->refund(
                $outTradeNo,
                (float) $reg->amount,
                (float) $reg->amount,
                $reg->wx_transaction_id ?: ''
            );
        } catch (\Exception $e) {
            Log::warning('微信退款 API 调用失败，仅更新本地状态', [
                'registration_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        $reg->update([
            'pay_status'     => 'refunded',
            'confirm_status' => 'pending',
        ]);

        Log::info('管理员退款', ['registration_id' => $id, 'amount' => $reg->amount]);

        return $this->success(null, '退款成功');
    }

    // ─── 修改报名记录 ─────────────────────────────────────────

    /**
     * 修改报名记录
     * PUT /api/admin/competition/registrations/{id}
     */
    public function registrationUpdate(Request $request, $id)
    {
        $reg = Registration::findOrFail($id);

        $this->validate($request, [
            'name_cn'      => 'sometimes|string|max:50',
            'name_pinyin'  => 'sometimes|string|max:100',
            'nationality'  => 'sometimes|string|max:50',
            'gender'       => 'sometimes|string',
            'id_card'      => 'sometimes|string|max:30',
            'birthday'     => 'sometimes|nullable|date',
            'age_group'    => 'sometimes|string|max:50',
            'belt_color'   => 'sometimes|string|max:20',
            'weight_gi'    => 'sometimes|nullable|string|max:50',
            'weight_nogi'  => 'sometimes|nullable|string|max:50',
            'gi_open'      => 'sometimes|boolean',
            'nogi_open'    => 'sometimes|boolean',
            'team'         => 'sometimes|string|max:100',
            'phone'        => 'sometimes|string|max:20',
            'email'        => 'sometimes|email|max:100',
            'pay_status'   => 'sometimes|string|in:pending,paid,cancelled,refund_pending,refunded',
            'confirm_status' => 'sometimes|string|in:pending,confirmed',
        ]);

        $data = $request->only([
            'name_cn', 'name_pinyin', 'nationality', 'id_card',
            'birthday', 'age_group', 'belt_color',
            'weight_gi', 'weight_nogi', 'gi_open', 'nogi_open',
            'team', 'phone', 'email',
            'pay_status', 'confirm_status',
        ]);

        // 处理性别
        if ($request->has('gender')) {
            $g = $request->input('gender');
            if (in_array($g, ['男', '女'], true)) {
                $data['gender'] = $g === '男' ? 1 : 2;
            } elseif (in_array((int) $g, [1, 2], true)) {
                $data['gender'] = (int) $g;
            }
        }

        $reg->update($data);

        Log::info('管理员修改报名记录', ['registration_id' => $id, 'data' => $data]);

        return $this->success($reg->fresh(), '修改成功');
    }

    // ─── 删除报名记录 ─────────────────────────────────────────

    /**
     * 删除报名记录
     * DELETE /api/admin/competition/registrations/{id}
     */
    public function registrationDestroy($id)
    {
        $reg = Registration::findOrFail($id);

        // 已支付的订单不允许直接删除，需先退款
        if (in_array($reg->pay_status, ['paid', 'refund_pending'], true)) {
            return $this->error('已支付或退款中的订单请先退款再删除', 422);
        }

        $reg->delete();

        Log::info('管理员删除报名记录', ['registration_id' => $id]);

        return $this->success(null, '删除成功');
    }
}
