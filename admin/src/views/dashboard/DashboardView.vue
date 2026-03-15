<template>
  <div class="dashboard">
    <!-- 统计卡片 -->
    <el-row :gutter="20" v-loading="loading">
      <el-col :span="6" v-for="card in statCards" :key="card.label">
        <el-card class="stat-card" shadow="never">
          <div class="stat-content">
            <div class="stat-info">
              <p class="stat-label">{{ card.label }}</p>
              <p class="stat-value">{{ card.value }}</p>
              <p class="stat-sub" v-if="card.sub">{{ card.sub }}</p>
            </div>
            <div class="stat-icon" :style="{ background: card.color + '18', color: card.color }">
              <el-icon size="26"><component :is="card.icon" /></el-icon>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 年龄组报名分布 -->
    <el-card shadow="never" header="年龄组报名分布" class="mt-20">
      <div v-if="ageDistribution.length">
        <div
          v-for="item in ageDistribution"
          :key="item.age_group"
          class="age-item"
        >
          <div class="age-label">{{ item.age_group }}</div>
          <el-progress
            :percentage="agePercent(item.count)"
            :stroke-width="10"
            :show-text="false"
            color="#1677ff"
          />
          <div class="age-count">{{ item.count }}</div>
        </div>
      </div>
      <el-empty v-else description="暂无报名数据" :image-size="60" />
    </el-card>

    <!-- 最近报名记录 -->
    <el-card class="mt-20" shadow="never" header="最近报名记录">
      <el-table :data="recentRegs" stripe size="small">
        <el-table-column prop="name_cn" label="姓名" width="100" />
        <el-table-column prop="age_group" label="年龄组" width="120" />
        <el-table-column prop="belt_color" label="带色" width="80" />
        <el-table-column prop="team" label="战队" width="120" />
        <el-table-column prop="package_label" label="套餐" min-width="140" />
        <el-table-column prop="amount" label="金额" width="80">
          <template #default="{ row }">
            <span style="color:#e74c3c;font-weight:600">¥{{ row.amount }}</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="statusType(row.pay_status)" size="small">
              {{ statusLabel(row.pay_status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="报名时间" width="150" />
      </el-table>
      <div class="view-more">
        <el-button text type="primary" @click="$router.push('/competition/registrations')">
          查看全部报名记录 →
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { getRegistrationList } from '@/api/competition'

const loading = ref(false)
const stats = ref<any>({})
const ageDistribution = ref<{ age_group: string; count: number }[]>([])
const recentRegs = ref<any[]>([])

const statCards = computed(() => [
  {
    label: '报名总数',
    value: stats.value.total_regs ?? '--',
    sub: `今日新增 ${stats.value.today_regs ?? 0} 人`,
    icon: 'List',
    color: '#1677ff',
  },
  {
    label: '已支付订单',
    value: stats.value.paid_count ?? '--',
    sub: `待支付 ${stats.value.pending_count ?? 0} 单`,
    icon: 'Wallet',
    color: '#52c41a',
  },
  {
    label: '总收入',
    value: stats.value.total_income != null ? `¥${stats.value.total_income}` : '--',
    sub: '已支付订单合计',
    icon: 'Money',
    color: '#fa8c16',
  },
  {
    label: '注册用户',
    value: stats.value.total_users ?? '--',
    sub: `今日新增 ${stats.value.today_users ?? 0} 人`,
    icon: 'User',
    color: '#722ed1',
  },
])

const totalAge = computed(() => ageDistribution.value.reduce((s, i) => s + i.count, 0) || 1)
function agePercent(count: number) {
  return Math.round((count / totalAge.value) * 100)
}

function statusLabel(s: string) {
  return { pending: '待支付', paid: '已支付', cancelled: '已取消', refund_pending: '退款中', refunded: '已退款' }[s] || s
}
function statusType(s: string): any {
  return { pending: 'warning', paid: 'success', cancelled: 'info', refund_pending: 'warning', refunded: 'info' }[s] || ''
}

async function fetchStats() {
  loading.value = true
  try {
    const res: any = await request.get('/admin/dashboard/stats')
    stats.value = res.data || {}
    ageDistribution.value = res.data?.age_distribution || []
  } catch {
    ElMessage.error('获取统计数据失败')
  } finally {
    loading.value = false
  }
}

async function fetchRecentRegs() {
  try {
    const res: any = await getRegistrationList({ pageSize: 5 })
    recentRegs.value = res.data?.data || []
  } catch {
    // ignore
  }
}

onMounted(() => {
  fetchStats()
  fetchRecentRegs()
})
</script>

<style scoped>
.dashboard {}
.mt-20 { margin-top: 20px; }

/* 统计卡片 */
.stat-card { border-radius: 8px; }
.stat-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.stat-label { font-size: 13px; color: #999; margin: 0 0 6px; }
.stat-value { font-size: 26px; font-weight: 700; color: #1a1a1a; margin: 0 0 4px; }
.stat-sub { font-size: 12px; color: #bbb; margin: 0; }
.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* 年龄组分布 */
.age-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.age-label { font-size: 12px; color: #555; width: 90px; flex-shrink: 0; }
.age-count { font-size: 12px; color: #999; width: 24px; text-align: right; flex-shrink: 0; }

/* 最近报名 */
.view-more { text-align: right; margin-top: 12px; }
</style>
