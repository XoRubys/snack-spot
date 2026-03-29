<template>
   <!-- 我的页面容器 -->
   <view class="container">
      <!-- 用户信息头部 -->
      <view class="user-header">
         <!-- 用户基本信息 -->
         <view class="user-info">
            <!-- 用户头像 -->
            <view class="avatar">
               <u-icon name="account-fill" size="80" color="#fff"></u-icon>
            </view>
            <!-- 用户详情 -->
            <view class="user-detail">
               <text class="username">{{ userInfo.username || '未登录' }}</text>
               <view class="user-sub-info">
                  <text class="user-phone" v-if="userInfo.phone">{{ maskPhone(userInfo.phone) }}</text>
                  <text class="user-level">{{ userInfo.level === 'admin' ? '管理员' : '普通用户' }}</text>
               </view>
            </view>
         </view>
         <!-- 用户消费统计 -->
         <view class="user-stats">
            <view class="stat-item">
               <text class="stat-value">¥{{ formatPrice(monthlySpend) }}</text>
               <text class="stat-label">本月消费</text>
            </view>
            <view class="stat-divider"></view>
            <view class="stat-item">
               <text class="stat-value">¥{{ formatPrice(weeklySpend) }}</text>
               <text class="stat-label">本周消费</text>
            </view>
            <view class="stat-divider"></view>
            <view class="stat-item">
               <text class="stat-value">¥{{ formatPrice(todaySpend) }}</text>
               <text class="stat-label">今日消费</text>
            </view>
         </view>
      </view>

      <!-- 订单区域 -->
      <view class="order-section">
         <!-- 订单区域头部 -->
         <view class="section-header">
            <text class="section-title">我的订单</text>
            <text class="view-all" @click="viewAllOrders">查看全部 ></text>
         </view>
         <!-- 订单类型列表 -->
         <view class="order-types">
            <view class="order-type-item" v-for="(item, index) in orderTypes" :key="index" @click="viewAllOrders">
               <view class="type-icon">
                  <u-icon :name="item.icon" size="48" color="#e1251b"></u-icon>
               </view>
               <text class="type-name">{{ item.name }}</text>
               <text class="type-count" v-if="item.showCount && item.count > 0">{{ item.count }}</text>
            </view>
         </view>
      </view>

      <!-- 菜单区域1：用户资产 -->
      <view class="menu-section">
         <!-- 我的积分 -->
         <view class="menu-item" @click="viewPoints">
            <view class="menu-left">
               <u-icon name="red-packet" size="40" color="#e1251b"></u-icon>
               <text class="menu-name">我的积分</text>
            </view>
            <view class="menu-right">
               <text class="menu-value">{{ userInfo.points || 0 }} 分</text>
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>
         <!-- 收货地址 -->
         <view class="menu-item" @click="goToAddress">
            <view class="menu-left">
               <u-icon name="map" size="40" color="#e1251b"></u-icon>
               <text class="menu-name">收货地址</text>
            </view>
            <view class="menu-right">
               <text class="menu-value">{{ userInfo.dormitory || '未设置' }}</text>
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>

      </view>

      <!-- 菜单区域2：服务支持 -->
      <view class="menu-section">
         <!-- 管理控制台（仅管理员可见） -->
         <view class="menu-item" v-if="userInfo.level === 'admin'" @click="goToAdmin">
            <view class="menu-left">
               <u-icon name="setting-fill" size="40" color="#e1251b"></u-icon>
               <text class="menu-name">管理控制台</text>
            </view>
            <view class="menu-right">
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>
         <!-- 联系客服 -->
         <view class="menu-item" @click="contactService">
            <view class="menu-left">
               <u-icon name="server-man" size="40" color="#e1251b"></u-icon>
               <text class="menu-name">联系客服</text>
            </view>
            <view class="menu-right">
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>
         <!-- AI助理 -->
         <view class="menu-item" @click="goToChat">
            <view class="menu-left">
               <u-icon name="android-fill" size="40" color="#e1251b"></u-icon>
               <text class="menu-name">智能助理</text>
            </view>
            <view class="menu-right">
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>
         <!-- 关于我们 -->
         <view class="menu-item" @click="goToAbout">
            <view class="menu-left">
               <u-icon name="info-circle" size="40" color="#e1251b"></u-icon>
               <text class="menu-name">关于我们</text>
            </view>
            <view class="menu-right">
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>
      </view>

   </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getApi, getAuth } from '@/utils/api'
