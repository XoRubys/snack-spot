<template>
   <tab-navbar title="库存管理" :show-back="true"></tab-navbar>
   <view class="container">
      <!-- 页面标题 -->
      <view class="header">
         <text class="title">库存管理</text>
         <view class="add-btn" @click="addInventory">
            <u-icon name="plus" size="32" color="#fff"></u-icon>
            <text>新增进货</text>
         </view>
      </view>

      <!-- 搜索栏 -->
      <view class="search-bar">
         <view class="search-input-wrap">
            <u-icon name="search" size="32" color="#999"></u-icon>
            <input class="search-input" v-model="searchKeyword" placeholder="搜索商品名称/订单号/快递单号" confirm-type="search"
               @confirm="handleSearch" />
         </view>
         <view class="filter-btn" @click="showFilter = true">
            <u-icon name="grid" size="32" color="#666"></u-icon>
         </view>
      </view>

      <!-- 数据概览卡片 -->
      <view class="stats-overview">
         <view class="stats-header">
            <view class="stats-title-wrap">
               <u-icon name="pie-chart-fill" size="36" color="#e1251b"></u-icon>
               <text class="stats-title">数据概览</text>
            </view>
            <text class="stats-subtitle">共 {{ filteredList.length }} 个批次</text>
         </view>
         <view class="stats-grid">
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #e1251b15;">
                  <u-icon name="grid-fill" size="36" color="#e1251b"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ monthProductQuantity }}</text>
                  <text class="stats-label">本月进货量</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #722ed115;">
                  <u-icon name="grid-fill" size="36" color="#722ed1"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ totalQuantity }}</text>
                  <text class="stats-label">本月批次数</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #52c41a15;">
                  <u-icon name="bag-fill" size="36" color="#52c41a"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ totalProductRemaining }}</text>
                  <text class="stats-label">商品剩余</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #faad1415;">
                  <u-icon name="checkmark-circle-fill" size="36" color="#faad14"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">{{ totalRemaining }}</text>
                  <text class="stats-label">有货批次</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #ff950015;">
                  <u-icon name="rmb-circle-fill" size="36" color="#ff9500"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">¥{{ totalCost }}</text>
                  <text class="stats-label">库存成本</text>
               </view>
            </view>
            <view class="stats-item">
               <view class="stats-icon-wrap" style="background: #1890ff15;">
                  <u-icon name="red-packet-fill" size="36" color="#1890ff"></u-icon>
               </view>
               <view class="stats-info">
                  <text class="stats-value">¥{{ totalProfit }}</text>
                  <text class="stats-label">预计利润</text>
               </view>
            </view>
         </view>
      </view>

      <!-- 库存列表 -->
      <view class="inventory-list">
         <view v-for="item in filteredList" :key="item.id" class="inventory-card" @click="editInventory(item)">
            <!-- 卡片头部 -->
            <view class="card-header-row">
               <view class="product-info">
                  <text class="product-name">{{ item.productName }}</text>
                  <view class="platform-tag" :class="item.platformName">
                     {{ platformText[item.platformName] }}
                  </view>
               </view>
               <text class="batch-id">#{{ item.id }}</text>
            </view>

            <!-- 库存进度条 -->
            <view class="stock-progress-wrap">
               <view class="progress-header">
                  <text class="progress-label">库存剩余</text>
                  <text class="progress-text">{{ item.remainingQuantity }}/{{ item.quantity }}</text>
               </view>
               <view class="progress-bar">
                  <view class="progress-fill" :style="{ width: (item.remainingQuantity / item.quantity * 100) + '%' }"
                     :class="{ 'low': item.remainingQuantity / item.quantity < 0.3, 'empty': item.remainingQuantity === 0 }">
                  </view>
               </view>
            </view>

            <!-- 价格信息 -->
            <view class="price-row">
               <view class="price-item">
                  <text class="price-label">批发价</text>
                  <text class="price-value">¥{{ formatPrice(item.wholesalePrice) }}</text>
               </view>
               <view class="price-item">
                  <text class="price-label">预计利润</text>
                  <text class="price-value profit">¥{{ formatPrice(item.profit) }}</text>
               </view>
            </view>

            <!-- 订单信息 -->
            <view class="info-row">
               <view class="info-tag">
                  <u-icon name="order" size="20" color="#999"></u-icon>
                  <text class="tag-text">{{ item.platformOrderNumber }}</text>
               </view>
               <view class="info-tag">
                  <u-icon name="car-fill" size="20" color="#999"></u-icon>
                  <text class="tag-text">{{ item.trackingNumber }}</text>
               </view>
            </view>

            <!-- 底部信息 -->
            <view class="card-footer">
               <view class="merchant-tag">
                  <u-icon name="account-fill" size="20" color="#999"></u-icon>
                  <text class="tag-text">{{ item.merchantName }}</text>
               </view>
               <text class="create-time">{{ formatTime(item.createTime) }}</text>
            </view>
         </view>
      </view>

      <!-- 空状态 -->
      <view v-if="filteredList.length === 0" class="empty-state">
         <u-icon name="shopping-cart" size="80" color="#ddd"></u-icon>
         <text class="empty-text">暂无库存数据</text>
         <text class="empty-tip">点击右上角按钮添加进货记录</text>
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
               <!-- 进货平台 -->
               <view class="filter-section">
                  <view class="section-header">
                     <view class="section-icon" style="background: #e1251b15;">
                        <u-icon name="shopping-cart-fill" size="28" color="#e1251b"></u-icon>
                     </view>
                     <text class="section-label">进货平台</text>
                  </view>
                  <view class="filter-options">
                     <view class="filter-option" :class="{ active: filterPlatform === null }"
                        @click="filterPlatform = null">
                        <text>全部</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterPlatform === 'pdd' }"
                        @click="filterPlatform = 'pdd'">
                        <text>拼多多</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterPlatform === 'jd' }"
                        @click="filterPlatform = 'jd'">
                        <text>京东</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterPlatform === 'tb' }"
                        @click="filterPlatform = 'tb'">
                        <text>淘宝</text>
                     </view>
                  </view>
               </view>
               <!-- 库存状态 -->
               <view class="filter-section">
                  <view class="section-header">
                     <view class="section-icon" style="background: #52c41a15;">
                        <u-icon name="checkmark-circle" size="28" color="#52c41a"></u-icon>
                     </view>
                     <text class="section-label">库存状态</text>
                  </view>
                  <view class="filter-options">
                     <view class="filter-option" :class="{ active: filterStock === null }" @click="filterStock = null">
                        <text>全部</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterStock === 'normal' }"
                        @click="filterStock = 'normal'">
                        <text>库存正常</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterStock === 'empty' }"
                        @click="filterStock = 'empty'">
                        <text>已售罄</text>
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
import { getApi, getAuth } from '@/utils/api';
import { onShow} from '@dcloudio/uni-app'

