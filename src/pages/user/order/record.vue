<template>
   <view class="container">
      <!-- Tab 切换 -->
      <view class="tabs-wrap">
         <u-tabs :list="tabList" :current="currentTab" lineColor="#e1251b" :activeStyle="activeStyle"
            :inactiveStyle="inactiveStyle" itemStyle="padding: 0 24rpx; height: 80rpx;" @change="handleTabChange">
         </u-tabs>
      </view>

      <!-- 加载中 -->
      <view v-if="loading" class="loading-wrap">
         <text class="loading-text">加载中...</text>
      </view>

      <!-- 订单列表 -->
      <view v-else class="order-list">
         <view v-if="orderList.length > 0" class="list-content">
            <view class="order-card" v-for="order in orderList" :key="order.id">
               <view class="order-header">
                  <view class="order-header-left">
                     <text class="order-no">订单号：{{ order.orderNo }}</text>
                     <text class="order-create-time">{{ order.createTime }}</text>
                  </view>
                  <u-tag :text="getStatusText(order.status)" :type="getStatusType(order.status)" size="mini" plain>
                  </u-tag>
               </view>

               <view class="goods-list">
                  <view class="goods-item" v-for="goods in order.goodsList" :key="goods.id">
                     <u-image :src="goods.image" width="120rpx" height="120rpx" mode="aspectFill" border-radius="8">
                     </u-image>
                     <view class="goods-info">
                        <text class="goods-name">{{ goods.name }}</text>
                        <view class="goods-bottom">
                           <text class="goods-unit-price">¥{{ goods.unitPrice }} x{{ goods.quantity }}</text>
                           <text class="goods-total-price">¥{{ goods.totalPrice }}</text>
                        </view>
                     </view>
                  </view>
               </view>

               <view class="order-amount">
                  <view class="amount-row">
                     <text>共{{ getTotalQuantity(order) }}件商品</text>
                  </view>
                  <view class="amount-detail">
                     <view class="amount-item">
                        <text class="amount-label">商品小计</text>
                        <text class="amount-value">¥{{ order.totalAmount }}</text>
                     </view>
                     <view class="amount-item">
                        <text class="amount-label">配送费</text>
                        <text class="amount-value">¥{{ order.deliveryFee }}</text>
                     </view>
                     <view class="amount-item total">
                        <text class="amount-label">订单总价</text>
                        <text class="amount-value">¥{{ order.payAmount }}</text>
                     </view>
                  </view>
               </view>

               <view class="order-time">
                  <view v-if="order.status !== 'pending' && order.payTime" class="time-row">
                     <view class="time-left">
                        <u-icon name="clock" size="26rpx" color="#999"></u-icon>
                        <text class="time-label">支付时间</text>
                     </view>
                     <text class="time-value">{{ order.payTime }}</text>
                  </view>
                  <view v-if="order.status === 'completed' && order.completeTime" class="time-row">
                     <view class="time-left">
                        <u-icon name="checkmark-circle" size="26rpx" color="#999"></u-icon>
                        <text class="time-label">完成时间</text>
                     </view>
                     <text class="time-value">{{ order.completeTime }}</text>
                  </view>
                  <view v-if="order.status === 'cancelled' && order.completeTime" class="time-row">
                     <view class="time-left">
                        <u-icon name="close-circle" size="26rpx" color="#999"></u-icon>
                        <text class="time-label">取消时间</text>
                     </view>
                     <text class="time-value">{{ order.completeTime }}</text>
                  </view>
                  <view v-if="order.status === 'timeout' && order.completeTime" class="time-row">
                     <view class="time-left">
                        <u-icon name="clock" size="26rpx" color="#999"></u-icon>
                        <text class="time-label">超时时间</text>
                     </view>
                     <text class="time-value">{{ order.completeTime }}</text>
                  </view>
               </view>

               <view v-if="order.userRemark || order.adminRemark" class="remark-section">
                  <view v-if="order.userRemark" class="remark-item">
                     <view class="remark-left">
                        <u-icon name="chat" size="26rpx" color="#999"></u-icon>
                        <text class="remark-label">用户备注</text>
                     </view>
                     <text class="remark-value">{{ order.userRemark }}</text>
                  </view>
                  <view v-if="order.adminRemark" class="remark-item">
                     <view class="remark-left">
                        <u-icon name="account" size="26rpx" color="#999"></u-icon>
                        <text class="remark-label">管理员备注</text>
                     </view>
                     <text class="remark-value">{{ order.adminRemark }}</text>
                  </view>
               </view>

               <view class="action-section">
                  <u-button v-if="order.status === 'pending'" type="error" size="mini" shape="circle"
                     @click="handlePay(order)">立即支付</u-button>
                  <u-button v-if="order.status === 'pending'" type="info" size="mini" shape="circle" plain
                     @click="handleCancel(order)">取消订单</u-button>
               </view>
            </view>
            <view class="bottom-placeholder">
               <text class="tip-text">仅显示近7天的订单记录</text>
            </view>
         </view>
         <view v-else class="empty-state">
            <u-empty mode="order" text="暂无订单"></u-empty>
         </view>
      </view>
   </view>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getApi, getAuth } from '@/utils/api'
