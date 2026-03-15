<template>
  <view class="page">

    <!-- 加载中 -->
    <view v-if="loading" class="loading-wrap">
      <text class="loading-text">加载中...</text>
    </view>

    <!-- 加载失败 -->
    <view v-else-if="loadError" class="error-wrap">
      <text class="error-text">{{ loadError }}</text>
      <button class="retry-btn" @click="fetchDetail">重新加载</button>
    </view>

    <template v-else-if="order">
      <!-- ══ 状态栏 ══ -->
      <view class="status-bar" :class="statusBgClass(order.pay_status)">
        <text class="status-text">{{ statusLabel(order.pay_status) }}</text>
      </view>

      <!-- ══ 订单信息卡片 ══ -->
      <view class="card">
        <view class="card-title">报名信息</view>
        <view class="info-row">
          <text class="info-key">姓名</text>
          <text class="info-val">{{ order.name_cn || order.name_pinyin }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">年龄组 / 性别</text>
          <text class="info-val">{{ order.age_group }} / {{ order.gender }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">出生日期</text>
          <text class="info-val">{{ order.birthday || '-' }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">带色</text>
          <text class="info-val">{{ order.belt_color }}</text>
        </view>
        <view v-if="order.weight_gi" class="info-row">
          <text class="info-key">体重（道服）</text>
          <text class="info-val">{{ order.weight_gi }}</text>
        </view>
        <view v-if="order.weight_nogi" class="info-row">
          <text class="info-key">体重（无道服）</text>
          <text class="info-val">{{ order.weight_nogi }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">战队</text>
          <text class="info-val">{{ order.team }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">套餐</text>
          <text class="info-val">{{ order.package_label }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">联系电话</text>
          <text class="info-val">{{ order.phone || '-' }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">邮箱</text>
          <text class="info-val">{{ order.email || '-' }}</text>
        </view>
      </view>

      <!-- ══ 订单信息卡片 ══ -->
      <view class="card">
        <view class="card-title">订单信息</view>
        <view class="info-row">
          <text class="info-key">订单号</text>
          <text class="info-val">{{ order.order_no }}</text>
        </view>
        <view class="info-row">
          <text class="info-key">下单时间</text>
          <text class="info-val">{{ order.created_at }}</text>
        </view>
        <view v-if="order.paid_at" class="info-row">
          <text class="info-key">支付时间</text>
          <text class="info-val">{{ order.paid_at }}</text>
        </view>
        <view class="info-row amount-row">
          <text class="info-key">支付金额</text>
          <text class="amount-val">¥{{ order.amount }}</text>
        </view>
        <!-- 已支付：申请退款按钮 -->
        <view v-if="order.pay_status === 'paid'" class="card-action-row">
          <button class="card-refund-btn" @click="handleRefund">申请退款</button>
        </view>
      </view>

      <!-- ══ 底部操作按钮（待支付时显示） ══ -->
      <view v-if="order.pay_status === 'pending'" class="action-bar">
        <button class="action-btn cancel-btn" @click="handleCancel">取消订单</button>
        <button class="action-btn pay-btn" @click="goRepay">继续支付</button>
      </view>

    </template>

  </view>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getOrderDetail, createPayOrder, queryPayResult, cancelOrder, requestRefund } from '@/api/competition'

const loading   = ref(true)
const order     = ref(null)
const loadError = ref('')
let orderId     = null

onLoad((query) => {
  orderId = query?.id
  if (!orderId) {
    uni.showToast({ title: '订单不存在', icon: 'none' })
    setTimeout(() => uni.navigateBack(), 500)
    return
  }
  fetchDetail()
})

async function fetchDetail() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await getOrderDetail(orderId)
    order.value = res.data
  } catch (e) {
    console.error('订单详情加载失败', e)
    loadError.value = e?.message || '加载失败，请重试'
  } finally {
    loading.value = false
  }
}

// ── 继续支付 ─────────────────────────────────────────────────
async function goRepay() {
  try {
    const payRes = await createPayOrder({ order_id: order.value.id })
    const payParams = payRes.data

    await new Promise((resolve, reject) => {
      uni.requestPayment({
        provider: 'wxpay',
        timeStamp: payParams.timeStamp,
        nonceStr: payParams.nonceStr,
        package: payParams.package,
        signType: payParams.signType,
        paySign: payParams.paySign,
        success: resolve,
        fail: reject,
      })
    })

    await queryPayResult(order.value.id)
    uni.showToast({ title: '支付成功！', icon: 'success' })
    setTimeout(() => fetchDetail(), 1500)
  } catch (e) {
    if (e?.errMsg === 'requestPayment:fail cancel') {
      uni.showToast({ title: '已取消支付', icon: 'none' })
    }
  }
}

// ── 申请退款 ────────────────────────────────────────────────
function handleRefund() {
  uni.showModal({
    title: '申请退款',
    content: '确定要申请退款吗？提交后请等待管理员处理。',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await requestRefund({ order_id: order.value.id })
        uni.showToast({ title: '退款申请已提交', icon: 'success' })
        setTimeout(() => fetchDetail(), 800)
      } catch (e) {
        uni.showToast({ title: e?.message || '申请失败', icon: 'none' })
      }
    }
  })
}

// ── 取消订单 ────────────────────────────────────────────────
function handleCancel() {
  uni.showModal({
    title: '提示',
    content: '确定要取消该订单吗？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await cancelOrder({ order_id: order.value.id })
        uni.showToast({ title: '订单已取消', icon: 'success' })
        setTimeout(() => fetchDetail(), 800)
      } catch (e) {
        uni.showToast({ title: e?.message || '取消失败', icon: 'none' })
      }
    }
  })
}

