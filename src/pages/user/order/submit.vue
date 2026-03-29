<template>
   <tab-navbar title="结算" :show-back="true"></tab-navbar>
   <!-- 订单提交页面容器 -->
   <view class="container">
      <!-- 收货地址卡片 -->
      <view class="card address-card">
         <view class="card-header">
            <u-icon name="location" size="32" color="#e1251b"></u-icon>
            <text class="card-title">收货地址</text>
         </view>
         <view class="address-body">
            <view class="user-row">
               <text class="user-name">{{ address.receiver || '未设置' }}</text>
               <text class="user-phone">{{ address.phone || '' }}</text>
            </view>
            <text class="address-text">{{ fullAddress }}</text>
         </view>
      </view>

      <!-- 商品清单卡片 -->
      <view class="card goods-card">
         <view class="card-header">
            <u-icon name="shopping-cart" size="32" color="#e1251b"></u-icon>
            <text class="card-title">商品清单</text>
         </view>
         <!-- 商品列表 -->
         <view class="goods-list">
            <view class="goods-item" v-for="item in cartGoods" :key="item.id">
               <u-image :src="item.image" width="140rpx" height="140rpx" mode="aspectFill"
                  border-radius="12rpx"></u-image>
               <view class="goods-info">
                  <text class="goods-name">{{ item.name }}</text>
                  <text class="goods-remark" v-if="item.remark">{{ item.remark }}</text>
                  <view class="goods-bottom">
                     <text class="goods-price">¥{{ item.price.toFixed(2) }}</text>
                     <text class="goods-num">x{{ item.quantity }}</text>
                  </view>
               </view>
            </view>
         </view>
      </view>

      <!-- 费用明细卡片 -->
      <view class="card fee-card">
         <view class="card-header">
            <u-icon name="order" size="32" color="#e1251b"></u-icon>
            <text class="card-title">费用明细</text>
         </view>
         <view class="fee-list">
            <!-- 商品总价 -->
            <view class="fee-row">
               <text class="fee-name">商品总价</text>
               <text class="fee-num">¥{{ totalPrice.toFixed(2) }}</text>
            </view>
            <!-- 配送费 -->
            <view class="fee-row">
               <view class="fee-name-group">
                  <text class="fee-name">配送费</text>
                  <text class="fee-rule">按 {{ Number(deliveryFeePercent).toFixed(2) }}% 计算，最低 ¥{{
                     Number(minDeliveryPrice).toFixed(2) }}</text>
               </view>
               <text class="fee-num">¥{{ calculatedDeliveryFee.toFixed(2) }}</text>
            </view>
            <view class="fee-divider"></view>
            <!-- 实付金额 -->
            <view class="fee-row total">
               <text class="fee-name">实付金额</text>
               <text class="fee-num">¥{{ finalPrice.toFixed(2) }}</text>
            </view>
         </view>
      </view>

      <!-- 订单备注卡片 -->
      <view class="card remark-card">
         <view class="card-header">
            <u-icon name="edit-pen" size="32" color="#e1251b"></u-icon>
            <text class="card-title">订单备注</text>
         </view>
         <u-input type="textarea" class="remark-input" v-model="orderRemark" placeholder="请输入备注信息（选填）"
            border="none"></u-input>
      </view>

      <!-- 底部提交栏 -->
      <view class="bottom-bar">
         <view class="price-wrap">
            <text class="price-label">合计：</text>
            <text class="price-num">¥{{ finalPrice.toFixed(2) }}</text>
         </view>
         <u-button class="submit-btn" type="error" shape="circle" :disabled="!canSubmit"
            @click="handleSubmit">提交订单</u-button>
      </view>
   </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getApi, getAuth } from '@/utils/api'
import { checkShopStatus } from '@/utils/go'

// 收货地址信息
const address = ref({
   receiver: '',
   phone: '',
   detail: ''
})

// 用户信息
const userInfo = ref(null)

