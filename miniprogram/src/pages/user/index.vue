<template>
  <view class="page">

    <!-- ══ 顶部黑色头部 ══════════════════════════════════════════ -->
    <view class="header" :style="{ paddingTop: statusBarHeight + 'px' }">

      <!-- 导航栏：标题居中，设置图标右侧 -->
      <view class="nav-bar">
        <text class="nav-title">用户中心</text>
        <image
          class="nav-setting"
          src="/static/icon-seeting.png"
          mode="aspectFit"
          @click="goProfile"
        />
      </view>

      <!-- 用户信息行：头像左，昵称右，垂直居中 -->
      <view class="user-row">
        <button
          class="avatar-btn"
          open-type="chooseAvatar"
          hover-class="none"
          :disabled="avatarUploading"
          @chooseavatar="onChooseAvatar"
        >
          <!-- 上传中显示 loading 遮罩 -->
          <view v-if="avatarUploading" class="avatar-loading">
            <text class="avatar-loading-text">上传中</text>
          </view>
          <image v-else class="user-avatar" :src="avatarSrc" mode="aspectFill" />
        </button>
        <view class="user-meta">
          <text class="user-name">{{ userStore.userInfo?.nickname || '用户' }}</text>
        </view>
      </view>
    </view>

    <!-- ══ 公告栏：循环滚动 ══════════════════════════════════════ -->
    <view v-if="notice" class="notice-bar">
      <image class="notice-img" src="/static/icon-notice.png" mode="aspectFit" />
      <view class="notice-scroll-wrap">
        <view class="notice-track" :style="noticeTrackStyle">
          <text class="notice-text">{{ notice }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ notice }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</text>
        </view>
      </view>
      <text class="notice-arrow">›</text>
    </view>

    <!-- ══ 个人资料入口（订单之上）══════════════════════════════ -->
    <view class="section-card profile-entry" @click="goProfile">
      <image class="entry-img" src="/static/icon-seeting.png" mode="aspectFit" />
      <text class="entry-label">个人资料</text>
      <text class="entry-arrow">›</text>
    </view>

    <!-- ══ 我的订单（内嵌列表）══════════════════════════════════ -->
    <view class="section-card order-section">
      <view class="section-header">
        <text class="section-title">我的订单</text>
      </view>

      <!-- 状态 Tab：全部排第一 -->
      <view class="order-tabs">
        <view
          v-for="tab in orderTabs"
          :key="tab.key"
          class="order-tab"
          :class="{ active: currentTab === tab.key }"
          @click="switchTab(tab.key)"
        >
          <text class="tab-label">{{ tab.label }}</text>
        </view>
      </view>

      <!-- 订单列表区域 -->
      <view v-if="ordersLoading && orders.length === 0" class="order-loading">
        <text class="order-loading-text">加载中...</text>
      </view>

      <view v-else-if="!ordersLoading && orders.length === 0" class="order-empty">

        <text class="empty-text">暂无相关订单</text>
        <button class="go-register-btn" @click="goHome">去报名</button>
      </view>

      <view v-else class="order-list">
        <view v-for="order in orders" :key="order.id" class="order-card">
          <!-- 订单头 -->
          <view class="order-header">
            <text class="order-no">订单 {{ order.order_no || order.id }}</text>
            <text class="order-status" :class="statusClass(order.pay_status)">
              {{ statusLabel(order.pay_status) }}
            </text>
          </view>

          <!-- 订单信息 -->
          <view class="order-body">
            <view class="info-row">
              <text class="info-key">姓名</text>
              <text class="info-val">{{ order.name_cn || order.name_pinyin }}</text>
            </view>
            <view class="info-row">
              <text class="info-key">年龄组 / 性别</text>
              <text class="info-val">{{ order.age_group }} / {{ order.gender }}</text>
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
          </view>

          <!-- 订单底部：支付金额 -->
          <view class="order-footer">
            <text class="order-amount-label">支付金额</text>
            <text class="order-price">¥{{ order.amount }}</text>
          </view>

          <!-- 待支付操作按钮（右对齐） -->
          <view v-if="order.pay_status === 'pending'" class="repay-wrap">
            <button class="cancel-btn" @click="handleCancel(order)">取消订单</button>
            <button class="repay-btn" @click="goRepay(order)">继续支付</button>
          </view>
        </view>
      </view>

      <!-- 加载更多 -->
      <view v-if="hasMore" class="load-more-wrap">
        <button class="load-more-btn" :loading="loadingMore" @click="loadMore">
          {{ loadingMore ? '加载中...' : '加载更多' }}
        </button>
      </view>
      <view v-else-if="orders.length > 0" class="no-more-wrap">
        <text class="no-more-text">已全部加载</text>
      </view>
    </view>

  </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user'
