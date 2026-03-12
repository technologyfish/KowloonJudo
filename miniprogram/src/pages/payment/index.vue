<template>
  <view class="page">
    <view class="tip-card">
      <text class="tip-text">订单已提交，请在「我的订单」中查看并完成支付</text>
      <button class="go-btn" @click="goOrders">查看我的订单</button>
    </view>
  </view>
</template>

<script setup>
function goOrders() {
  uni.switchTab({ url: '/pages/user/index' })
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
}
.tip-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 60rpx 40rpx;
  margin: 0 40rpx;
  text-align: center;
  box-shadow: 0 2rpx 12rpx rgba(0,0,0,.06);
}
.tip-text {
  font-size: 28rpx;
  color: #666;
  display: block;
  margin-bottom: 40rpx;
}
.go-btn {
  width: 300rpx;
  height: 80rpx;
  line-height: 80rpx;
  background: #e74c3c;
  color: #fff;
  border-radius: 40rpx;
  font-size: 30rpx;
  border: none;
}
</style>