// 购物车商品列表
const cartGoods = ref([])
// 订单备注
const orderRemark = ref('')
// 系统配置信息
const systemConfig = ref(null)
// 最低配送费
const minDeliveryPrice = ref(0)
// 配送费
const deliveryFee = ref(0)
// 配送费百分比
const deliveryFeePercent = ref(0)
// 起送价
const startPrice = ref(0)

// 计算完整地址（使用用户地址）
const fullAddress = computed(() => {
   return address.value.detail || '未设置'
})

// 计算商品总价
const totalPrice = computed(() => {
   const total = cartGoods.value.reduce((sum, item) => {
      return sum + item.price * item.quantity
   }, 0)
   return Math.round(total * 100) / 100
})

// 计算配送费（按百分比计算，不低于最低配送费，保留2位小数）
const calculatedDeliveryFee = computed(() => {
   const fee = totalPrice.value * (deliveryFeePercent.value / 100)
   const finalFee = Math.max(fee, minDeliveryPrice.value)
   return Math.round(finalFee * 100) / 100
})

// 计算最终价格（总价+配送费，保留2位小数）
const finalPrice = computed(() => {
   let price = totalPrice.value + calculatedDeliveryFee.value
   return Math.max(0, Math.round(price * 100) / 100)
})

// 判断是否可以提交订单
const canSubmit = computed(() => {
   return cartGoods.value.length > 0
})

// 页面显示时初始化数据
onShow(() => {
   // 从本地存储获取购物车商品
   const cartData = uni.getStorageSync('cartGoods')
   if (cartData) {
      cartGoods.value = cartData.filter((item) => item.quantity > 0)
   }

   // 从本地存储获取系统配置
   const config = uni.getStorageSync('config')
   if (config) {
      systemConfig.value = config
      minDeliveryPrice.value = config.deliveryFeeMin
      deliveryFee.value = config.deliveryFeeMin
      deliveryFeePercent.value = config.deliveryFeePercent
      startPrice.value = config.startPrice
   }

   // 检查是否达到起送价
   if (totalPrice.value < startPrice.value) {
      uni.showToast({
         title: `商品总价需满¥${Number(startPrice.value).toFixed(2)}才能下单`,
         icon: 'none',
         duration: 2000
      })
      setTimeout(() => {
         uni.navigateBack()
      }, 2000)
      return
   }

   // 获取用户信息
   fetchUserInfo()
})

// 获取用户信息
const fetchUserInfo = () => {
   const auth = getAuth()
   if (!auth) {
      uni.showToast({ title: '请先登录', icon: 'none' })
      setTimeout(() => {
         uni.navigateTo({ url: '/pages/user/user/login' })
      }, 1500)
      return
   }

   uni.request({
      url: getApi('/user/user/info'),
      method: 'GET',
      header: {
         'Authorization': auth,
         'Content-Type': 'application/json'
      },
      success: (res) => {
         checkShopStatus(res.data.code, res.data.message)
         if (res.statusCode === 200 && res.data.code === 200) {
            const data = res.data.data
            userInfo.value = data
            address.value = {
               receiver: data.username || '',
               phone: data.phone || '',
               detail: data.address || ''
            }
         } else {
            uni.showToast({ title: res.data.message || '登录状态异常', icon: 'none' })
            setTimeout(() => {
               uni.navigateTo({ url: '/pages/user/user/login' })
            }, 1500)
         }
      },
      fail: (err) => {
         console.error('获取用户信息失败:', err)
      }
   })
}

