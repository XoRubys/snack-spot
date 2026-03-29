<template>
   <tab-navbar title="用户管理" :show-back="true"></tab-navbar>
   <view class="container">
      <!-- 页面标题 -->
      <view class="header">
         <text class="title">用户管理</text>
      </view>

      <!-- 数据概览卡片 -->
      <view class="stats-overview">
         <view class="stats-header">
            <view class="stats-title-wrap">
               <view class="title-icon" style="background: #1890ff15;">
                  <u-icon name="account-fill" size="32" color="#1890ff"></u-icon>
               </view>
               <text class="stats-title">用户概览</text>
            </view>
            <text class="stats-subtitle">共 {{ overviewData.totalUsers }} 位用户</text>
         </view>
         <view class="stats-grid">
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #52c41a15;">
                  <u-icon name="checkmark-circle-fill" size="36" color="#52c41a"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ overviewData.activeUsers }}</text>
                  <text class="stats-label">活跃用户</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #ff950015;">
                  <u-icon name="plus-circle-fill" size="36" color="#ff9500"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ overviewData.newUsers }}</text>
                  <text class="stats-label">本月新增</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #e1251b15;">
                  <u-icon name="clock-fill" size="36" color="#e1251b"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">¥{{ overviewData.weekSpend }}</text>
                  <text class="stats-label">本周消费</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #722ed115;">
                  <u-icon name="rmb-circle-fill" size="36" color="#722ed1"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">¥{{ overviewData.monthSpend }}</text>
                  <text class="stats-label">本月消费</text>
               </view>
            </view>
         </view>
      </view>

      <!-- 搜索栏 -->
      <view class="search-bar">
         <view class="search-input-wrap">
            <u-icon name="search" size="32" color="#999"></u-icon>
            <input class="search-input" v-model="searchKeyword" placeholder="搜索用户名/手机号" confirm-type="search"
               @confirm="handleSearch" />
         </view>
         <view class="filter-btn" @click="showFilter = true">
            <u-icon name="grid" size="32" color="#666"></u-icon>
         </view>
      </view>

      <!-- 统计信息 -->
      <view class="stats-bar">
         <text class="stats-text">共 {{ filteredList.length }} 位用户</text>
         <text class="stats-text">正常 {{ activeCount }} 位</text>
         <text class="stats-text">封禁 {{ inactiveCount }} 位</text>
      </view>

      <!-- 用户列表 -->
      <view class="user-list">
         <view v-for="item in filteredList" :key="item.id" class="user-item">
            <view class="user-avatar">
               <u-icon name="account-fill" size="48" :color="item.status === 'active' ? '#1890ff' : '#999'"></u-icon>
            </view>
            <view class="user-info">
               <view class="user-header">
                  <text class="user-name">{{ item.username }}</text>
                  <view class="status-tag" :class="item.status">
                     {{ item.status === 'active' ? '正常' : '封禁' }}
                  </view>
                  <view class="level-tag" :class="item.level">
                     {{ item.level === 'admin' ? '管理员' : '普通用户' }}
                  </view>
               </view>
               <!-- 消费额统计 -->
               <view class="user-spend">
                  <view class="spend-item">
                     <text class="spend-label">本周消费</text>
                     <text class="spend-value">¥{{ formatPrice(item.weekSpend) }}</text>
                  </view>
                  <view class="spend-divider"></view>
                  <view class="spend-item">
                     <text class="spend-label">本月消费</text>
                     <text class="spend-value">¥{{ formatPrice(item.monthSpend) }}</text>
                  </view>
               </view>
               <view class="user-meta">
                  <text class="meta-item">
                     <u-icon name="phone-fill" size="20" color="#999"></u-icon>
                     {{ item.phone }}
                  </text>
                  <text class="meta-item">
                     <u-icon name="home-fill" size="20" color="#999"></u-icon>
                     {{ item.dormitory }}号寝室
                  </text>
               </view>
               <view class="user-time">
                  <text>注册: {{ formatTime(item.createTime) }}</text>
               </view>
            </view>
            <view class="user-action">
               <view class="action-btn" :class="item.status === 'active' ? 'ban' : 'restore'"
                  @click="toggleUserStatus(item)">
                  <u-icon :name="item.status === 'active' ? 'minus-circle-fill' : 'reload'" size="24"
                     color="#fff"></u-icon>
                  <text>{{ item.status === 'active' ? '禁用' : '恢复' }}</text>
               </view>
            </view>
         </view>
      </view>

      <!-- 空状态 -->
      <view v-if="filteredList.length === 0" class="empty-state">
         <u-icon name="account" size="80" color="#ddd"></u-icon>
         <text class="empty-text">暂无用户数据</text>
         <text class="empty-tip">尝试调整搜索条件</text>
      </view>

      <!-- 筛选弹窗 -->
      <u-popup v-model="showFilter" mode="right" width="70%">
         <view class="filter-panel">
            <view class="filter-header">
               <view class="filter-header-left">
                  <u-icon name="filter-fill" size="36" color="#333"></u-icon>
                  <text class="filter-title">筛选条件</text>
               </view>
               <view class="close-btn" @click="showFilter = false">
                  <u-icon name="close" size="32" color="#999"></u-icon>
               </view>
            </view>
            <scroll-view class="filter-body" scroll-y>
               <!-- 用户状态 -->
               <view class="filter-section">
                  <view class="section-header">
                     <view class="section-icon" style="background: #52c41a15;">
                        <u-icon name="checkmark-circle" size="28" color="#52c41a"></u-icon>
                     </view>
                     <text class="section-label">用户状态</text>
                  </view>
                  <view class="filter-options">
                     <view class="filter-option" :class="{ active: filterStatus === null }"
                        @click="filterStatus = null">
                        <text>全部</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterStatus === 'active' }"
                        @click="filterStatus = 'active'">
                        <text>正常</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterStatus === 'inactive' }"
                        @click="filterStatus = 'inactive'">
                        <text>封禁</text>
                     </view>
                  </view>
               </view>
               <!-- 用户等级 -->
               <view class="filter-section">
                  <view class="section-header">
                     <view class="section-icon" style="background: #ff950015;">
                        <u-icon name="star-fill" size="28" color="#ff9500"></u-icon>
                     </view>
                     <text class="section-label">用户等级</text>
                  </view>
                  <view class="filter-options">
                     <view class="filter-option" :class="{ active: filterLevel === null }" @click="filterLevel = null">
                        <text>全部</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterLevel === 'user' }"
                        @click="filterLevel = 'user'">
                        <text>普通用户</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterLevel === 'admin' }"
                        @click="filterLevel = 'admin'">
                        <text>管理员</text>
                     </view>
                  </view>
               </view>
            </scroll-view>
            <view class="filter-footer">
               <view class="btn reset" @click="resetFilter">
                  <u-icon name="reload" size="24" color="#666"></u-icon>
                  <text>重置</text>
               </view>
               <view class="btn confirm" @click="applyFilter">
                  <u-icon name="checkmark" size="24" color="#fff"></u-icon>
                  <text>确定</text>
               </view>
            </view>
         </view>
      </u-popup>
   </view>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onShow } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';

