<template>
  <div class="bill-page">
    <!-- ═══ 筛选区 ═══ -->
    <el-card shadow="never" class="filter-card">
      <el-form :inline="true" :model="filter">
        <el-form-item label="赛事站点">
          <el-select v-model="filter.site_id" placeholder="所有站点" clearable style="width:180px" @change="fetchAll">
            <el-option
              v-for="s in siteOptions"
              :key="s.id"
              :label="s.label"
              :value="s.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="filter.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width:280px"
            @change="fetchAll"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchAll">查询</el-button>
          <el-button @click="resetFilter">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- ═══ 统计卡片（仅已支付金额） ═══ -->
    <el-card shadow="never" class="stat-card paid stats-full-card">
      <div class="stat-label">已支付金额</div>
      <div class="stat-value">¥{{ formatMoney(stats.paid_amount) }}</div>
      <div class="stat-sub">共 {{ stats.paid_count }} 笔</div>
    </el-card>

    <!-- ═══ 按站点分组统计 ═══ -->
    <el-card shadow="never" class="mt-16" v-if="stats.site_stats && stats.site_stats.length">
      <template #header><span style="font-weight:600">按赛事站点统计（已支付）</span></template>
      <el-table :data="stats.site_stats" stripe border>
        <el-table-column prop="site_name" label="赛事站点" min-width="180">
          <template #default="{ row }">{{ row.site_name || '未指定' }}</template>
        </el-table-column>
        <el-table-column prop="order_count" label="订单数" width="120" align="center" />
        <el-table-column prop="total_amount" label="总金额" width="160" align="right">
          <template #default="{ row }">¥{{ formatMoney(row.total_amount) }}</template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- ═══ 订单明细 ═══ -->
    <el-card shadow="never" class="mt-16">
      <template #header>
        <div style="display:flex;align-items:center;justify-content:space-between">
          <span style="font-weight:600">订单明细</span>
          <el-select v-model="orderFilter.pay_status" placeholder="全部状态" clearable style="width:140px" @change="fetchOrders(true)">
            <el-option label="已支付" value="paid" />
            <el-option label="待支付" value="pending" />
            <el-option label="已取消" value="cancelled" />
            <el-option label="已退款" value="refunded" />
          </el-select>
        </div>
      </template>

      <el-table :data="orderList" v-loading="orderLoading" stripe border>
        <el-table-column prop="order_no" label="订单号" min-width="160" />
        <el-table-column prop="site_name" label="赛事站点" width="140" />
        <el-table-column label="姓名" min-width="140">
          <template #default="{ row }">{{ row.name_cn || row.name_pinyin || '-' }}</template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="130" />
        <el-table-column prop="package_label" label="套餐" width="120" />
        <el-table-column prop="amount" label="金额" width="100" align="right">
          <template #default="{ row }">¥{{ formatMoney(row.amount) }}</template>
        </el-table-column>
        <el-table-column prop="pay_status" label="支付状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="payStatusType(row.pay_status)" size="small">{{ payStatusLabel(row.pay_status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="paid_at" label="支付时间" width="170" />
        <el-table-column prop="created_at" label="创建时间" width="170" />
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="orderFilter.page"
          v-model:page-size="orderFilter.pageSize"
          :total="orderTotal"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchOrders(true)"
          @current-change="fetchOrders(false)"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { getBillStats, getBillOrders } from '@/api/competition'
import { getDictItems } from '@/api/dict'

// ═══ 赛事站点选项 ═══
const siteOptions = ref<any[]>([])
async function fetchSites() {
  try {
    const res: any = await getDictItems('competition_site')
    siteOptions.value = res.data?.data || res.data || []
  } catch { /* ignore */ }
}

// ═══ 筛选条件 ═══
const filter = reactive({
  site_id: '' as any,
  dateRange: null as string[] | null,
})

function resetFilter() {
  filter.site_id = ''
  filter.dateRange = null
  fetchAll()
}

function buildParams() {
  const params: any = {}
  if (filter.site_id) params.site_id = filter.site_id
  if (filter.dateRange && filter.dateRange.length === 2) {
    params.start_date = filter.dateRange[0]
    params.end_date = filter.dateRange[1]
  }
  return params
}

// ═══ 统计数据 ═══
const stats = ref<any>({
  paid_amount: 0, paid_count: 0,
  refund_amount: 0, refund_count: 0,
  pending_amount: 0, pending_count: 0,
  net_income: 0,
  site_stats: [],
})

async function fetchStats() {
  try {
    const res: any = await getBillStats(buildParams())
    stats.value = res.data || stats.value
  } catch (e) {
    console.error('加载账单统计失败', e)
  }
}

// ═══ 订单明细 ═══
const orderLoading = ref(false)
const orderList = ref<any[]>([])
const orderTotal = ref(0)
const orderFilter = reactive({
  page: 1,
  pageSize: 20,
  pay_status: '',
})

async function fetchOrders(reset = false) {
  if (reset) orderFilter.page = 1
  orderLoading.value = true
  try {
    const params: any = {
      ...buildParams(),
      page: orderFilter.page,
      pageSize: orderFilter.pageSize,
    }
    if (orderFilter.pay_status) params.pay_status = orderFilter.pay_status
    const res: any = await getBillOrders(params)
    orderList.value = res.data?.data || []
    orderTotal.value = res.data?.total || 0
  } catch (e) {
    console.error('加载订单明细失败', e)
  } finally {
    orderLoading.value = false
  }
}

// ═══ 一起刷新 ═══
function fetchAll() {
  fetchStats()
  fetchOrders(true)
}

onMounted(() => {
  fetchSites()
  fetchAll()
})

// ═══ 工具函数 ═══
function formatMoney(v: any) {
  const n = Number(v) || 0
  return n.toFixed(2)
}

function payStatusLabel(s: string) {
  return ({ pending: '待支付', paid: '已支付', cancelled: '已取消', refund_pending: '退款中', refunded: '已退款' } as any)[s] || s
}
function payStatusType(s: string) {
  return ({ pending: 'warning', paid: 'success', cancelled: 'info', refund_pending: 'danger', refunded: 'danger' } as any)[s] || ''
}
</script>

<style scoped>
.bill-page {
  padding: 0;
}
.filter-card {
  margin-bottom: 16px;
}
.stats-full-card {
  margin-bottom: 16px;
  width: 100%;
}
.stat-card {
  text-align: center;
  padding: 8px 0;
}
.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}
.stat-value {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 4px;
}
.stat-sub {
  font-size: 12px;
  color: #c0c4cc;
}
.stat-card.paid .stat-value { color: #67c23a; }
.stat-card.refund .stat-value { color: #f56c6c; }
.stat-card.net .stat-value { color: #409eff; }
.stat-card.pending .stat-value { color: #e6a23c; }
.mt-16 {
  margin-top: 16px;
}
.pagination-wrap {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