// 提交订单
const handleSubmit = () => {
   if (!canSubmit.value) return

   const auth = getAuth()
   if (!auth) {
      uni.showToast({ title: '请先登录', icon: 'none' })
      return
   }

   // 构建订单数据
   const orderData = {
      goods: cartGoods.value.map(item => ({
         id: item.id,
         name: item.name,
         remark: item.remark,
         price: item.price,
         image: item.image,
         quantity: item.quantity
      })),
      totalAmount: totalPrice.value.toFixed(2),
      deliveryFee: calculatedDeliveryFee.value.toFixed(2),
      finalAmount: finalPrice.value.toFixed(2),
      remark: orderRemark.value
   }

   uni.showLoading({ title: '提交中...' })
   uni.request({
      url: getApi('/user/order/submit'),
      method: 'POST',
      data: orderData,
      header: {
         'Content-Type': 'application/json',
         'Authorization': auth
      },
      success: (res) => {
         uni.hideLoading()
         checkShopStatus(res.data.code, res.data.message)
         if (res.statusCode === 200 && res.data.code === 200) {
            uni.removeStorageSync('cartGoods')
            uni.showToast({
               title: res.data.message || '提交成功',
               icon: 'success'
            })
            setTimeout(() => {
               uni.redirectTo({
                  url: '/pages/user/order/pay?url=' + res.data.data.payUrl
               })
            }, 1500)
         } else {
            uni.showToast({
               title: res.data.message || '提交失败',
               icon: 'error'
            })
         }
      },
      fail: (err) => {
         uni.hideLoading()
         uni.showToast({ title: '网络错误', icon: 'error' })
         console.error('提交订单失败:', err)
      }
   })
}
</script>

<style scoped lang="scss">
// 页面容器
.container {
   min-height: 94vh; // 最小高度：视口高度94%
   background-color: #f5f5f5; // 背景色：浅灰色
   padding: 20rpx; // 内边距：20rpx
   padding-bottom: 140rpx; // 底部内边距：140rpx
}

// 卡片
.card {
   background-color: #fff; // 背景色：白色
   border-radius: 16rpx; // 圆角：16rpx
   padding: 24rpx; // 内边距：24rpx
   margin-bottom: 20rpx; // 底部外边距：20rpx
}

// 卡片头部
.card-header {
   display: flex; // 布局：弹性布局
   align-items: center; // 垂直对齐：居中
   gap: 12rpx; // 间距：12rpx
   margin-bottom: 20rpx; // 底部外边距：20rpx

   // 卡片标题
   .card-title {
      font-size: 30rpx; // 字体大小：30rpx
      font-weight: 600; // 字重：600（半粗）
      color: #333; // 颜色：深灰色
   }
}

// 地址卡片
.address-card {

   // 地址主体
   .address-body {

      // 用户行
      .user-row {
         display: flex; // 布局：弹性布局
         align-items: center; // 垂直对齐：居中
         gap: 20rpx; // 间距：20rpx
         margin-bottom: 12rpx; // 底部外边距：12rpx

         // 用户名
         .user-name {
            font-size: 32rpx; // 字体大小：32rpx
            font-weight: 500; // 字重：500（中等）
            color: #333; // 颜色：深灰色
         }

         // 用户电话
         .user-phone {
            font-size: 28rpx; // 字体大小：28rpx
            color: #666; // 颜色：中灰色
         }
      }

      // 地址文本
      .address-text {
         font-size: 28rpx; // 字体大小：28rpx
         color: #666; // 颜色：中灰色
         line-height: 1.5; // 行高：1.5倍
      }
   }
}

// 商品卡片
.goods-card {

   // 商品列表
   .goods-list {

      // 商品项
      .goods-item {
         display: flex; // 布局：弹性布局
         gap: 20rpx; // 间距：20rpx
         padding: 16rpx 0; // 内边距：上下16rpx 左右0
         border-bottom: 1rpx solid #f5f5f5; // 底部边框：1rpx 实线 浅灰色

         // 最后一项无边框
         &:last-child {
            border-bottom: none; // 底部边框：无
         }

         // 商品信息
         .goods-info {
            flex: 1; // 弹性：1
            display: flex; // 布局：弹性布局
            flex-direction: column; // 方向：纵向
            justify-content: space-between; // 垂直对齐：两端对齐

            // 商品名称
            .goods-name {
               font-size: 28rpx; // 字体大小：28rpx
               color: #333; // 颜色：深灰色
               line-height: 1.4; // 行高：1.4倍
            }

            // 商品备注
            .goods-remark {
               font-size: 24rpx; // 字体大小：24rpx
               color: #999; // 颜色：灰色
               margin-top: 4rpx; // 顶部外边距：4rpx
            }

            // 商品底部
            .goods-bottom {
               display: flex; // 布局：弹性布局
               justify-content: space-between; // 水平对齐：两端对齐
               align-items: center; // 垂直对齐：居中
               margin-top: 8rpx; // 顶部外边距：8rpx

               // 商品价格
               .goods-price {
                  font-size: 30rpx; // 字体大小：30rpx
                  color: #e1251b; // 颜色：主题红色
                  font-weight: 500; // 字重：500（中等）
               }

               // 商品数量
               .goods-num {
                  font-size: 26rpx; // 字体大小：26rpx
                  color: #999; // 颜色：灰色
               }
            }
         }
      }
   }
}

