<template>
   <tab-navbar title="库损管理" :show-back="true"></tab-navbar>
   <view class="container">
      <!-- 页面标题 -->
      <view class="header">
         <text class="title">库损管理</text>
         <view class="add-btn" @click="addLoss">
            <u-icon name="plus" size="32" color="#fff"></u-icon>
            <text>新增库损</text>
         </view>
      </view>

      <!-- 数据概览卡片 -->
      <view class="stats-overview">
         <view class="stats-header">
            <view class="stats-title-wrap">
               <view class="title-icon" style="background: #ff4d4f15;">
                  <u-icon name="warning-fill" size="32" color="#ff4d4f"></u-icon>
               </view>
               <text class="stats-title">库损概览</text>
            </view>
            <text class="stats-subtitle">共 {{ filteredList.length }} 条记录</text>
         </view>
         <view class="stats-grid">
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #ff950015;">
                  <u-icon name="grid-fill" size="36" color="#ff9500"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ overviewData.totalLossQuantity }}</text>
                  <text class="stats-label">损耗数量</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #ff4d4f15;">
                  <u-icon name="rmb-circle-fill" size="36" color="#ff4d4f"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value loss">¥{{ overviewData.totalLossAmount }}</text>
                  <text class="stats-label">总损失</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #faad1415;">
                  <u-icon name="calendar-fill" size="36" color="#faad14"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">¥{{ overviewData.monthLossAmount }}</text>
                  <text class="stats-label">本月损失</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #1890ff15;">
                  <u-icon name="tags-fill" size="36" color="#1890ff"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ overviewData.monthLossRecords }}</text>
                  <text class="stats-label">本月记录</text>
               </view>
            </view>
         </view>
      </view>

      <!-- 搜索栏 -->
      <view class="search-bar">
         <view class="search-input-wrap">
            <u-icon name="search" size="32" color="#999"></u-icon>
            <input class="search-input" v-model="searchKeyword" placeholder="搜索商品名称" confirm-type="search"
               @confirm="handleSearch" />
         </view>
         <view class="filter-btn" @click="showFilter = true">
            <u-icon name="grid" size="32" color="#666"></u-icon>
         </view>
      </view>

      <!-- 库损列表 -->
      <view class="loss-list">
         <view v-for="item in filteredList" :key="item.id" class="loss-card">
            <!-- 卡片头部 -->
            <view class="card-header-row">
               <view class="product-info">
                  <text class="product-name">{{ item.product_name }}</text>
                  <view class="type-tag" :class="item.loss_type">
                     {{ lossTypeText[item.loss_type] }}
                  </view>
               </view>
               <text class="batch-id">批次#{{ item.batch_id }}</text>
            </view>

            <!-- 损耗统计 -->
            <view class="loss-stats-row">
               <view class="loss-stat-item">
                  <view class="stat-icon-wrap" style="background: #ff950015;">
                     <u-icon name="grid" size="28" color="#ff9500"></u-icon>
                  </view>
                  <view class="stat-info">
                     <text class="stat-value">{{ item.quantity }}<text class="unit">件</text></text>
                     <text class="stat-label">损耗数量</text>
                  </view>
               </view>
               <view class="loss-stat-item">
                  <view class="stat-icon-wrap" style="background: #ff4d4f15;">
                     <u-icon name="rmb-circle" size="28" color="#ff4d4f"></u-icon>
                  </view>
                  <view class="stat-info">
                     <text class="stat-value loss">¥{{ formatPrice(item.loss_amount) }}</text>
                     <text class="stat-label">损失金额</text>
                  </view>
               </view>
            </view>

            <!-- 损耗原因 -->
            <view class="reason-section">
               <view class="reason-header">
                  <u-icon name="info-circle" size="24" color="#999"></u-icon>
                  <text class="reason-title">损耗原因</text>
               </view>
               <text class="reason-text">{{ item.reason }}</text>
            </view>

            <!-- 备注 -->
            <view v-if="item.remark" class="remark-section">
               <view class="remark-header">
                  <u-icon name="edit-pen" size="24" color="#999"></u-icon>
                  <text class="remark-title">备注</text>
               </view>
               <text class="remark-text">{{ item.remark }}</text>
            </view>

            <!-- 底部信息 -->
            <view class="card-footer">
               <view class="operator-info">
                  <u-icon name="account" size="24" color="#999"></u-icon>
                  <text class="operator-name">{{ item.operator_name }}</text>
               </view>
               <text class="create-time">{{ formatTime(item.create_time) }}</text>
            </view>

            <!-- 操作按钮 -->
            <view class="action-row">
               <view class="action-btn edit" @click.stop="handleEdit(item)">
                  <u-icon name="edit-pen" size="24" color="#1890ff"></u-icon>
                  <text>编辑</text>
               </view>
               <view class="action-btn delete" @click.stop="handleDelete(item)">
                  <u-icon name="trash" size="24" color="#ff4d4f"></u-icon>
                  <text>删除</text>
               </view>
            </view>
         </view>
      </view>

      <!-- 空状态 -->
      <view v-if="filteredList.length === 0" class="empty-state">
         <u-icon name="warning" size="80" color="#ddd"></u-icon>
         <text class="empty-text">暂无库损数据</text>
         <text class="empty-tip">点击右上角按钮添加库损记录</text>
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
               <!-- 损耗类型 -->
               <view class="filter-card">
                  <view class="filter-card-title">
                     <view class="title-icon" style="background: #ff950015;">
                        <u-icon name="warning-fill" size="24" color="#ff9500"></u-icon>
                     </view>
                     <text>损耗类型</text>
                  </view>
                  <view class="filter-card-content">
                     <view class="filter-tag" :class="{ active: filterType === null }" @click="filterType = null">
                        <text>全部</text>
                     </view>
                     <view class="filter-tag" :class="{ active: filterType === 'damage' }"
                        @click="filterType = 'damage'">
                        <text>损坏</text>
                     </view>
                     <view class="filter-tag" :class="{ active: filterType === 'expired' }"
                        @click="filterType = 'expired'">
                        <text>过期</text>
                     </view>
                     <view class="filter-tag" :class="{ active: filterType === 'theft' }" @click="filterType = 'theft'">
                        <text>盗窃</text>
                     </view>
                     <view class="filter-tag" :class="{ active: filterType === 'error' }" @click="filterType = 'error'">
                        <text>盘点错误</text>
                     </view>
                     <view class="filter-tag" :class="{ active: filterType === 'other' }" @click="filterType = 'other'">
                        <text>其他</text>
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
const filterType = ref(null);
const loading = ref(false);