import { goToLogin, checkShopStatus } from '@/utils/go'

const tabList = [
   { name: '全部', value: '' },
   { name: '待支付', value: 'pending' },
   { name: '已支付', value: 'paid' },
   { name: '已完成', value: 'completed' },
   { name: '已取消', value: 'cancelled' }
]

const activeStyle = { color: '#e1251b', fontWeight: 'bold', fontSize: '30rpx' }
const inactiveStyle = { color: '#666', fontSize: '28rpx' }

const currentTab = ref(0)
const orderList = ref([])
const loading = ref(false)

const getStatusText = (status) => {
   const map = { pending: '待支付', paid: '已支付', completed: '已完成', cancelled: '已取消', timeout: '已超时' }
   return map[status] || status
}

const getStatusType = (status) => {
   const map = { pending: 'warning', paid: 'success', completed: 'info', cancelled: 'info', timeout: 'error' }
   return map[status] || 'info'
}

const getTotalQuantity = (order) => {
   return order.goodsList?.reduce((sum, item) => sum + item.quantity, 0) || 0
}

const fetchOrderList = async () => {
   loading.value = true
   try {
      const auth = getAuth()
      if (!auth) {
         uni.showToast({
            title: '请先登录',
            icon: 'none'
         })
         goToLogin(1500)
         return
      }

      const status = tabList[currentTab.value].value
      let url = getApi('/user/order/history')
      if (status) url += `?status=${status}`

      const res = await uni.request({
         url,
         method: 'GET',
         header: {
            'Authorization': auth
         }
      })

      checkShopStatus(res.data.code, res.data.message)

      if (res.statusCode === 401 && res.data.code === 401) {
         uni.removeStorageSync('userInfo')
         uni.showToast({
            title: res.data?.message || '登录已过期，请重新登录',
            icon: 'none',
            duration: 1500,
            success: () => {
               goToLogin(1500)
            }
         })
         return
      }

      if (res.statusCode === 200 && res.data.code === 200) {
         orderList.value = res.data.data.list || []
      } else if (res.statusCode !== 200) {
         uni.showToast({ title: res.data.message || '获取失败', icon: 'none' })
      }
   } catch (e) {
      uni.showToast({ title: '网络错误', icon: 'none' })
   } finally {
      loading.value = false
   }
}

const handleTabChange = (index) => {
   currentTab.value = index
   fetchOrderList()
}

const handlePay = async (order) => {
   try {
      const res = await uni.request({
         url: getApi(`/user/order/payUrl?id=${order.id}`),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      })
      if (res.statusCode === 200 && res.data.code === 200) {
         uni.navigateTo({
            url: `/pages/user/order/pay?url=${res.data.data.payUrl}`
         })
      } else {
         uni.showToast({
            duration: 0,
            title: res.data?.message || '获取支付链接失败', icon: 'none'
         })
      }
   } catch (e) {
      uni.showToast({
         duration: 0,
         title: '网络错误', icon: 'none'
      })
   }
}

const handleCancel = (order) => {
   uni.showModal({
      title: '提示',
      content: '确定取消该订单？',
      success: (res) => {
         if (res.confirm) cancelOrder(order.id)
      }
   })
}

const cancelOrder = async (id) => {
   try {
      const res = await uni.request({
         url: getApi('/user/order/cancel'),
         method: 'POST',
         header: {
            'Authorization': getAuth()
         },
         data: { id }
      })
      if (res.data.code === 200) {
         uni.showToast({ title: '取消成功', icon: 'success' })
         fetchOrderList()
      } else {
         uni.showToast({
            duration: 0,
            title: res.data?.message || '取消失败', icon: 'none'
         })
      }
   } catch (e) {
      uni.showToast({
         duration: 0,
         title: '网络错误', icon: 'none'
      })
   }
}