// 费用卡片
.fee-card {

   // 费用列表
   .fee-list {

      // 费用行
      .fee-row {
         display: flex; // 布局：弹性布局
         justify-content: space-between; // 水平对齐：两端对齐
         align-items: center; // 垂直对齐：居中
         padding: 16rpx 0; // 内边距：上下16rpx 左右0

         // 总计行
         &.total {

            // 费用名称
            .fee-name {
               font-size: 30rpx; // 字体大小：30rpx
               font-weight: 600; // 字重：600（半粗）
               color: #333; // 颜色：深灰色
            }

            // 费用数值
            .fee-num {
               font-size: 36rpx; // 字体大小：36rpx
               font-weight: 600; // 字重：600（半粗）
               color: #e1251b; // 颜色：主题红色
            }
         }

         // 费用名称组
         .fee-name-group {
            display: flex; // 布局：弹性布局
            flex-direction: column; // 方向：纵向
            gap: 4rpx; // 间距：4rpx

            // 费用名称
            .fee-name {
               font-size: 28rpx; // 字体大小：28rpx
               color: #666; // 颜色：中灰色
            }

            // 费用规则
            .fee-rule {
               font-size: 22rpx; // 字体大小：22rpx
               color: #999; // 颜色：灰色
            }
         }

         // 费用名称
         .fee-name {
            font-size: 28rpx; // 字体大小：28rpx
            color: #666; // 颜色：中灰色
         }

         // 费用数值
         .fee-num {
            font-size: 28rpx; // 字体大小：28rpx
            color: #333; // 颜色：深灰色
         }
      }

      // 费用分割线
      .fee-divider {
         height: 2rpx; // 高度：2rpx
         background-color: #e8e8e8; // 背景色：浅灰色
         margin: 10rpx 0; // 外边距：上下10rpx 左右0
      }
   }
}

// 备注卡片
.remark-card {
   margin-bottom: 0; // 底部外边距：0
}

// 底部栏
.bottom-bar {
   position: fixed; // 定位：固定定位
   bottom: 0; // 底部：0
   left: 0; // 左侧：0
   right: 0; // 右侧：0
   display: flex; // 布局：弹性布局
   justify-content: space-between; // 水平对齐：两端对齐
   align-items: center; // 垂直对齐：居中
   padding: 20rpx 30rpx; // 内边距：上20rpx 右30rpx 下20rpx 左30rpx
   background-color: #fff; // 背景色：白色
   box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05); // 阴影：0 -4rpx 20rpx 黑色5%透明度

   // 价格包装器
   .price-wrap {
      display: flex; // 布局：弹性布局
      align-items: baseline; // 垂直对齐：基线对齐
      gap: 8rpx; // 间距：8rpx

      // 价格标签
      .price-label {
         font-size: 28rpx; // 字体大小：28rpx
         color: #666; // 颜色：中灰色
      }

      // 价格数值
      .price-num {
         font-size: 40rpx; // 字体大小：40rpx
         font-weight: 600; // 字重：600（半粗）
         color: #e1251b; // 颜色：主题红色
      }
   }

   // 提交按钮
   .submit-btn {
      margin: 0; // 外边距：0
   }
}
</style>