const searchKeyword = ref('');
const loading = ref(false);

const showFilter = ref(false);

const filterPlatform = ref(null);
const filterStock = ref(null);

const platformText = {
   pdd: '拼多多',
   jd: '京东',
   tb: '淘宝',
};

// 库存列表数据
const inventoryList = ref([]);

// 库存概览数据
const overviewData = ref({
   totalPurchase: 0,
   monthProductQuantity: 0,
   totalRemaining: 0,
   totalProductRemaining: 0,
   totalCost: '¥0.00',
   totalProfit: '¥0.00'
});

// 当月进货商品数量
const monthProductQuantity = computed(() => {
   return overviewData.value.monthProductQuantity;
});

// 总商品剩余数量
const totalProductRemaining = computed(() => {
   return overviewData.value.totalProductRemaining;
});

// 总进货量（从概览API获取）
const totalQuantity = computed(() => {
   return overviewData.value.totalPurchase;
});

// 剩余库存（从概览API获取）
const totalRemaining = computed(() => {
   return overviewData.value.totalRemaining;
});

// 库存成本（从概览API获取）
const totalCost = computed(() => {
   return overviewData.value.totalCost.replace('¥', '');
});

// 预计利润（从概览API获取）
const totalProfit = computed(() => {
   return overviewData.value.totalProfit.replace('¥', '');
});

// 筛选后的库存列表
const filteredList = computed(() => {
   return inventoryList.value.filter(item => {
      // 平台筛选
      if (filterPlatform.value && item.platformName !== filterPlatform.value) {
         return false;
      }
      // 库存状态筛选
      if (filterStock.value) {
         if (filterStock.value === 'normal' && item.remainingQuantity === 0) {
            return false;
         }
         if (filterStock.value === 'empty' && item.remainingQuantity !== 0) {
            return false;
         }
      }
      return true;
   });
});