import { goToLogin, checkShopStatus } from '@/utils/go'



// 用户信息
const userInfo = ref({
   id: '',
   username: '',
   phone: '',
   dormitory: '',
   points: 0,
   level: 'user'
})

// 订单类型列表
const orderTypes = ref([
   { name: '待付款', icon: 'clock', status: 'pending', count: 0, showCount: true },
   { name: '已付款', icon: 'checkmark-circle', status: 'paid', count: 0, showCount: true },
   { name: '已完成', icon: 'bag', status: 'completed', count: 0, showCount: false },
   { name: '已取消', icon: 'close-circle', status: 'cancelled', count: 0, showCount: false }
])

// 消费统计数据
const monthlySpend = ref(0)    // 本月消费
const weeklySpend = ref(0)     // 本周消费
const todaySpend = ref(0)      // 今日消费

// 页面显示时获取用户信息
onShow(() => {
   fetchUserInfo()
})

// 获取用户信息
const fetchUserInfo = async () => {

   try {
      const response = await uni.request({
         url: getApi('/user/user/info'),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      })
      const resData = response.data
      checkShopStatus(resData.code, resData.message)
      if (response.statusCode === 200 && resData.code === 200) {
         userInfo.value = resData.data
         if (resData.data.spend) {
            todaySpend.value = resData.data.spend.today || 0
            weeklySpend.value = resData.data.spend.week || 0
            monthlySpend.value = resData.data.spend.month || 0
         }
         // 更新订单数量
         if (resData.data.orderCount) {
            orderTypes.value = orderTypes.value.map(item => {
               if (item.status === 'pending') {
                  return { ...item, count: resData.data.orderCount.pending || 0 }
               }
               if (item.status === 'paid') {
                  return { ...item, count: resData.data.orderCount.paid || 0 }
               }
               return item
            })
         }
      } else if (response.statusCode === 401 && resData.code === 401) {
         uni.removeStorageSync('userInfo')
         uni.showToast({
            title: resData.message || '登录已过期，请重新登录',
            icon: 'none',
            duration: 1500,
            success: () => {
               goToLogin(1500)
            }
         })
      }
   } catch (error) {
      console.error('获取用户信息失败:', error)
   }
}