// ── 辅助 ────────────────────────────────────────────────────
function statusLabel(s) {
  return { pending: '待支付', paid: '已支付', cancelled: '已取消', refund_pending: '退款中', refunded: '已退款' }[s] || s
}
function statusDesc(s) {
  return {
    pending: '请尽快完成支付',
    paid: '报名信息已确认',
    cancelled: '订单已取消',
    refund_pending: '退款申请已提交，请等待处理',
    refunded: '退款已完成',
  }[s] || ''
}
function statusBgClass(s) {
  return {
    pending: 'bg-pending',
    paid: 'bg-paid',
    cancelled: 'bg-cancel',
    refund_pending: 'bg-refund-pending',
    refunded: 'bg-refund',
  }[s] || ''
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 60rpx;
}

/* ══ 加载中 ══ */
.loading-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 200rpx 0;
}
.loading-text { font-size: 28rpx; color: #bbb; }

/* ══ 加载失败 ══ */
.error-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 200rpx 40rpx;
}
.error-text {
  font-size: 28rpx;
  color: #999;
  margin-bottom: 40rpx;
  text-align: center;
}
.retry-btn {
  width: 240rpx;
  height: 76rpx;
  line-height: 76rpx;
  background: #e74c3c;
  color: #fff;
  border-radius: 38rpx;
  font-size: 28rpx;
  border: none;
}

/* ══ 状态栏 ══ */
.status-bar {
  padding: 40rpx 36rpx;
}
.status-text {
  display: block;
  font-size: 38rpx;
  font-weight: bold;
  color: #fff;
}
.bg-pending       { background: linear-gradient(135deg, #fa8c16, #f5a623); }
.bg-paid          { background: linear-gradient(135deg, #52c41a, #73d13d); }
.bg-cancel        { background: linear-gradient(135deg, #999, #bbb); }
.bg-refund-pending { background: linear-gradient(135deg, #fa541c, #ff7a45); }
.bg-refund        { background: linear-gradient(135deg, #e74c3c, #f5222d); }

/* ══ 卡片 ══ */
.card {
  background: #fff;
  margin: 20rpx 24rpx 0;
  border-radius: 16rpx;
  padding: 28rpx;
  box-shadow: 0 2rpx 8rpx rgba(0,0,0,.04);
}
.card-title {
  font-size: 28rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 20rpx;
  padding-bottom: 16rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #fafafa;
  &:last-child { border-bottom: none; }
}
.info-key {
  font-size: 26rpx;
  color: #999;
  flex-shrink: 0;
}
.info-val {
  font-size: 26rpx;
  color: #333;
  font-weight: 500;
  text-align: right;
  max-width: 60%;
  word-break: break-all;
}
.amount-row {
  padding: 20rpx 0 8rpx;
}
.amount-val {
  font-size: 36rpx;
  font-weight: bold;
  color: #e74c3c;
}

/* ══ 卡片内操作行（申请退款） ══ */
.card-action-row {
  display: flex;
  justify-content: flex-end;
  padding-top: 20rpx;
  margin-top: 12rpx;
  border-top: 1rpx solid #f0f0f0;
}
.card-refund-btn {
  height: 64rpx;
  line-height: 64rpx;
  border-radius: 32rpx;
  font-size: 26rpx;
  text-align: center;
  padding: 0 36rpx;
  margin: 0;
  background: #fff;
  color: #fa541c;
  border: 2rpx solid #fa541c;
}

/* ══ 底部操作栏（待支付时显示） ══ */
.action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 20rpx 36rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  display: flex;
  justify-content: flex-end;
  gap: 20rpx;
  border-top: 1rpx solid #f0f0f0;
  z-index: 100;
}
.action-btn {
  height: 76rpx;
  line-height: 76rpx;
  border-radius: 38rpx;
  font-size: 28rpx;
  text-align: center;
  margin: 0;
  padding: 0 40rpx;
}
.cancel-btn {
  background: #fff;
  color: #999;
  border: 2rpx solid #ddd;
}
.pay-btn {
  background: #e74c3c;
  color: #fff;
  border: none;
}
</style>
