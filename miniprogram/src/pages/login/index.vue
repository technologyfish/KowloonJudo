<template>
  <!-- 检查登录状态期间不渲染任何内容，防止闪屏 -->
  <view v-if="!checking" class="login-page">

    <!-- 步骤1：微信登录 -->
    <view v-if="step === 'login'" class="btn-wrap">
      <button class="wx-login-btn" :loading="loading" :disabled="loading" @click="handleWxLogin">
        {{ loading ? '登录中...' : '微信一键登录' }}
      </button>

      <!-- 隐私声明 -->
      <view class="privacy-wrap">
        <view class="checkbox-row">
          <view class="checkbox" :class="{ checked: agreed }" @click="agreed = !agreed">
            <text v-if="agreed" class="check-icon">✓</text>
          </view>
          <text class="privacy-text">
            我已同意并阅读
            <text class="privacy-link" @click.stop="showPrivacy('service')">《用户服务协议》</text>
            与
            <text class="privacy-link" @click.stop="showPrivacy('privacy')">《隐私政策》</text>
          </text>
        </view>
      </view>
    </view>

    <!-- 步骤2：新用户完善资料 -->
    <view v-if="step === 'profile'" class="profile-wrap">
      <text class="profile-title">完善个人信息</text>
      <text class="profile-sub">仅用于在平台展示，不会分享给第三方</text>

      <!-- 微信官方头像选择组件 -->
      <button class="avatar-btn" open-type="chooseAvatar" hover-class="none" @chooseavatar="onChooseAvatar">
        <image
          class="avatar-img"
          :src="avatar || 'https://mmbiz.qpic.cn/mmbiz/icTdbqWNOwNRna42FI242Lcia07jQodd2FJGIYQfG0LAJGFxM4FbnQP6yfMxBgJ0F3YRqJCJ1aPAK2dQagdusBZg/0'"
          mode="aspectFill"
        />
        <view class="avatar-edit"><text>换头像</text></view>
      </button>

      <!-- 微信官方昵称输入组件（键盘上方有快捷填充） -->
      <view class="nickname-wrap">
        <input
          class="nickname-input"
          type="nickname"
          placeholder="请输入昵称"
          :value="nickname"
          @input="nickname = $event.detail.value"
          @change="nickname = $event.detail.value"
        />
      </view>

      <button class="submit-btn" :loading="saving" @click="handleSaveProfile">
        完成，进入我的页面
      </button>
      <text class="skip-text" @click="skipProfile">跳过，先不设置</text>
    </view>

  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUserStore } from '@/store/user'
import { wxLogin, updateUserInfo } from '@/api/user'

const userStore = useUserStore()

const checking = ref(true)   // true 时不渲染 UI，防闪屏
const step     = ref('login')
const loading  = ref(false)
const saving   = ref(false)
const avatar   = ref('')
const nickname = ref('')
const agreed   = ref(false)  // 是否勾选协议

// ── 启动检查：已登录直接跳"我的"，不渲染登录页 ───────────────
onMounted(() => {
  if (userStore.isLoggedIn) {
    uni.switchTab({ url: '/pages/user/index' })
    // 不设 checking=false，让页面保持空白直到跳转完成
    return
  }
  checking.value = false
})

// ── 跳转协议页面 ─────────────────────────────────────────────
function showPrivacy(type) {
  if (type === 'service') {
    uni.navigateTo({ url: '/pages/agreement/service' })
  } else {
    uni.navigateTo({ url: '/pages/agreement/privacy' })
  }
}

// ── Step1: 微信登录 ─────────────────────────────────────────
async function handleWxLogin() {
  if (loading.value) return
  if (!agreed.value) {
    uni.showToast({ title: '请先阅读并同意用户协议', icon: 'none' })
    return
  }
  loading.value = true
  try {
    const loginRes = await new Promise((resolve, reject) =>
      uni.login({ provider: 'weixin', success: resolve, fail: reject })
    )
    if (!loginRes.code) throw new Error('获取 code 失败')

    const res = await wxLogin(loginRes.code)
    const { token, user, is_new_user } = res.data || res
    if (!token) throw new Error('未获取到 token')

    userStore.setToken(token)
    userStore.setUserInfo(user)

    if (is_new_user) {
      // 新用户完善资料
      step.value = 'profile'
    } else {
      // 老用户直接进"我的"页面
      goToMe('登录成功')
    }
  } catch (e) {
    console.error('[wxLogin]', e)
    uni.showToast({ title: e?.message || '登录失败，请重试', icon: 'none' })
  } finally {
    loading.value = false
  }
}