// 手机号脱敏处理
const maskPhone = (phone) => {
   if (!phone) return '未绑定'
   return String(phone).replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

// 格式化价格，确保两位小数
const formatPrice = (price) => {
   const num = parseFloat(price) || 0
   return num.toFixed(2)
}

// 查看全部订单
const viewAllOrders = () => {
   uni.switchTab({
      url: '/pages/user/order/record'
   })
}

// 查看积分（功能开发中）
const viewPoints = () => {
   uni.showToast({
      title: '积分功能开发中',
      icon: 'none'
   })
}

// 跳转到地址管理
const goToAddress = () => {
   uni.navigateTo({
      url: '/pages/user/user/edit'
   })
}

// 跳转到管理控制台（仅管理员）
const goToAdmin = () => {
   uni.navigateTo({
      url: '/pages/admin/dashboard'
   })
}

// 联系客服
const contactService = () => {
   uni.showModal({
      title: '联系客服',
      content: '客服电话：xxx xxxx xxxx\n客服邮箱：youremail@example.com',
      showCancel: false  // 不显示取消按钮
   })
}

// 跳转到AI助理页面
const goToChat = () => {
   uni.showToast({
      title: '智能助理开发中...',
      icon: 'none'
   })
}

// 关于我们
const goToAbout = () => {
   uni.navigateTo({
      url: '/pages/user/about/index'
   })
}
</script>

<style scoped lang="scss">
// 页面容器
.container {
   min-height: 94vh; // 最小高度：视口高度94%
   background-color: #f5f5f5; // 背景色：浅灰色
   padding-bottom: 40rpx; // 底部内边距：40rpx
}

// 用户头部区域
.user-header {
   background: linear-gradient(135deg, #e1251b 0%, #ff6b6b 100%); // 背景：红色渐变
   padding: 60rpx 30rpx 40rpx; // 内边距：上60rpx 右30rpx 下40rpx 左30rpx

   // 用户信息
   .user-info {
      display: flex; // 布局：弹性布局
      align-items: center; // 垂直对齐：居中
      gap: 24rpx; // 间距：24rpx
      margin-bottom: 40rpx; // 底部外边距：40rpx

      // 头像
      .avatar {
         width: 120rpx; // 宽度：120rpx
         height: 120rpx; // 高度：120rpx
         border-radius: 60rpx; // 圆角：60rpx（圆形）
         background-color: rgba(255, 255, 255, 0.2); // 背景色：白色20%透明度
         display: flex; // 布局：弹性布局
         align-items: center; // 垂直对齐：居中
         justify-content: center; // 水平对齐：居中
      }

      // 用户详情
      .user-detail {
         display: flex; // 布局：弹性布局
         flex-direction: column; // 方向：纵向
         gap: 8rpx; // 间距：8rpx

         // 用户名
         .username {
            font-size: 40rpx; // 字体大小：40rpx
            font-weight: 600; // 字重：600（半粗）
            color: #fff; // 颜色：白色
         }

         // 用户子信息
         .user-sub-info {
            display: flex; // 布局：弹性布局
            align-items: center; // 垂直对齐：居中
            gap: 16rpx; // 间距：16rpx

            // 手机号
            .user-phone {
               font-size: 24rpx; // 字体大小：24rpx
               color: rgba(255, 255, 255, 0.8); // 颜色：白色80%透明度
            }

            // 用户等级
            .user-level {
               font-size: 22rpx; // 字体大小：22rpx
               color: rgba(255, 255, 255, 0.9); // 颜色：白色90%透明度
               background-color: rgba(255, 255, 255, 0.2); // 背景色：白色20%透明度
               padding: 4rpx 12rpx; // 内边距：上4rpx 右12rpx 下4rpx 左12rpx
               border-radius: 8rpx; // 圆角：8rpx
            }
         }
      }
   }

   // 用户统计
   .user-stats {
      display: flex; // 布局：弹性布局
      align-items: center; // 垂直对齐：居中
      justify-content: space-around; // 水平对齐：均匀分布
      padding: 0 20rpx; // 内边距：左右20rpx

      // 统计项
      .stat-item {
         display: flex; // 布局：弹性布局
         flex-direction: column; // 方向：纵向
         align-items: center; // 垂直对齐：居中
         gap: 8rpx; // 间距：8rpx
         flex: 1; // 弹性：1（平均分配）

         // 统计值
         .stat-value {
            font-size: 32rpx; // 字体大小：32rpx
            font-weight: 600; // 字重：600（半粗）
            color: #fff; // 颜色：白色
         }

         // 统计标签
         .stat-label {
            font-size: 22rpx; // 字体大小：22rpx
            color: rgba(255, 255, 255, 0.8); // 颜色：白色80%透明度
         }
      }

      // 分割线
      .stat-divider {
         width: 2rpx; // 宽度：2rpx
         height: 50rpx; // 高度：50rpx
         background-color: rgba(255, 255, 255, 0.3); // 背景色：白色30%透明度
         flex-shrink: 0; // 不收缩
      }
   }
}

// 订单区域
.order-section {
   background-color: #fff; // 背景色：白色
   margin: 24rpx; // 外边距：24rpx
   border-radius: 16rpx; // 圆角：16rpx
   padding: 30rpx; // 内边距：30rpx

   // 区域头部
   .section-header {
      display: flex; // 布局：弹性布局
      justify-content: space-between; // 水平对齐：两端对齐
      align-items: center; // 垂直对齐：居中
      margin-bottom: 30rpx; // 底部外边距：30rpx

      // 区域标题
      .section-title {
         font-size: 32rpx; // 字体大小：32rpx
         font-weight: 600; // 字重：600（半粗）
         color: #333; // 颜色：深灰色
      }

      // 查看全部
      .view-all {
         font-size: 26rpx; // 字体大小：26rpx
         color: #999; // 颜色：灰色
      }
   }

   // 订单类型
   .order-types {
      display: flex; // 布局：弹性布局
      justify-content: space-between; // 水平对齐：两端对齐

      // 订单类型项
      .order-type-item {
         display: flex; // 布局：弹性布局
         flex-direction: column; // 方向：纵向
         align-items: center; // 垂直对齐：居中
         gap: 12rpx; // 间距：12rpx
         position: relative; // 定位：相对定位

         // 类型图标
         .type-icon {
            width: 80rpx; // 宽度：80rpx
            height: 80rpx; // 高度：80rpx
            border-radius: 40rpx; // 圆角：40rpx（圆形）
            background-color: #fff5f5; // 背景色：浅粉色
            display: flex; // 布局：弹性布局
            align-items: center; // 垂直对齐：居中
            justify-content: center; // 水平对齐：居中
         }

         // 类型名称
         .type-name {
            font-size: 24rpx; // 字体大小：24rpx
            color: #666; // 颜色：中灰色
         }

         // 类型数量
         .type-count {
            position: absolute; // 定位：绝对定位
            top: -8rpx; // 顶部：-8rpx
            right: -8rpx; // 右侧：-8rpx
            min-width: 32rpx; // 最小宽度：32rpx
            height: 32rpx; // 高度：32rpx
            background-color: #e1251b; // 背景色：主题红色
            color: #fff; // 颜色：白色
            font-size: 20rpx; // 字体大小：20rpx
            border-radius: 16rpx; // 圆角：16rpx（圆形）
            display: flex; // 布局：弹性布局
            align-items: center; // 垂直对齐：居中
            justify-content: center; // 水平对齐：居中
            padding: 0 8rpx; // 内边距：左右8rpx
         }
      }
   }
}

// 菜单区域
.menu-section {
   background-color: #fff; // 背景色：白色
   margin: 0 24rpx 24rpx; // 外边距：上0 右24rpx 下24rpx 左24rpx
   border-radius: 16rpx; // 圆角：16rpx
   overflow: hidden; // 溢出：隐藏

   // 菜单项
   .menu-item {
      display: flex; // 布局：弹性布局
      justify-content: space-between; // 水平对齐：两端对齐
      align-items: center; // 垂直对齐：居中
      padding: 30rpx; // 内边距：30rpx
      border-bottom: 1rpx solid #f5f5f5; // 底部边框：1rpx 实线 浅灰色

      // 最后一项无边框
      &:last-child {
         border-bottom: none; // 底部边框：无
      }

      // 菜单左侧
      .menu-left {
         display: flex; // 布局：弹性布局
         align-items: center; // 垂直对齐：居中
         gap: 20rpx; // 间距：20rpx

         // 菜单名称
         .menu-name {
            font-size: 30rpx; // 字体大小：30rpx
            color: #333; // 颜色：深灰色
         }
      }

      // 菜单右侧
      .menu-right {
         display: flex; // 布局：弹性布局
         align-items: center; // 垂直对齐：居中
         gap: 12rpx; // 间距：12rpx

         // 菜单值
         .menu-value {
            font-size: 28rpx; // 字体大小：28rpx
            color: #999; // 颜色：灰色
         }
      }
   }
}
</style>
