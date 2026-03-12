<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * 仪表盘统计数据
     * GET /api/admin/dashboard/stats
     */
    public function stats()
    {
        $today = Carbon::today()->toDateString();

        // 用户总数
        $totalUsers = User::count();

        // 今日新增用户
        $todayUsers = User::whereDate('created_at', $today)->count();

        // 报名总数
        $totalRegs = Registration::count();

        // 今日新增报名
        $todayRegs = Registration::whereDate('created_at', $today)->count();

        // 已支付订单数
        $paidCount = Registration::where('pay_status', 'paid')->count();

        // 总收入（已支付）
        $totalIncome = Registration::where('pay_status', 'paid')->sum('amount');

        // 待支付订单数
        $pendingCount = Registration::where('pay_status', 'pending')->count();

        // 最近7天报名趋势
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $trend[] = [
                'date'  => $date,
                'count' => Registration::whereDate('created_at', $date)->count(),
            ];
        }

        // 年龄组分布
        $ageDistribution = Registration::select('age_group', DB::raw('count(*) as count'))
            ->groupBy('age_group')
            ->get();

        return $this->success([
            'total_users'   => $totalUsers,
            'today_users'   => $todayUsers,
            'total_regs'    => $totalRegs,
            'today_regs'    => $todayRegs,
            'paid_count'    => $paidCount,
            'pending_count' => $pendingCount,
            'total_income'  => (float) $totalIncome,
            'trend'         => $trend,
            'age_distribution' => $ageDistribution,
        ]);
    }
}