import { get, post } from '@/utils/request'
import { getMyOrders, createPayOrder, queryPayResult, cancelOrder } from '@/api/competition'

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const userStore       = useUserStore()
const statusBarHeight = ref(uni.getSystemInfoSync().statusBarHeight || 20)
const notice          = ref('')
const avatarUploading = ref(false)

// 公告滚动动画时长（字数×0.3s，最短8s）
const noticeTrackStyle = computed(() => {
  const duration = Math.max(8, notice.value.length * 0.3)
  return { animationDuration: `${duration}s` }
})

const DEFAULT_AVATAR = 'https://mmbiz.qpic.cn/mmbiz/icTdbqWNOwNRna42FI242Lcia07jQodd2FJGIYQfG0LAJGFxM4FbnQP6yfMxBgJ0F3YRqJCJ1aPAK2dQagdusBZg/0'
const avatarSrc = computed(() => {
  const av = userStore.userInfo?.avatar
  return (av && av.trim()) ? av : DEFAULT_AVATAR
})

// ── 订单相关 ─────────────────────────────────────────────────
const PER_PAGE = 10
const orderTabs = [
  { key: 'all',       label: '全部' },
  { key: 'pending',   label: '待付款' },
  { key: 'paid',      label: '已支付' },
  { key: 'cancelled', label: '已取消' },
  { key: 'refunded',  label: '已退款' },
]
const currentTab   = ref('all')
const orders       = ref([])
const ordersLoading = ref(false)
const loadingMore  = ref(false)
const hasMore      = ref(false)
const currentPage  = ref(1)

// ── 页面可见时刷新 ───────────────────────────────────────────
onShow(async () => {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/login/index' })
    return
  }
  // 刷新用户信息（确保 gender/birthday 等字段不丢失）
  try {
    const userRes = await get('/user/info')
    if (userRes.data) userStore.setUserInfo(userRes.data)
  } catch { /* ignore */ }
  fetchNotice()
  fetchOrders(true)
})

// ── 拉取最新公告 ──────────────────────────────────────────────
async function fetchNotice() {
  try {
    const res = await get('/announcement/latest')
    notice.value = res.data?.content || ''
  } catch { /* 不影响页面 */ }
}

// ── 切换 Tab ────────────────────────────────────────────────
function switchTab(key) {
  if (currentTab.value === key) return
  currentTab.value = key
  fetchOrders(true)
}

// ── 拉取订单列表 ─────────────────────────────────────────────
async function fetchOrders(reset = false) {
  if (reset) {
    currentPage.value = 1
    orders.value = []
    hasMore.value = false
  }

  const isFirstLoad = currentPage.value === 1
  if (isFirstLoad) ordersLoading.value = true
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
    ordersLoading.value = false
    loadingMore.value   = false
  }
}

// ── 加载更多 ─────────────────────────────────────────────────
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  currentPage.value++
  fetchOrders(false)
}

// ── 继续支付 ─────────────────────────────────────────────────
async function goRepay(order) {
  try {
    const payRes = await createPayOrder({
      order_id: order.id,
    })
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

    await queryPayResult(order.id)
    uni.showToast({ title: '支付成功！', icon: 'success' })
    setTimeout(() => fetchOrders(true), 1500)

  } catch (e) {
    if (e?.errMsg === 'requestPayment:fail cancel') {
      uni.showToast({ title: '已取消支付', icon: 'none' })
    }
  }
}

// ── 取消订单 ────────────────────────────────────────────────
function handleCancel(order) {
  uni.showModal({
    title: '提示',
    content: '确定要取消该订单吗？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await cancelOrder({ order_id: order.id })
        uni.showToast({ title: '订单已取消', icon: 'success' })
        setTimeout(() => fetchOrders(true), 800)
      } catch (e) {
        uni.showToast({ title: e?.message || '取消失败', icon: 'none' })
      }
    }
  })
}