const lossTypeText = {
   damage: '损坏',
   expired: '过期',
   theft: '盗窃',
   error: '盘点错误',
   other: '其他',
};

// 库损列表数据
const lossList = ref([]);

// 库损概览数据
const overviewData = ref({
   totalLossRecords: 0,
   monthLossRecords: 0,
   totalLossQuantity: 0,
   totalLossAmount: '0.00',
   monthLossAmount: '0.00'
});

// 获取库损概览数据
const getLossOverview = async () => {
   try {
      const res = await uni.request({
         url: getApi('/admin/inventory-loss/getOverview'),
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
      console.error('获取库损概览失败:', error);
   }
};

// 计算总损耗数量
const totalQuantity = computed(() => {
   return lossList.value.reduce((sum, item) => sum + item.quantity, 0);
});

// 计算损耗类型数量
const totalTypes = computed(() => {
   const types = new Set(lossList.value.map(item => item.loss_type));
   return types.size;
});

// 计算总损失金额
const totalLossAmount = computed(() => {
   const amount = lossList.value.reduce((sum, item) => sum + parseFloat(item.loss_amount), 0);
   return amount.toFixed(2);
});

// 计算本月损失金额
const monthLossAmount = computed(() => {
   const now = new Date();
   const currentMonth = now.getMonth();
   const currentYear = now.getFullYear();
   const amount = lossList.value
      .filter(item => {
         const itemDate = new Date(item.create_time * 1000);
         return itemDate.getMonth() === currentMonth && itemDate.getFullYear() === currentYear;
      })
      .reduce((sum, item) => sum + parseFloat(item.loss_amount), 0);
   return amount.toFixed(2);
});

// 计算平均损失金额
const avgLossAmount = computed(() => {
   if (lossList.value.length === 0) return '0.00';
   const amount = lossList.value.reduce((sum, item) => sum + parseFloat(item.loss_amount), 0);
   return (amount / lossList.value.length).toFixed(2);
});

// 筛选后的库损列表
const filteredList = computed(() => {
   return lossList.value.filter(item => {
      // 类型筛选
      if (filterType.value && item.loss_type !== filterType.value) {
         return false;
      }
      // 关键词搜索
      if (searchKeyword.value) {
         const keyword = searchKeyword.value.toLowerCase();
         if (!item.product_name.toLowerCase().includes(keyword)) {
            return false;
         }
      }
      return true;
   });
});

// 格式化价格，确保两位小数
const formatPrice = (price) => {
   const num = parseFloat(price) || 0;
   return num.toFixed(2);
};

// 获取库损列表
const getLossList = async () => {
   if (loading.value) return;
   loading.value = true;

   try {
      const params = [];
      if (filterType.value) {
         params.push(`loss_type=${filterType.value}`);
      }
      if (searchKeyword.value) {
         params.push(`keyword=${encodeURIComponent(searchKeyword.value)}`);
      }
      const queryString = params.length > 0 ? '?' + params.join('&') : '';

      const res = await uni.request({
         url: getApi('/admin/inventory-loss/getLists' + queryString),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });

      const data = res.data;
      if (data.code === 200) {
         lossList.value = data.data.list || [];
      } else {
         uni.showToast({ title: data.message || '获取失败', icon: 'none' });
      }
   } catch (error) {
      console.error('获取库损列表失败:', error);
      uni.showToast({ title: '网络错误', icon: 'none' });
   } finally {
      loading.value = false;
   }
};

// 搜索
const handleSearch = () => {
   getLossList();
};

// 格式化时间
const formatTime = (timestamp) => {
   const date = new Date(timestamp * 1000);
   const year = date.getFullYear();
   const month = (date.getMonth() + 1).toString().padStart(2, '0');
   const day = date.getDate().toString().padStart(2, '0');
   return `${year}-${month}-${day}`;
};

// 新增库损
const addLoss = () => {
   uni.navigateTo({
      url: '/pages/admin/inventory-loss/edit'
   });
};

// 编辑库损
const handleEdit = (item) => {
   uni.navigateTo({
      url: `/pages/admin/inventory-loss/edit?id=${item.id}`
   });
};

// 删除库损
const handleDelete = (item) => {
   uni.showModal({
      title: '确认删除',
      content: `确定要删除 "${item.product_name}" 的库损记录吗？删除后不可恢复。`,
      confirmColor: '#e1251b',
      success: async (res) => {
         if (res.confirm) {
            try {
               const result = await uni.request({
                  url: getApi('/admin/inventory-loss/delete'),
                  method: 'POST',
                  header: {
                     'Content-Type': 'application/json',
                     'Authorization': getAuth()
                  },
                  data: { id: item.id }
               });

               if (result.data.code === 200) {
                  uni.showToast({ title: '删除成功', icon: 'success' });
                  getLossList();
               } else {
                  uni.showToast({ title: result.data.message || '删除失败', icon: 'none' });
               }
            } catch (error) {
               console.error('删除失败:', error);
               uni.showToast({ title: '网络错误', icon: 'none' });
            }
         }
      }
   });
};

// 重置筛选
const resetFilter = () => {
   filterType.value = null;
};

// 应用筛选
const applyFilter = () => {
   showFilter.value = false;
   getLossList();
};

// 页面加载时刷新数据
onShow(() => {
   getLossOverview();
   getLossList();
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

   .add-btn {
      display: flex;
      align-items: center;
      gap: 8rpx;
      background: #e1251b;
      color: #fff;
      padding: 16rpx 24rpx;
      border-radius: 30rpx;
      font-size: 26rpx;
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

               &.loss {
                  color: #ff4d4f;
               }
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

.loss-list {
   .loss-card {
      background: #fff;
      border-radius: 20rpx;
      padding: 30rpx;
      margin-bottom: 24rpx;
      box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);

      .card-header-row {
         display: flex;
         justify-content: space-between;
         align-items: flex-start;
         margin-bottom: 24rpx;

         .product-info {
            flex: 1;
            min-width: 0;

            .product-name {
               font-size: 32rpx;
               font-weight: 600;
               color: #333;
               margin-bottom: 12rpx;
               display: block;
            }

            .type-tag {
               display: inline-block;
               padding: 6rpx 16rpx;
               border-radius: 8rpx;
               font-size: 22rpx;

               &.damage {
                  background: #ff4d4f15;
                  color: #ff4d4f;
               }

               &.expired {
                  background: #ff950015;
                  color: #ff9500;
               }

               &.theft {
                  background: #722ed115;
                  color: #722ed1;
               }

               &.error {
                  background: #1890ff15;
                  color: #1890ff;
               }

               &.other {
                  background: #52c41a15;
                  color: #52c41a;
               }
            }
         }

         .batch-id {
            font-size: 26rpx;
            color: #999;
            font-weight: 500;
         }
      }

      .loss-stats-row {
         display: flex;
         gap: 16rpx;
         margin-bottom: 24rpx;

         .loss-stat-item {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 12rpx;
            padding: 16rpx 12rpx;
            background: #f8f9fa;
            border-radius: 12rpx;

            .stat-icon-wrap {
               width: 56rpx;
               height: 56rpx;
               border-radius: 12rpx;
               display: flex;
               align-items: center;
               justify-content: center;
            }

            .stat-info {
               display: flex;
               flex-direction: column;
               gap: 4rpx;

               .stat-value {
                  font-size: 26rpx;
                  font-weight: 600;
                  color: #333;

                  .unit {
                     font-size: 20rpx;
                     color: #999;
                     margin-left: 4rpx;
                  }

                  &.loss {
                     color: #ff4d4f;
                  }
               }

               .stat-label {
                  font-size: 20rpx;
                  color: #999;
               }
            }
         }
      }

      .reason-section,
      .remark-section {
         margin-bottom: 20rpx;
         padding: 20rpx;
         background: #f8f9fa;
         border-radius: 12rpx;

         .reason-header,
         .remark-header {
            display: flex;
            align-items: center;
            gap: 8rpx;
            margin-bottom: 12rpx;

            .reason-title,
            .remark-title {
               font-size: 24rpx;
               color: #666;
               font-weight: 500;
            }
         }

         .reason-text,
         .remark-text {
            font-size: 26rpx;
            color: #333;
            line-height: 1.5;
         }
      }

      .card-footer {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 20rpx 0;
         border-bottom: 1rpx solid #f0f0f0;
         margin-bottom: 20rpx;

         .operator-info {
            display: flex;
            align-items: center;
            gap: 8rpx;

            .operator-name {
               font-size: 24rpx;
               color: #666;
            }
         }

         .create-time {
            font-size: 22rpx;
            color: #999;
         }
      }

      .action-row {
         display: flex;
         gap: 20rpx;

         .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8rpx;
            padding: 20rpx 0;
            border-radius: 12rpx;
            font-size: 28rpx;
            font-weight: 500;
            background: #f5f5f5;

            &.edit {
               color: #1890ff;
            }

            &.delete {
               color: #ff4d4f;
            }

            &:active {
               opacity: 0.8;
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

// 筛选面板
.filter-panel {
   height: 100%;
   display: flex;
   flex-direction: column;
   background: #f5f5f5;

   .filter-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff;
      padding: 30rpx;
      border-bottom: 1rpx solid #eee;

      .filter-header-left {
         display: flex;
         align-items: center;
         gap: 16rpx;

         .filter-title {
            font-size: 32rpx;
            font-weight: 600;
            color: #333;
         }
      }

      .close-btn {
         width: 56rpx;
         height: 56rpx;
         display: flex;
         align-items: center;
         justify-content: center;
         background: #f5f5f5;
         border-radius: 50%;
      }
   }

   .filter-body {
      flex: 1;
      padding: 20rpx;

      .filter-card {
         background: #fff;
         border-radius: 16rpx;
         padding: 24rpx;
         margin-bottom: 20rpx;

         .filter-card-title {
            display: flex;
            align-items: center;
            gap: 16rpx;
            margin-bottom: 24rpx;
            font-size: 30rpx;
            font-weight: 600;
            color: #333;

            .title-icon {
               width: 48rpx;
               height: 48rpx;
               border-radius: 12rpx;
               display: flex;
               align-items: center;
               justify-content: center;
            }
         }

         .filter-card-content {
            display: flex;
            flex-wrap: wrap;
            gap: 16rpx;

            .filter-tag {
               display: flex;
               align-items: center;
               gap: 8rpx;
               padding: 16rpx 32rpx;
               background: #f5f5f5;
               border-radius: 8rpx;
               font-size: 26rpx;
               color: #666;
               border: 2rpx solid transparent;

               &.active {
                  background: #e1251b15;
                  color: #e1251b;
                  border-color: #e1251b;
               }
            }
         }
      }
   }

   .filter-footer {
      display: flex;
      gap: 20rpx;
      padding: 20rpx;
      background: #fff;
      border-top: 1rpx solid #eee;

      .btn {
         flex: 1;
         height: 80rpx;
         border-radius: 40rpx;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 8rpx;
         font-size: 28rpx;

         &.reset {
            background: #f5f5f5;
            color: #666;
         }

         &.confirm {
            background: #e1251b;
            color: #fff;
         }
      }
   }
}
</style>
