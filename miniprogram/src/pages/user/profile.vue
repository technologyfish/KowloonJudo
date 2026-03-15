<template>
  <view class="page">

    <!-- ── 每一项独立白色行，灰色背景形成间隔 ── -->

    <!-- 头像 -->
    <button
      class="row avatar-row"
      open-type="chooseAvatar"
      hover-class="row-hover"
      :disabled="avatarUploading"
      @chooseavatar="onChooseAvatar"
    >
      <text class="row-label">头像</text>
      <view class="row-right">
        <view class="avatar-wrap">
          <image class="avatar-img" :src="avatarSrc" mode="aspectFill" />
          <view v-if="avatarUploading" class="avatar-uploading-mask">
            <text class="avatar-uploading-text">上传中</text>
          </view>
        </view>
        <text class="row-arrow">›</text>
      </view>
    </button>

    <!-- 昵称 -->
    <view class="row" @click="openModal('nickname', '昵称', userStore.userInfo?.nickname)">
      <text class="row-label">昵称</text>
      <view class="row-right">
        <text class="row-value">{{ userStore.userInfo?.nickname || '请设置昵称' }}</text>
        <text class="row-arrow">›</text>
      </view>
    </view>

    <!-- 性别 -->
    <view class="row">
      <text class="row-label">性别</text>
      <picker class="row-picker" :range="genderOptions" @change="onGenderChange">
        <view class="row-right">
          <text class="row-value" :class="{ gray: !genderLabel }">{{ genderLabel || '请选择' }}</text>
          <text class="row-arrow">›</text>
        </view>
      </picker>
    </view>

    <!-- 出生日期 -->
    <view class="row">
      <text class="row-label">出生日期</text>
      <picker class="row-picker" mode="date" :value="birthdayVal" start="1950-01-01" :end="today" @change="onBirthdayChange">
        <view class="row-right">
          <text class="row-value" :class="{ gray: !birthdayVal }">{{ birthdayVal || '请选择' }}</text>
          <text class="row-arrow">›</text>
        </view>
      </picker>
    </view>

    <!-- 绑定手机号 -->
    <view class="row" @click="openModal('phone', '手机号', userStore.userInfo?.phone)">
      <text class="row-label">绑定手机号</text>
      <view class="row-right">
        <text class="row-value" :class="{ gray: !userStore.userInfo?.phone }">
          {{ userStore.userInfo?.phone || '未绑定' }}
        </text>
        <text class="row-arrow">›</text>
      </view>
    </view>

    <!-- 占位，撑开页面避免被底部栏遮住 -->
    <view class="page-spacer" />

    <!-- ══ 退出登录：固定在底部，白色背景 ══ -->
    <view class="logout-bar">
      <view class="logout-btn" hover-class="logout-btn-hover" @click="handleLogout">
        <text class="logout-text">退出登录</text>
      </view>
    </view>

    <!-- ══ 通用编辑弹窗 ══ -->
    <view v-if="modalVisible" class="modal-mask" @click.self="modalVisible = false">
      <view class="modal-box">
        <text class="modal-title">{{ modalTitle }}</text>
        <input
          class="modal-input"
          :type="modalField === 'nickname' ? 'nickname' : modalField === 'phone' ? 'number' : 'text'"
          :placeholder="'请输入' + modalTitle"
          :value="modalValue"
          @input="modalValue = $event.detail.value"
          @change="modalValue = $event.detail.value"
          focus
        />
        <view class="modal-btns">
          <button class="modal-cancel" @click="modalVisible = false">取消</button>
          <button class="modal-confirm" :loading="modalSaving" @click="saveModal">保存</button>
        </view>
      </view>
    </view>

  </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user'
import { get, post } from '@/utils/request'

// const BASE_URL = 'https://copade.net.cn/api'
const BASE_URL =  'http://localhost:8000/api'
const userStore = useUserStore()