const searchKeyword = ref('');
const showFilter = ref(false);
const filterStatus = ref(null);
const filterLevel = ref(null);
const loading = ref(false);

// 用户列表数据
const userList = ref([]);

// 用户概览数据
const overviewData = ref({
   totalUsers: 0,
   activeUsers: 0,
   newUsers: 0,
   weekSpend: '0.00',
   monthSpend: '0.00'
});

// 获取用户概览数据
const getUserOverview = async () => {
   try {
      const res = await uni.request({
         url: getApi('/admin/user/getOverview'),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });
      const data = res.data;
      if (data.code === 200) {
         overviewData.value = data.data;
      }
   } catch (error) {
      console.error('获取用户概览失败:', error);
   }
};

// 计算正常用户数量
const activeCount = computed(() => {
   return userList.value.filter(item => item.status === 'active').length;
});

// 计算封禁用户数量
const inactiveCount = computed(() => {
   return userList.value.filter(item => item.status === 'inactive').length;
});

// 筛选后的用户列表
const filteredList = computed(() => {
   return userList.value;
});

// 获取用户列表
const getUserList = async () => {
   if (loading.value) return;
   loading.value = true;

   try {
      const params = [];
      if (filterStatus.value) {
         params.push(`status=${filterStatus.value}`);
      }
      if (filterLevel.value) {
         params.push(`level=${filterLevel.value}`);
      }
      if (searchKeyword.value) {
         params.push(`keyword=${encodeURIComponent(searchKeyword.value)}`);
      }
      const queryString = params.length > 0 ? '?' + params.join('&') : '';

      const res = await uni.request({
         url: getApi('/admin/user/getLists' + queryString),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });

      const data = res.data;
      if (data.code === 200) {
         userList.value = data.data.list || [];
      } else {
         uni.showToast({ title: data.message || '获取失败', icon: 'none' });
      }
   } catch (error) {
      console.error('获取用户列表失败:', error);
      uni.showToast({ title: '网络错误', icon: 'none' });
   } finally {
      loading.value = false;
   }
};

