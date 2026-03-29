<template>
   <view class="status-container">
      <view class="status-card">
         <view class="status-icon">
            <view class="icon-circle">
               <u-icon name="pause-circle" size="80" color="#fff"></u-icon>
            </view>
         </view>
         <view class="status-info">
            <text class="status-title">店铺已打烊</text>
            <text class="status-desc">{{ message }}</text>
         </view>
         <view class="status-hours">
            <view class="hours-dot"></view>
            <text class="hours-text">营业时间 07:00 - 23:30</text>
            <view class="hours-dot"></view>
         </view>
      </view>
   </view>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

const message = ref('')

onLoad(() => {
   const pages = getCurrentPages()
   const currentPage = pages[pages.length - 1]
   const options = currentPage?.options || {}

   if (options.m) {
      try {
         message.value = decodeURIComponent(options.m)
      } catch (e) {
         message.value = '明天再来吧～'
      }
   } else {
      message.value = '明天再来吧～'
   }
})
</script>

<style scoped lang="scss">
.status-container {
   min-height: 100vh;
   background: linear-gradient(180deg, #f8f8f8 0%, #e8e8e8 100%);
   display: flex;
   align-items: center;
   justify-content: center;
   padding: 40rpx;
}

.status-card {
   width: 100%;
   max-width: 560rpx;
   background-color: #ffffff;
   border-radius: 32rpx;
   padding: 96rpx 64rpx;
   display: flex;
   flex-direction: column;
   align-items: center;
   box-shadow: 0 12rpx 40rpx rgba(0, 0, 0, 0.08);
}

.status-icon {
   margin-bottom: 56rpx;
}

.icon-circle {
   width: 140rpx;
   height: 140rpx;
   background: linear-gradient(135deg, #e1251b 0%, #ff6b6b 100%);
   border-radius: 50%;
   display: flex;
   align-items: center;
   justify-content: center;
   box-shadow: 0 8rpx 24rpx rgba(225, 37, 27, 0.3);
}

.status-info {
   text-align: center;
   margin-bottom: 56rpx;
}

.status-title {
   font-size: 44rpx;
   font-weight: 600;
   color: #1a1a1a;
   display: block;
   margin-bottom: 16rpx;
}

.status-desc {
   font-size: 28rpx;
   color: #666;
   line-height: 1.6;
}

.status-hours {
   display: flex;
   align-items: center;
   gap: 16rpx;
   padding: 24rpx 40rpx;
   background-color: #fafafa;
   border-radius: 100rpx;
}

.hours-dot {
   width: 8rpx;
   height: 8rpx;
   background-color: #e1251b;
   border-radius: 50%;
}

.hours-text {
   font-size: 26rpx;
   color: #666;
}
</style>