// ── 每次进入页面从后端刷新用户信息（确保 gender/birthday 等字段不丢失）──
onShow(async () => {
  try {
    const res = await get('/user/info')
    if (res.data) {
      userStore.setUserInfo(res.data)
      birthdayVal.value = (res.data.birthday || '').slice(0, 10)
    }
  } catch { /* ignore */ }
})

// ── 头像 ────────────────────────────────────────────────────────
const avatarUploading = ref(false)
const DEFAULT_AVATAR  = 'https://mmbiz.qpic.cn/mmbiz/icTdbqWNOwNRna42FI242Lcia07jQodd2FJGIYQfG0LAJGFxM4FbnQP6yfMxBgJ0F3YRqJCJ1aPAK2dQagdusBZg/0'
const avatarSrc = computed(() => {
  const av = userStore.userInfo?.avatar
  return (av && av.trim()) ? av : DEFAULT_AVATAR
})

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
    uni.showToast({ title: err?.message || '更新失败', icon: 'none' })
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
      header: { Authorization: userStore.token ? `Bearer ${userStore.token}` : '' },
      success(res) {
        try {
          const data = typeof res.data === 'string' ? JSON.parse(res.data) : res.data
          data?.data?.url ? resolve(data.data.url) : reject(new Error(data?.message || '上传失败'))
        } catch { reject(new Error('响应解析失败')) }
      },
      fail() { reject(new Error('网络错误，上传失败')) }
    })
  })
}

// ── 通用弹窗 ─────────────────────────────────────────────────────
const modalVisible = ref(false)
const modalField   = ref('')
const modalTitle   = ref('')
const modalValue   = ref('')
const modalSaving  = ref(false)

function openModal(field, title, current) {
  modalField.value   = field
  modalTitle.value   = title
  modalValue.value   = current || ''
  modalVisible.value = true
}

async function saveModal() {
  const val = modalValue.value.trim()
  if (!val && modalField.value === 'nickname') {
    uni.showToast({ title: '昵称不能为空', icon: 'none' })
    return
  }
  modalSaving.value = true
  try {
    await post('/user/info', { [modalField.value]: val })
    userStore.setUserInfo({ ...userStore.userInfo, [modalField.value]: val })
    modalVisible.value = false
    uni.showToast({ title: '保存成功', icon: 'success' })
  } catch {
    uni.showToast({ title: '保存失败', icon: 'none' })
  } finally {
    modalSaving.value = false
  }
}

// ── 性别（只有男/女）────────────────────────────────────────────
const genderOptions = ['男', '女']
const genderLabel   = computed(() => {
  const g = userStore.userInfo?.gender
  if (g === 1) return '男'
  if (g === 2) return '女'
  return ''
})

async function onGenderChange(e) {
  const newGender = Number(e.detail.value) === 0 ? 1 : 2
  if (newGender === userStore.userInfo?.gender) return
  try {
    await post('/user/info', { gender: newGender })
    userStore.setUserInfo({ ...userStore.userInfo, gender: newGender })
    uni.showToast({ title: '已保存', icon: 'success', duration: 800 })
  } catch {
    uni.showToast({ title: '保存失败', icon: 'none' })
  }
}

// ── 出生日期 ─────────────────────────────────────────────────────
const today       = new Date().toISOString().slice(0, 10)
const birthdayVal = ref((userStore.userInfo?.birthday || '').slice(0, 10))

async function onBirthdayChange(e) {
  const val = e.detail.value
  if (val === birthdayVal.value) return
  birthdayVal.value = val
  try {
    await post('/user/info', { birthday: val })
    userStore.setUserInfo({ ...userStore.userInfo, birthday: val })
    uni.showToast({ title: '已保存', icon: 'success', duration: 800 })
  } catch {
    uni.showToast({ title: '保存失败', icon: 'none' })
  }
}

// ── 退出登录 ─────────────────────────────────────────────────────
function handleLogout() {
  uni.showModal({
    title: '提示',
    content: '确定要退出登录吗？',
    success(res) {
      if (res.confirm) {
        userStore.logout()
        uni.reLaunch({ url: '/pages/login/index' })
      }
    }
  })
}
</script>