// 搜索用户
const handleSearch = () => {
   getUserList();
};

// 格式化价格
const formatPrice = (price) => {
   const num = parseFloat(price) || 0;
   return num.toFixed(2);
};

// 格式化时间
const formatTime = (timestamp) => {
   const date = new Date(timestamp * 1000);
   const year = date.getFullYear();
   const month = (date.getMonth() + 1).toString().padStart(2, '0');
   const day = date.getDate().toString().padStart(2, '0');
   return `${year}-${month}-${day}`;
};

// 切换用户状态（禁用/恢复）
const toggleUserStatus = async (item) => {
   const action = item.status === 'active' ? '禁用' : '恢复';
   uni.showModal({
      title: '提示',
      content: `确定要${action}用户 "${item.username}" 吗？`,
      success: async (res) => {
         if (res.confirm) {
            try {
               const result = await uni.request({
                  url: getApi('/admin/user/toggleStatus'),
                  method: 'POST',
                  header: {
                     'Content-Type': 'application/json',
                     'Authorization': getAuth()
                  },
                  data: { id: item.id }
               });

               if (result.data.code === 200) {
                  uni.showToast({ title: `${action}成功`, icon: 'success' });
                  getUserList();
               } else {
                  uni.showToast({ title: result.data.message || '操作失败', icon: 'none' });
               }
            } catch (error) {
               console.error('操作失败:', error);
               uni.showToast({ title: '网络错误', icon: 'none' });
            }
         }
      }
   });
};

// 重置筛选
const resetFilter = () => {
   filterStatus.value = null;
   filterLevel.value = null;
};

// 应用筛选
const applyFilter = () => {
   showFilter.value = false;
   getUserList();
};

// 页面加载时刷新数据
onShow(() => {
   getUserOverview();
   getUserList();
});
</script>

<style lang="scss" scoped>
.container {
   min-height: 94vh;
   background: #f5f5f5;
   padding: 20rpx;
}

.header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   padding: 20rpx 10rpx;
   margin-bottom: 20rpx;

   .title {
      font-size: 36rpx;
      font-weight: 600;
      color: #333;
   }
}

// 数据概览样式
.stats-overview {
   background: #fff;
   border-radius: 20rpx;
   margin: 0 10rpx 30rpx;
   padding: 30rpx;
   box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);

   .stats-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30rpx;

      .stats-title-wrap {
         display: flex;
         align-items: center;
         gap: 12rpx;

         .title-icon {
            width: 56rpx;
            height: 56rpx;
            border-radius: 14rpx;
            display: flex;
            align-items: center;
            justify-content: center;
         }

         .stats-title {
            font-size: 32rpx;
            font-weight: 600;
            color: #333;
         }
      }

      .stats-subtitle {
         font-size: 26rpx;
         color: #999;
      }
   }

   .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20rpx;

      .stats-item {
         display: flex;
         align-items: center;
         gap: 20rpx;
         padding: 24rpx;
         background: #f8f9fa;
         border-radius: 16rpx;

         .stats-icon-wrap {
            width: 72rpx;
            height: 72rpx;
            border-radius: 16rpx;
            display: flex;
            align-items: center;
            justify-content: center;
         }

         .stats-info {
            display: flex;
            flex-direction: column;
            gap: 8rpx;

            .stats-value {
               font-size: 32rpx;
               font-weight: 700;
               color: #333;
            }

            .stats-label {
               font-size: 24rpx;
               color: #999;
            }
         }
      }
   }
}

.search-bar {
   display: flex;
   gap: 16rpx;
   margin-bottom: 20rpx;

   .search-input-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 12rpx;
      padding: 0 24rpx;
      height: 80rpx;

      .search-input {
         flex: 1;
         margin-left: 16rpx;
         font-size: 28rpx;
         color: #333;
      }
   }

   .filter-btn {
      width: 80rpx;
      height: 80rpx;
      background: #fff;
      border-radius: 12rpx;
      display: flex;
      align-items: center;
      justify-content: center;
   }
}

.stats-bar {
   display: flex;
   gap: 24rpx;
   padding: 0 10rpx 20rpx;

   .stats-text {
      font-size: 26rpx;
      color: #666;
   }
}