// ── 换头像 ──────────────────────────────────────────────────
async function onChooseAvatar(e) {
  const tempPath = e.detail.avatarUrl
  if (!tempPath) return

  avatarUploading.value = true
  try {
    const permanentUrl = await uploadAvatarFile(tempPath)
    await post('/user/info', { avatar: permanentUrl })
    userStore.setUserInfo({ ...userStore.userInfo, avatar: permanentUrl })
    uni.showToast({ title: '头像已更新', icon: 'success' })
  } catch (err) {
    console.error('头像上传失败', err)
    uni.showToast({ title: err?.message || '上传失败，请重试', icon: 'none' })
  } finally {
    avatarUploading.value = false
  }
}

function uploadAvatarFile(filePath) {
  return new Promise((resolve, reject) => {
    uni.uploadFile({
      url: BASE_URL + '/upload/avatar',
      filePath,
      name: 'file',
      header: {
        Authorization: userStore.token ? `Bearer ${userStore.token}` : '',
      },
      success(res) {
        try {
          const data = typeof res.data === 'string' ? JSON.parse(res.data) : res.data
          if (data?.data?.url) {
            resolve(data.data.url)
          } else {
            reject(new Error(data?.message || '上传失败'))
          }
        } catch {
          reject(new Error('响应解析失败'))
        }
      },
      fail() {
        reject(new Error('网络错误，上传失败'))
      }
    })
  })
}

// ── 跳转 ────────────────────────────────────────────────────
function goProfile() {
  uni.navigateTo({ url: '/pages/user/profile' })
}
function goHome() {
  uni.switchTab({ url: '/pages/index/index' })
}

// ── 辅助 ────────────────────────────────────────────────────
function statusLabel(s) {
  return { pending: '待支付', paid: '已支付', cancelled: '已取消', refunded: '已退款' }[s] || s
}
function statusClass(s) {
  return { pending: 'status-pending', paid: 'status-paid', cancelled: 'status-cancel', refunded: 'status-refund' }[s] || ''
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40rpx;
}

/* ══ 顶部背景图 ══ */
.header {
  background: url('https://copade.net.cn/header.jpg') center/cover no-repeat;
  padding-bottom: 40rpx;
}

/* 导航栏 */
.nav-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 88rpx;
  padding: 0 32rpx;
  position: relative;
}
.nav-title {
  font-size: 34rpx;
  font-weight: bold;
  color: #fff;
  letter-spacing: 2rpx;
}
.nav-setting {
  position: absolute;
  right: 32rpx;
  top: 50%;
  transform: translateY(-50%);
  width: 48rpx;
  height: 48rpx;
}

/* 用户信息行 */
.user-row {
  display: flex;
  align-items: center;
  padding: 24rpx 36rpx 8rpx;
  gap: 28rpx;
}
.avatar-btn {
  width: 110rpx;
  height: 110rpx;
  border-radius: 50%;
  padding: 0;
  margin: 0;
  border: 3rpx solid rgba(255,255,255,0.5);
  background: transparent;
  overflow: hidden;
  flex-shrink: 0;
  line-height: 1;
  &::after { border: none; }
}
.user-avatar {
  width: 110rpx;
  height: 110rpx;
  border-radius: 50%;
  display: block;
}
/* 上传中遮罩 */
.avatar-loading {
  width: 110rpx;
  height: 110rpx;
  border-radius: 50%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}
.avatar-loading-text {
  font-size: 20rpx;
  color: #fff;
}
.user-meta {
  display: flex;
  flex-direction: column;
  gap: 10rpx;
  justify-content: center;
}
.user-name {
  font-size: 34rpx;
  font-weight: bold;
  color: #fff;
}

/* ══ 公告滚动栏 ══ */
.notice-bar {
  display: flex;
  align-items: center;
  background: #fff5f5;
  margin: 20rpx 24rpx;
  border-radius: 10rpx;
  padding: 20rpx 24rpx;
  gap: 16rpx;
}
.notice-img {
  width: 36rpx;
  height: 36rpx;
  flex-shrink: 0;
}
.notice-scroll-wrap {
  flex: 1;
  overflow: hidden;
  height: 40rpx;
  display: flex;
  align-items: center;
}
.notice-track {
  display: inline-flex;
  white-space: nowrap;
  animation: marquee-scroll linear infinite;
}
.notice-text {
  font-size: 26rpx;
  color: #e74c3c;
  white-space: nowrap;
}
@keyframes marquee-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
.notice-arrow { font-size: 32rpx; color: #e74c3c; flex-shrink: 0; }

/* ══ 通用卡片 ══ */
.section-card {
  background: #fff;
  margin: 0 24rpx 20rpx;
  border-radius: 16rpx;
  overflow: hidden;
  box-shadow: 0 2rpx 8rpx rgba(0,0,0,.05);
}
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 28rpx 20rpx;
  border-bottom: 1rpx solid #f5f5f5;
}
.section-title {
  font-size: 30rpx;
  font-weight: bold;
  color: #222;
}