<style lang="scss" scoped>
/* ══ 页面 ══ */
.page {
  min-height: 100vh;
  background: #f7f7f7;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom)); /* 为底部 bar 留位置 */
}

/* ══ 每一行：独立白色块，下方留灰色间隔 ══ */
.row {
  display: flex;
  align-items: center;
  background: #fff;
  padding: 0 36rpx;
  min-height: 104rpx;
  margin-bottom: 2rpx;   /* 行间的细灰缝 */
  font-size: 30rpx;
  width: 100%;
  box-sizing: border-box;
  text-align: left;
  border-radius: 0;
}

/* 每个区块之间的大间隔：昵称/性别/手机号 各留一条明显灰缝 */
.row + .row {
  margin-top: 0;
}

/* 给整体分组一点上间距，让第一行不贴着导航栏 */
.row:first-child {
  margin-top: 20rpx;
}

/* 每个 section 之间加大间隔（通过额外 margin-bottom） */
.row:nth-child(1) { margin-bottom: 20rpx; } /* 头像 → 昵称 */
.row:nth-child(2) { margin-bottom: 2rpx;  } /* 昵称 → 性别 */
.row:nth-child(3) { margin-bottom: 20rpx; } /* 性别 → 出生 */
.row:nth-child(4) { margin-bottom: 20rpx; } /* 出生 → 手机 */

.row-hover { background: #f9f9f9 !important; }

/* button 特有重置 */
.avatar-row {
  line-height: 1;
  &::after { border: none; }
}

.row-label {
  font-size: 30rpx;
  color: #222;
  flex-shrink: 0;
  width: 200rpx;
}
.row-right {
  display: flex;
  align-items: center;
  gap: 12rpx;
  flex: 1;
  justify-content: flex-end;
}
.row-value {
  font-size: 28rpx;
  color: #333;
  max-width: 340rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.gray { color: #bbb; }
.row-picker { flex: 1; }
.row-arrow  { font-size: 36rpx; color: #ccc; }

/* 头像 */
.avatar-wrap {
  position: relative;
  width: 80rpx;
  height: 80rpx;
}
.avatar-uploading-mask {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}
.avatar-uploading-text { font-size: 18rpx; color: #fff; }
.avatar-img {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
}

/* ══ 底部退出栏：固定居底，白色背景 ══ */
.logout-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 20rpx 36rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  border-top: 1rpx solid #f0f0f0;
  z-index: 100;
}
.logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  height: 88rpx;
  border-radius: 8rpx;
  background: #fff;
}
.logout-btn-hover .logout-text {
  opacity: 0.6;
}
.logout-text {
  font-size: 30rpx;
  color: #333;
  font-weight: 500;
}

/* 占位撑高，防止底部 bar 遮住内容 */
.page-spacer { height: 20rpx; }

/* ══ 通用弹窗 ══ */
.modal-mask {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.modal-box {
  width: 620rpx;
  background: #fff;
  border-radius: 20rpx;
  padding: 48rpx 40rpx 40rpx;
}
.modal-title {
  display: block;
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
  text-align: center;
  margin-bottom: 36rpx;
}
.modal-input {
  width: 100%;
  height: 90rpx;
  background: #f7f8fa;
  border-radius: 12rpx;
  padding: 0 24rpx;
  font-size: 30rpx;
  color: #333;
  margin-bottom: 32rpx;
  box-sizing: border-box;
}
.modal-btns { display: flex; gap: 20rpx; }
.modal-cancel, .modal-confirm {
  flex: 1;
  height: 84rpx;
  line-height: 84rpx;
  border-radius: 42rpx;
  font-size: 28rpx;
  border: none;
}
.modal-cancel  { background: #f5f5f5; color: #666; }
.modal-confirm { background: #1677ff; color: #fff; }
</style>
