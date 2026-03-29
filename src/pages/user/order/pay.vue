<template>
   <view class="pay-container">
      <web-view :src="payUrl" @message="handleMessage"></web-view>
   </view>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

const payUrl = ref('')

onLoad((options) => {
   if (options.url) {
      payUrl.value = options.url
   } else {
      uni.showToast({
         title: '支付链接无效',
         icon: 'none'
      })
      setTimeout(() => {
         uni.navigateBack()
      }, 1500)
   }
})

const handleMessage = (e) => {
   console.log('支付页面消息:', e.detail)
}
</script>

<style scoped>
.pay-container {
   width: 100%;
   height: 94vh;
}
</style>