onLoad(() => fetchOrderList())
</script>

<style scoped lang="scss">
.container {
   min-height: 94vh;
   background: #f5f5f5;
}

.tabs-wrap {
   position: sticky;
   top: 0;
   z-index: 100;
   background: #fff;
}

.loading-wrap {
   display: flex;
   justify-content: center;
   padding: 100rpx 0;

   .loading-text {
      font-size: 28rpx;
      color: #999;
   }
}

.list-content {
   padding: 20rpx;
}

.order-card {
   background: #fff;
   border-radius: 16rpx;
   padding: 24rpx;
   margin-bottom: 20rpx;
}

.order-header {
   display: flex;
   justify-content: space-between;
   align-items: flex-start;
   margin-bottom: 20rpx;
   padding-bottom: 20rpx;
   border-bottom: 1rpx solid #f0f0f0;

   .order-header-left {
      display: flex;
      flex-direction: column;
      gap: 8rpx;
   }

   .order-no {
      font-size: 26rpx;
      color: #666;
   }

   .order-create-time {
      font-size: 20rpx;
      color: #bbb;
   }
}

.goods-list {
   display: flex;
   flex-direction: column;
   gap: 16rpx;
   margin-bottom: 20rpx;
}

.goods-item {
   display: flex;
   gap: 16rpx;

   .goods-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;

      .goods-name {
         font-size: 28rpx;
         color: #333;
      }

      .goods-bottom {
         display: flex;
         justify-content: space-between;
         align-items: center;

         .goods-unit-price {
            color: #999;
            font-size: 24rpx;
         }

         .goods-total-price {
            color: #333;
            font-weight: bold;
            font-size: 28rpx;
         }
      }
   }
}

.order-amount {
   padding: 16rpx 0;
   border-top: 1rpx solid #f5f5f5;
   font-size: 26rpx;
   color: #666;

   .amount-row {
      margin-bottom: 12rpx;
   }

   .amount-detail {
      background: #fafafa;
      border-radius: 8rpx;
      padding: 16rpx;
      margin-bottom: 16rpx;

      .amount-item {
         display: flex;
         justify-content: space-between;
         margin-bottom: 8rpx;

         &:last-child {
            margin-bottom: 0;
         }

         &.total {
            border-top: 1rpx dashed #ddd;
            padding-top: 8rpx;
            margin-top: 8rpx;

            .amount-label,
            .amount-value {
               font-weight: bold;
               color: #333;
            }
         }

         .amount-label {
            color: #999;
         }

         .amount-value {
            color: #666;
         }
      }
   }

   .pay-amount {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 12rpx;
      border-top: 1rpx solid #f0f0f0;

      .pay-label {
         font-size: 28rpx;
         color: #333;
         font-weight: bold;
      }

      .pay-value {
         font-size: 32rpx;
         color: #e1251b;
         font-weight: bold;
      }
   }
}

.order-time {
   margin-top: 12rpx;
   padding: 16rpx 20rpx;
   background: #fafafa;
   border-radius: 8rpx;

   .time-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12rpx;

      &:last-child {
         margin-bottom: 0;
      }
   }

   .time-left {
      display: flex;
      align-items: center;
      gap: 10rpx;
   }

   .time-label {
      color: #666;
      font-size: 26rpx;
   }

   .time-value {
      color: #999;
      font-size: 26rpx;
   }
}

.remark-section {
   margin-top: 12rpx;
   padding: 16rpx 20rpx;
   background: #fafafa;
   border-radius: 8rpx;

   .remark-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12rpx;

      &:last-child {
         margin-bottom: 0;
      }
   }

   .remark-left {
      display: flex;
      align-items: center;
      gap: 10rpx;
   }

   .remark-label {
      color: #666;
      font-size: 26rpx;
   }

   .remark-value {
      color: #999;
      font-size: 26rpx;
      text-align: right;
      flex: 1;
      margin-left: 16rpx;
      word-break: break-all;
   }
}

.action-section {
   display: flex;
   justify-content: flex-end;
   gap: 16rpx;
   padding-top: 16rpx;
}

.empty-state {
   padding-top: 200rpx;
}

.bottom-placeholder {
   margin-bottom: 100rpx;

   .tip-text {
      margin: 20rpx;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 24rpx;
      color: #999;
   }
}
</style>