.user-list {
   .user-item {
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 16rpx;
      padding: 24rpx;
      margin-bottom: 20rpx;

      .user-avatar {
         width: 88rpx;
         height: 88rpx;
         background: #f5f5f5;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         margin-right: 20rpx;
         flex-shrink: 0;
      }

      .user-info {
         flex: 1;
         min-width: 0;

         .user-header {
            display: flex;
            align-items: center;
            gap: 12rpx;
            margin-bottom: 12rpx;

            .user-name {
               font-size: 30rpx;
               font-weight: 600;
               color: #333;
            }

            .status-tag {
               padding: 4rpx 12rpx;
               border-radius: 8rpx;
               font-size: 22rpx;

               &.active {
                  background: #52c41a15;
                  color: #52c41a;
               }

               &.inactive {
                  background: #ff4d4f15;
                  color: #ff4d4f;
               }
            }

            .level-tag {
               padding: 4rpx 12rpx;
               border-radius: 8rpx;
               font-size: 22rpx;

               &.admin {
                  background: #ff950015;
                  color: #ff9500;
               }

               &.user {
                  background: #1890ff15;
                  color: #1890ff;
               }
            }
         }

         .user-spend {
            display: flex;
            align-items: center;
            gap: 16rpx;
            margin-bottom: 12rpx;
            padding: 12rpx 16rpx;
            background: #f8f9fa;
            border-radius: 12rpx;

            .spend-item {
               display: flex;
               flex-direction: column;
               gap: 4rpx;

               .spend-label {
                  font-size: 22rpx;
                  color: #999;
               }

               .spend-value {
                  font-size: 26rpx;
                  font-weight: 600;
                  color: #e1251b;
               }
            }

            .spend-divider {
               width: 2rpx;
               height: 40rpx;
               background: #e8e8e8;
            }
         }

         .user-meta {
            display: flex;
            gap: 24rpx;
            margin-bottom: 8rpx;

            .meta-item {
               display: flex;
               align-items: center;
               gap: 8rpx;
               font-size: 24rpx;
               color: #666;
            }
         }

         .user-time {
            font-size: 22rpx;
            color: #999;
         }
      }

      .user-action {
         margin-left: 16rpx;

         .action-btn {
            display: flex;
            align-items: center;
            gap: 8rpx;
            padding: 16rpx 24rpx;
            border-radius: 30rpx;
            font-size: 24rpx;
            color: #fff;

            &.ban {
               background: #ff4d4f;
            }

            &.restore {
               background: #52c41a;
            }

            &:active {
               opacity: 0.9;
            }
         }
      }
   }
}

.empty-state {
   display: flex;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   padding: 100rpx 0;

   .empty-text {
      font-size: 30rpx;
      color: #666;
      margin-top: 20rpx;
   }

   .empty-tip {
      font-size: 26rpx;
      color: #999;
      margin-top: 12rpx;
   }
}

// 筛选弹窗样式
.filter-panel {
   height: 100vh;
   display: flex;
   flex-direction: column;
   background: #fff;

   .filter-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 30rpx;
      border-bottom: 1rpx solid #f5f5f5;

      .filter-header-left {
         display: flex;
         align-items: center;
         gap: 16rpx;
      }

      .filter-title {
         font-size: 32rpx;
         font-weight: 600;
         color: #333;
      }

      .close-btn {
         padding: 10rpx;
      }
   }

   .filter-body {
      flex: 1;
      padding: 30rpx;
   }

   .filter-section {
      margin-bottom: 40rpx;

      .section-header {
         display: flex;
         align-items: center;
         gap: 16rpx;
         margin-bottom: 20rpx;

         .section-icon {
            width: 48rpx;
            height: 48rpx;
            border-radius: 12rpx;
            display: flex;
            align-items: center;
            justify-content: center;
         }

         .section-label {
            font-size: 30rpx;
            font-weight: 600;
            color: #333;
         }
      }

      .filter-options {
         display: flex;
         flex-wrap: wrap;
         gap: 16rpx;
         padding-left: 64rpx;

         .filter-option {
            display: flex;
            align-items: center;
            gap: 8rpx;
            padding: 16rpx 24rpx;
            background: #f5f5f5;
            border-radius: 12rpx;
            font-size: 26rpx;
            color: #666;

            &.active {
               background: #fff5f5;
               color: #e1251b;
               border: 2rpx solid #e1251b;
            }
         }
      }
   }

   .filter-footer {
      display: flex;
      gap: 20rpx;
      padding: 30rpx;
      border-top: 1rpx solid #f5f5f5;

      .btn {
         flex: 1;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 12rpx;
         height: 88rpx;
         border-radius: 44rpx;
         font-size: 30rpx;

         &.reset {
            background: #f5f5f5;
            color: #666;
         }

         &.confirm {
            background: linear-gradient(135deg, #e1251b 0%, #ff4d4f 100%);
            color: #fff;
         }
      }
   }
}
</style>