/* ══ 个人资料入口 ══ */
.profile-entry {
  display: flex;
  align-items: center;
  padding: 28rpx 28rpx;
  gap: 20rpx;
}
.entry-img {
  width: 40rpx;
  height: 40rpx;
}
.entry-label { flex: 1; font-size: 30rpx; color: #333; }
.entry-arrow { font-size: 36rpx; color: #ccc; }

/* ══ 订单 Tab ══ */
.order-section {
  margin-bottom: 0;
}
.order-tabs {
  display: flex;
  background: #fff;
  border-bottom: 1rpx solid #f0f0f0;
}
.order-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 26rpx 0;
  position: relative;
}
.tab-label {
  font-size: 28rpx;
  color: #888;
}
.order-tab.active .tab-label {
  color: #e74c3c;
  font-weight: bold;
}
.order-tab.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 25%;
  width: 50%;
  height: 4rpx;
  background: #e74c3c;
  border-radius: 2rpx;
}

/* ══ 订单加载 / 空状态 ══ */
.order-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80rpx 0;
}
.order-loading-text { font-size: 28rpx; color: #bbb; }

.order-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 80rpx 0;
}
.empty-icon { font-size: 64rpx; margin-bottom: 16rpx; }
.empty-text { font-size: 26rpx; color: #bbb; margin-bottom: 32rpx; }
.go-register-btn {
  width: 200rpx;
  height: 68rpx;
  line-height: 68rpx;
  background: #e74c3c;
  color: #fff;
  border-radius: 34rpx;
  font-size: 26rpx;
  border: none;
}

/* ══ 订单列表 ══ */
.order-list { padding: 16rpx 20rpx 0; }
.order-card {
  background: #fafafa;
  border-radius: 14rpx;
  margin-bottom: 16rpx;
  overflow: hidden;
}
.order-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20rpx 24rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.order-no     { font-size: 24rpx; color: #999; }
.order-status { font-size: 24rpx; font-weight: bold; }
.status-pending { color: #fa8c16; }
.status-paid    { color: #52c41a; }
.status-cancel  { color: #bbb;    }
.status-refund  { color: #e74c3c; }

.order-body { padding: 6rpx 24rpx; }
.info-row {
  display: flex;
  justify-content: space-between;
  padding: 12rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
  &:last-child { border-bottom: none; }
}
.info-key { font-size: 24rpx; color: #999; }
.info-val { font-size: 24rpx; color: #333; font-weight: 500; }

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14rpx 24rpx;
  border-top: 1rpx solid #f0f0f0;
}
.order-amount-label { font-size: 24rpx; color: #999; }
.order-price { font-size: 32rpx; font-weight: bold; color: #e74c3c; }

/* 待支付操作按钮 - 右对齐 */
.repay-wrap {
  padding: 0 24rpx 20rpx;
  display: flex;
  justify-content: flex-end;
  gap: 16rpx;
}
.cancel-btn {
  width: 140rpx;
  height: 52rpx;
  line-height: 52rpx;
  background: #fff;
  color: #999;
  border-radius: 26rpx;
  font-size: 24rpx;
  border: 2rpx solid #ddd;
  text-align: center;
  margin: 0;
  padding: 0;
}
.repay-btn {
  width: 140rpx;
  height: 52rpx;
  line-height: 52rpx;
  background: #e74c3c;
  color: #fff;
  border-radius: 26rpx;
  font-size: 24rpx;
  border: none;
  text-align: center;
  margin: 0;
  padding: 0;
  float: none;
}

/* ══ 加载更多 ══ */
.load-more-wrap {
  padding: 24rpx 32rpx;
}
.load-more-btn {
  width: 100%;
  height: 72rpx;
  line-height: 72rpx;
  background: #fff;
  color: #666;
  border-radius: 36rpx;
  font-size: 26rpx;
  border: 2rpx solid #e0e0e0;
  box-shadow: none;
}
.no-more-wrap {
  padding: 24rpx 0;
  display: flex;
  justify-content: center;
}
.no-more-text { font-size: 24rpx; color: #ccc; }
</style>