// ── Step2: 选头像 ────────────────────────────────────────────
function onChooseAvatar(e) {
  avatar.value = e.detail.avatarUrl
}

// ── Step2: 保存资料 ──────────────────────────────────────────
async function handleSaveProfile() {
  if (!nickname.value.trim()) {
    uni.showToast({ title: '请输入昵称', icon: 'none' })
    return
  }
  saving.value = true
  try {
    await updateUserInfo({ nickname: nickname.value, avatar: avatar.value })
    userStore.setUserInfo({ ...userStore.userInfo, nickname: nickname.value, avatar: avatar.value })
    goToMe('设置成功')
  } catch (e) {
    uni.showToast({ title: '保存失败，请重试', icon: 'none' })
  } finally {
    saving.value = false
  }
}

// 跳过完善资料
function skipProfile() {
  goToMe()
}

// 统一跳转到"我的"页面
function goToMe(msg) {
  if (msg) {
    uni.showToast({ title: msg, icon: 'success', duration: 1200 })
  }
  setTimeout(() => {
    uni.switchTab({ url: '/pages/user/index' })
  }, msg ? 1200 : 0)
}
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(160deg, #f0f7ff 0%, #ffffff 60%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0 60rpx;
}

/* ── 登录按钮 ── */
.btn-wrap {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.wx-login-btn {
  width: 100%;
  height: 96rpx;
  line-height: 96rpx;
  background: #07c160;
  color: #fff;
  border-radius: 48rpx;
  font-size: 32rpx;
  font-weight: 500;
  letter-spacing: 2rpx;
  border: none;
  box-shadow: none;
  &::after { border: none; }
}

/* ── 隐私声明 ── */
.privacy-wrap {
  margin-top: 32rpx;
  width: 100%;
}
.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
}
.checkbox {
  width: 36rpx;
  height: 36rpx;
  border-radius: 8rpx;
  border: 2rpx solid #ccc;
  background: #fff;
  flex-shrink: 0;
  margin-top: 4rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  &.checked {
    background: #07c160;
    border-color: #07c160;
  }
}
.check-icon {
  font-size: 24rpx;
  color: #fff;
  font-weight: bold;
}
.privacy-text {
  font-size: 24rpx;
  color: #999;
  line-height: 1.6;
  flex: 1;
}
.privacy-link {
  color: #1677ff;
}

/* ── 完善资料 ── */
.profile-wrap {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.profile-title { font-size: 40rpx; font-weight: bold; color: #1a1a1a; margin-bottom: 12rpx; }
.profile-sub   { font-size: 24rpx; color: #999; margin-bottom: 48rpx; text-align: center; }

.avatar-btn {
  width: 160rpx;
  height: 160rpx;
  border-radius: 50%;
  padding: 0;
  border: none;
  background: transparent;
  position: relative;
  margin-bottom: 40rpx;
  overflow: hidden;
}
.avatar-img  { width: 160rpx; height: 160rpx; border-radius: 50%; display: block; }
.avatar-edit {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: rgba(0,0,0,0.4);
  height: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  text { font-size: 22rpx; color: #fff; }
}

.nickname-wrap {
  width: 100%;
  background: #f7f8fa;
  border-radius: 16rpx;
  padding: 0 32rpx;
  margin-bottom: 40rpx;
}
.nickname-input {
  width: 100%;
  height: 96rpx;
  font-size: 30rpx;
  color: #333;
}

.submit-btn {
  width: 100%;
  height: 96rpx;
  line-height: 96rpx;
  background: #1677ff;
  color: #fff;
  border-radius: 48rpx;
  font-size: 32rpx;
  border: none;
  box-shadow: 0 8rpx 24rpx rgba(22,119,255,0.3);
  margin-bottom: 32rpx;
}
.skip-text { font-size: 26rpx; color: #999; }
</style>
