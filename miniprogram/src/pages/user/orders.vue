<template>
  <view class="page">

    <!-- ══ 状态 tab ══════════════════════════════════════════════ -->
    <view class="tab-bar">
      <view
        v-for="tab in tabs"
        :key="tab.key"
        class="tab-item"
        :class="{ active: currentTab === tab.key }"
        @click="switchTab(tab.key)"
      >
        <text class="tab-text">{{ tab.label }}</text>
      </view>
    </view>

    <!-- ══ 订单列表 ══════════════════════════════════════════════ -->
    <view v-if="loading && orders.length === 0" class="loading-wrap">
      <text class="loading-text">加载中...</text>
    </view>

    <view v-else-if="!loading && orders.length === 0" class="empty-wrap">
      <text class="empty-text">暂无相关订单</text>
      <button class="go-btn" @click="goHome">去报名</button>
    </view>

    <view v-else class="order-list">
      <view v-for="order in orders" :key="order.id" class="order-card" @click="goOrderDetail(order)">
        <!-- 第一行：订单号 + 状态 -->
        <view class="order-header">
          <text class="order-no">订单号: {{ order.order_no || order.id }}</text>
          <text class="order-status" :class="statusClass(order.pay_status)">
            {{ statusLabel(order.pay_status) }}
          </text>
        </view>
        <!-- 第二行：下单时间 -->
        <view class="order-row">
          <text class="order-label">下单时间</text>
          <text class="order-val">{{ order.created_at }}</text>
        </view>
        <!-- 第三行：订单金额 -->
        <view class="order-row">
          <text class="order-label">订单金额</text>
          <text class="order-price">¥{{ order.amount }}</text>
        </view>
      </view>
    </view>

    <!-- ══ 加载更多 ══════════════════════════════════════════════ -->
    <view v-if="hasMore" class="load-more-wrap">
      <button class="load-more-btn" :loading="loadingMore" @click="loadMore">
        {{ loadingMore ? '加载中...' : '加载更多' }}
      </button>
    </view>
    <view v-else-if="orders.length > 0" class="no-more-wrap">
      <text class="no-more-text">已全部加载</text>
    </view>

  </view>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getMyOrders } from '@/api/competition'

const PER_PAGE = 10

const tabs = [
  { key: 'all',        label: '全部' },
  { key: 'pending',    label: '待付款' },
  { key: 'paid',       label: '已支付' },
  { key: 'cancelled',  label: '已取消' },
  { key: 'after_sale', label: '售后' },
]

const currentTab  = ref('all')
const orders      = ref([])
const loading     = ref(false)
const loadingMore = ref(false)
const hasMore     = ref(false)
const currentPage = ref(1)

// ── 接收 status 参数（从「我的」页跳转传来）──────────────────────
onLoad((query) => {
  if (query?.status && tabs.some(t => t.key === query.status)) {
    currentTab.value = query.status
  }
  fetchOrders(true)
})

// ── 从详情页返回时刷新列表 ─────────────────────────────────────
onShow(() => {
  fetchOrders(true)
})

// ── 切换 Tab ─────────────────────────────────────────────────
function switchTab(key) {
  if (currentTab.value === key) return
  currentTab.value = key
  fetchOrders(true)
}

// ── 拉取订单 ─────────────────────────────────────────────────
async function fetchOrders(reset = false) {
  if (reset) {
    currentPage.value = 1
    orders.value = []
    hasMore.value = false
  }

  const isFirstLoad = currentPage.value === 1
  if (isFirstLoad) loading.value = true
  else loadingMore.value = true

  try {
    const res = await getMyOrders({
      status:   currentTab.value,
      page:     currentPage.value,
      per_page: PER_PAGE,
    })
    const data = res.data || {}
    const newList = data.list || []

    if (reset) {
      orders.value = newList
    } else {
      orders.value = [...orders.value, ...newList]
    }

    hasMore.value = !!data.has_more
  } catch (e) {
    console.error('订单加载失败', e)
  } finally {
    loading.value     = false
    loadingMore.value = false
  }
}

// ── 加载更多 ─────────────────────────────────────────────────
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  currentPage.value++
  fetchOrders(false)
}

// ── 跳转 ─────────────────────────────────────────────────────
function goHome() {
  uni.switchTab({ url: '/pages/index/index' })
}

function goOrderDetail(order) {
  uni.navigateTo({ url: `/pages/user/order-detail?id=${order.id}` })
}

// ── 辅助 ─────────────────────────────────────────────────────
function statusLabel(s) {
  return { pending: '待支付', paid: '已支付', cancelled: '已取消', refund_pending: '退款中', refunded: '已退款' }[s] || s
}
function statusClass(s) {
  return { pending: 'status-pending', paid: 'status-paid', cancelled: 'status-cancel', refund_pending: 'status-refund-pending', refunded: 'status-refund' }[s] || ''
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 60rpx;
}

/* ══ Tab 栏 ══ */
.tab-bar {
  display: flex;
  background: #fff;
  border-bottom: 1rpx solid #f0f0f0;
  position: sticky;
  top: 0;
  z-index: 10;
}
.tab-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28rpx 0;
  position: relative;
}
.tab-text {
  font-size: 28rpx;
  color: #888;
}
.tab-item.active .tab-text {
  color: #e74c3c;
  font-weight: bold;
}
.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 25%;
  width: 50%;
  height: 4rpx;
  background: #e74c3c;
  border-radius: 2rpx;
}

/* ══ 空状态 / 加载中 ══ */
.loading-wrap, .empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 100rpx 0;
}
.loading-text { font-size: 28rpx; color: #bbb; }
.empty-text   { font-size: 28rpx; color: #bbb; margin-bottom: 48rpx; }
.go-btn {
  width: 240rpx;
  height: 76rpx;
  line-height: 76rpx;
  background: #e74c3c;
  color: #fff;
  border-radius: 38rpx;
  font-size: 28rpx;
  border: none;
}

/* ══ 订单卡片 ══ */
.order-list  { padding: 20rpx 24rpx 0; }
.order-card  {
  background: #fff;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  padding: 28rpx;
  box-shadow: 0 2rpx 10rpx rgba(0,0,0,.05);
}
.order-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 20rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.order-no     { font-size: 26rpx; color: #333; font-weight: 500; }
.order-status { font-size: 24rpx; font-weight: bold; }
.status-pending { color: #fa8c16; }
.status-paid    { color: #52c41a; }
.status-cancel  { color: #bbb;    }
.status-refund-pending { color: #fa541c; }
.status-refund  { color: #e74c3c; }

.order-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
  &:last-child { border-bottom: none; }
}
.order-label { font-size: 24rpx; color: #999; }
.order-val   { font-size: 24rpx; color: #333; }
.order-price { font-size: 28rpx; font-weight: bold; color: #e74c3c; }

/* ══ 加载更多 ══ */
.load-more-wrap {
  padding: 32rpx 48rpx;
}
.load-more-btn {
  width: 100%;
  height: 80rpx;
  line-height: 80rpx;
  background: #fff;
  color: #666;
  border-radius: 40rpx;
  font-size: 28rpx;
  border: 2rpx solid #e0e0e0;
  box-shadow: none;
}
.no-more-wrap {
  padding: 32rpx 0;
  display: flex;
  justify-content: center;
}
.no-more-text { font-size: 24rpx; color: #ccc; }
</style>
