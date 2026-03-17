<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    /**
     * 账单统计概览
     * GET /api/admin/bill/stats?start_date=&end_date=&site_id=
     */
    public function stats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId    = $request->input('site_id');

        // 基础查询：仅已支付订单
        $baseQuery = Registration::where('pay_status', 'paid');

        if ($startDate) $baseQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate)   $baseQuery->whereDate('created_at', '<=', $endDate);
        if ($siteId)    $baseQuery->where('site_id', $siteId);

        // 统计数据
        $totalAmount = (clone $baseQuery)->sum('amount');
        $totalCount  = (clone $baseQuery)->count();

        // 已退款统计
        $refundQuery = Registration::where('pay_status', 'refunded');
        if ($startDate) $refundQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate)   $refundQuery->whereDate('created_at', '<=', $endDate);
        if ($siteId)    $refundQuery->where('site_id', $siteId);
        $refundAmount = $refundQuery->sum('amount');
        $refundCount  = $refundQuery->count();

        // 待支付统计
        $pendingQuery = Registration::where('pay_status', 'pending');
        if ($startDate) $pendingQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate)   $pendingQuery->whereDate('created_at', '<=', $endDate);
        if ($siteId)    $pendingQuery->where('site_id', $siteId);
        $pendingAmount = $pendingQuery->sum('amount');
        $pendingCount  = $pendingQuery->count();

        // 按赛事站点分组统计（已支付）
        $siteStats = (clone $baseQuery)
            ->select('site_id', 'site_name', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('site_id', 'site_name')
            ->get();

        return $this->success([
            'paid_amount'    => (float) $totalAmount,
            'paid_count'     => $totalCount,
            'refund_amount'  => (float) $refundAmount,
            'refund_count'   => $refundCount,
            'pending_amount' => (float) $pendingAmount,
            'pending_count'  => $pendingCount,
            'net_income'     => (float) ($totalAmount - $refundAmount),
            'site_stats'     => $siteStats,
        ]);
    }

    /**
     * 订单明细列表
     * GET /api/admin/bill/orders?start_date=&end_date=&site_id=&pay_status=&page=&pageSize=
     */
    public function orders(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId    = $request->input('site_id');
        $payStatus = $request->input('pay_status');
        $pageSize  = min((int)($request->input('pageSize', 20)), 100);

        $query = Registration::orderByDesc('created_at');

        if ($startDate)  $query->whereDate('created_at', '>=', $startDate);
        if ($endDate)    $query->whereDate('created_at', '<=', $endDate);
        if ($siteId)     $query->where('site_id', $siteId);
        if ($payStatus)  $query->where('pay_status', $payStatus);

        $paginator = $query->paginate($pageSize);

        $items = collect($paginator->items())->map(fn($r) => [
            'id'             => $r->id,
            'order_no'       => $r->order_no,
            'site_name'      => $r->site_name ?: '-',
            'name_cn'        => $r->name_cn ?: '',
            'name_pinyin'    => $r->name_pinyin ?: '',
            'phone'          => $r->phone,
            'package_label'  => $r->package_label,
            'amount'         => $r->amount,
            'pay_status'     => $r->pay_status,
            'paid_at'        => $r->paid_at ? $r->paid_at->format('Y-m-d H:i:s') : '',
            'created_at'     => $r->created_at->format('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'data'         => $items,
            'total'        => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'last_page'    => $paginator->lastPage(),
        ]);
    }
}