// 获取库存概览数据
const getInventoryOverview = async () => {
   try {
      const res = await uni.request({
         url: getApi('/admin/inventory/getOverview'),
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
      console.error('获取库存概览失败:', error);
   }
};

// 获取库存列表
const getInventoryList = async () => {
   loading.value = true;
   try {
      const params = {};
      if (filterPlatform.value) {
         params.platform_name = filterPlatform.value;
      }
      if (searchKeyword.value) {
         params.keyword = searchKeyword.value;
      }

      const queryString = Object.keys(params).length > 0
         ? '?' + new URLSearchParams(params).toString()
         : '';

      const res = await uni.request({
         url: getApi('/admin/inventory/getLists') + queryString,
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });
      const data = res.data;
      if (data.code === 200) {
         inventoryList.value = data.data.list;
      } else {
         uni.showToast({ title: data.message || '获取库存列表失败', icon: 'none' });
      }
   } catch (error) {
      console.error('获取库存列表失败:', error);
      uni.showToast({ title: '网络错误，请重试', icon: 'none' });
   } finally {
      loading.value = false;
   }
};

// 搜索
const handleSearch = () => {
   getInventoryList();
};

// 格式化价格，确保两位小数
const formatPrice = (price) => {
   const num = parseFloat(price) || 0;
   return num.toFixed(2);
};

const formatTime = (timestamp) => {
   const date = new Date(timestamp * 1000);
   const year = date.getFullYear();
   const month = (date.getMonth() + 1).toString().padStart(2, '0');
   const day = date.getDate().toString().padStart(2, '0');
   return `${year}-${month}-${day}`;
};

const addInventory = () => {
   uni.navigateTo({
      url: '/pages/admin/inventory/edit'
   });
};

const editInventory = (item) => {
   uni.navigateTo({
      url: `/pages/admin/inventory/edit?id=${item.id}`
   });
};

// 重置筛选
const resetFilter = () => {
   filterPlatform.value = null;
   filterStock.value = null;
};

// 应用筛选
const applyFilter = () => {
   showFilter.value = false;
   getInventoryList();
};

onShow(() => {
   getInventoryOverview();
   getInventoryList();
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

.inventory-list {
   .inventory-card {
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

            .platform-tag {
               display: inline-block;
               padding: 6rpx 16rpx;
               border-radius: 8rpx;
               font-size: 22rpx;

               &.pdd {
                  background: #e02e2415;
                  color: #e02e24;
               }

               &.jd {
                  background: #e1251b15;
                  color: #e1251b;
               }

               &.tb {
                  background: #ff500015;
                  color: #ff5000;
               }
            }
         }

         .batch-id {
            font-size: 26rpx;
            color: #999;
            font-weight: 500;
         }
      }

      .stock-progress-wrap {
         margin-bottom: 24rpx;

         .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12rpx;

            .progress-label {
               font-size: 24rpx;
               color: #666;
            }

            .progress-text {
               font-size: 24rpx;
               color: #333;
               font-weight: 500;
            }
         }

         .progress-bar {
            height: 12rpx;
            background: #f0f0f0;
            border-radius: 6rpx;
            overflow: hidden;

            .progress-fill {
               height: 100%;
               background: linear-gradient(90deg, #52c41a, #73d13d);
               border-radius: 6rpx;
               transition: width 0.3s ease;

               &.low {
                  background: linear-gradient(90deg, #faad14, #ffc53d);
               }

               &.empty {
                  background: #d9d9d9;
               }
            }
         }
      }

      .price-row {
         display: flex;
         gap: 40rpx;
         margin-bottom: 24rpx;
         padding: 20rpx;
         background: #f8f9fa;
         border-radius: 12rpx;

         .price-item {
            display: flex;
            flex-direction: column;
            gap: 8rpx;

            .price-label {
               font-size: 24rpx;
               color: #999;
            }

            .price-value {
               font-size: 30rpx;
               font-weight: 600;
               color: #333;

               &.profit {
                  color: #52c41a;
               }
            }
         }
      }

      .info-row {
         display: flex;
         flex-wrap: wrap;
         gap: 16rpx;
         margin-bottom: 20rpx;

         .info-tag {
            display: flex;
            align-items: center;
            gap: 8rpx;
            padding: 10rpx 16rpx;
            background: #f5f5f5;
            border-radius: 8rpx;

            .tag-text {
               font-size: 24rpx;
               color: #666;
            }
         }
      }

      .card-footer {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding-top: 20rpx;
         border-top: 1rpx solid #f0f0f0;

         .merchant-tag {
            display: flex;
            align-items: center;
            gap: 8rpx;

            .tag-text {
               font-size: 24rpx;
               color: #666;
            }
         }

         .create-time {
            font-size: 22rpx;
            color: #999;
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
