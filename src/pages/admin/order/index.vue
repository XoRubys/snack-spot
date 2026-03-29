<template>
  <tab-navbar title="订单管理" :show-back="true"></tab-navbar>
  <view class="container">
    <!-- 页面标题 -->
    <view class="header">
      <text class="title">订单管理</text>
    </view>

    <!-- 数据概览卡片 -->
    <view class="stats-overview">
      <view class="stats-header">
        <view class="stats-title-wrap">
          <view class="title-icon" style="background: #e1251b15;">
            <u-icon name="order" size="32" color="#e1251b"></u-icon>
          </view>
          <text class="stats-title">订单概览</text>
        </view>
        <text class="stats-subtitle">共 {{ filteredList.length }} 条记录</text>
      </view>
      <view class="stats-grid">
        <view class="stats-item">
          <view class="stats-icon-wrap" style="background: #ff950015;">
            <u-icon name="clock-fill" size="36" color="#ff9500"></u-icon>
          </view>
          <view class="stats-info">
            <text class="stats-value pending">{{ overviewData.pendingOrders }}<text class="unit">单</text></text>
            <text class="stats-label">待处理</text>
          </view>
        </view>
        <view class="stats-item">
          <view class="stats-icon-wrap" style="background: #1890ff15;">
            <u-icon name="list-dot" size="36" color="#1890ff"></u-icon>
          </view>
          <view class="stats-info">
            <text class="stats-value">{{ overviewData.monthOrders }}<text class="unit">单</text></text>
            <text class="stats-label">本月订单</text>
          </view>
        </view>
        <view class="stats-item">
          <view class="stats-icon-wrap" style="background: #52c41a15;">
            <u-icon name="checkmark-circle-fill" size="36" color="#52c41a"></u-icon>
          </view>
          <view class="stats-info">
            <text class="stats-value">{{ overviewData.completedOrders }}<text class="unit">单</text></text>
            <text class="stats-label">已完成</text>
          </view>
        </view>
        <view class="stats-item">
          <view class="stats-icon-wrap" style="background: #e1251b15;">
            <u-icon name="rmb-circle-fill" size="36" color="#e1251b"></u-icon>
          </view>
          <view class="stats-info">
            <text class="stats-value sales">¥{{ overviewData.monthSales }}</text>
            <text class="stats-label">本月销售</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 搜索栏 -->
    <view class="search-bar">
      <view class="search-input-wrap">
        <u-icon name="search" size="32" color="#999"></u-icon>
        <input class="search-input" v-model="searchKeyword" placeholder="搜索订单号/商品名称" confirm-type="search"
          @confirm="handleSearch" />
      </view>
      <view class="filter-btn" @click="showFilter = true">
        <u-icon name="grid" size="32" color="#666"></u-icon>
      </view>
    </view>

    <!-- 订单列表 -->
    <view class="order-list">
      <view v-for="item in filteredList" :key="item.id" class="order-item" @click="handleView(item)">
        <view class="order-header">
          <view class="order-info">
            <text class="order-id">订单 #{{ item.orderNo }}</text>
            <text class="order-time">{{ item.createTime }}</text>
          </view>
          <view class="status-tag" :class="item.status">
            {{ getStatusText(item.status) }}
          </view>
        </view>
        <view class="order-body">
          <view class="product-row">
            <text class="product-name">{{ item.productName }} <text v-if="item.itemCount > 1" class="item-count">等{{
              item.itemCount }}件商品</text><text v-else>x {{ item.quantity }}</text></text>
            <text class="order-amount">¥{{ formatPrice(item.payAmount) }}</text>
          </view>
          <view class="info-row">
            <text class="info-item">
              <u-icon name="account-fill" size="20" color="#999"></u-icon>
              {{ item.receiverName }}
            </text>
            <text class="info-item">
              <u-icon name="map" size="20" color="#999"></u-icon>
              {{ item.receiverAddress }}
            </text>
          </view>
        </view>
      </view>
    </view>

    <!-- 空状态 -->
    <view v-if="filteredList.length === 0" class="empty-state">
      <u-icon name="order" size="80" color="#ddd"></u-icon>
      <text class="empty-text">暂无订单数据</text>
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
          <!-- 订单状态 -->
          <view class="filter-card">
            <view class="filter-card-title">
              <view class="title-icon" style="background: #07c16015;">
                <u-icon name="checkmark-circle-fill" size="24" color="#07c160"></u-icon>
              </view>
              <text>订单状态</text>
            </view>
            <view class="filter-card-content">
              <view class="filter-tag" :class="{ active: filterStatus === null }" @click="filterStatus = null">
                <text>全部</text>
              </view>
              <view class="filter-tag" :class="{ active: filterStatus === 'pending' }"
                @click="filterStatus = 'pending'">
                <text>待支付</text>
              </view>
              <view class="filter-tag" :class="{ active: filterStatus === 'timeout' }"
                @click="filterStatus = 'timeout'">
                <text>已超时</text>
              </view>
              <view class="filter-tag" :class="{ active: filterStatus === 'cancelled' }"
                @click="filterStatus = 'cancelled'">
                <text>已取消</text>
              </view>
              <view class="filter-tag" :class="{ active: filterStatus === 'completed' }"
                @click="filterStatus = 'completed'">
                <text>已完成</text>
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
const loading = ref(false);

const statusText = {
  pending: '待支付',
  paid: '已支付',
  timeout: '已超时',
  cancelled: '已取消',
  completed: '已完成',
};

// 订单列表数据
const orderList = ref([]);

// 订单概览数据
const overviewData = ref({
  totalOrders: 0,
  completedOrders: 0,
  pendingOrders: 0,
  monthOrders: 0,
  monthSales: '0.00'
});

// 获取订单概览数据
const getOrderOverview = async () => {
  try {
    const res = await uni.request({
      url: getApi('/admin/order/getOverview'),
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
    console.error('获取订单概览失败:', error);
  }
};

// 待处理订单数量（待支付）
const pendingCount = computed(() => {
  return orderList.value.filter(item => item.status === 'pending').length;
});

// 本月订单数量
const monthOrderCount = computed(() => {
  const now = new Date();
  const currentMonth = now.getMonth();
  const currentYear = now.getFullYear();
  return orderList.value.filter(item => {
    const itemDate = new Date(item.createTime * 1000);
    return itemDate.getMonth() === currentMonth && itemDate.getFullYear() === currentYear;
  }).length;
});

// 筛选后的订单列表（前端筛选）
const filteredList = computed(() => {
  return orderList.value;
});

// 格式化价格，确保两位小数
const formatPrice = (price) => {
  const num = parseFloat(price) || 0;
  return num.toFixed(2);
};

// 获取订单列表
const getOrderList = async () => {
  if (loading.value) return;
  loading.value = true;

  try {
    const params = [];
    if (filterStatus.value) {
      params.push(`status=${filterStatus.value}`);
    }
    if (searchKeyword.value) {
      params.push(`keyword=${encodeURIComponent(searchKeyword.value)}`);
    }
    const queryString = params.length > 0 ? '?' + params.join('&') : '';

    const res = await uni.request({
      url: getApi('/admin/order/getLists' + queryString),
      method: 'GET',
      header: {
        'Authorization': getAuth()
      }
    });

    const data = res.data;
    if (data.code === 200) {
      orderList.value = data.data.list || [];
    } else {
      uni.showToast({ title: data.message || '获取失败', icon: 'none' });
    }
  } catch (error) {
    console.error('获取订单列表失败:', error);
    uni.showToast({ title: '网络错误', icon: 'none' });
  } finally {
    loading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  getOrderList();
};

const getStatusText = (status) => {
  return statusText[status] || status;
};

const handleView = (item) => {
  uni.navigateTo({
    url: `/pages/admin/order/detail?id=${item.id}`
  });
};

// 重置筛选
const resetFilter = () => {
  filterStatus.value = null;
};

// 应用筛选
const applyFilter = () => {
  showFilter.value = false;
  getOrderList();
};

// 页面加载时刷新数据
onShow(() => {
  getOrderOverview();
  getOrderList();
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

          .unit {
            font-size: 24rpx;
            font-weight: 400;
            color: #999;
            margin-left: 4rpx;
          }

          &.pending {
            color: #ff9500;
          }

          &.sales {
            color: #e1251b;
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

.order-list {
  .order-item {
    background: #fff;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 16rpx;

      .order-info {
        display: flex;
        flex-direction: column;
        gap: 8rpx;

        .order-id {
          font-size: 28rpx;
          font-weight: 600;
          color: #333;
        }

        .order-time {
          font-size: 24rpx;
          color: #999;
        }
      }

      .status-tag {
        padding: 8rpx 20rpx;
        border-radius: 8rpx;
        font-size: 24rpx;
        flex-shrink: 0;

        &.pending {
          background: #ff950015;
          color: #ff9500;
        }

        &.paid {
          background: #1890ff15;
          color: #1890ff;
        }

        &.timeout {
          background: #af52de15;
          color: #af52de;
        }

        &.cancelled {
          background: #ff4d4f15;
          color: #ff4d4f;
        }

        &.completed {
          background: #07c16015;
          color: #07c160;
        }
      }
    }

    .order-body {
      .product-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16rpx 0;
        border-bottom: 1rpx solid #f0f0f0;

        .product-name {
          font-size: 28rpx;
          color: #333;
        }

        .order-amount {
          font-size: 32rpx;
          font-weight: 600;
          color: #e1251b;
        }
      }

      .info-row {
        display: flex;
        justify-content: space-between;
        padding-top: 16rpx;

        .info-item {
          display: flex;
          align-items: center;
          gap: 8rpx;
          font-size: 24rpx;
          color: #999;
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
          padding: 12rpx 24rpx;
          border-radius: 8rpx;
          background: #f5f5f5;
          font-size: 26rpx;
          color: #666;

          &.active {
            background: #e1251b;
            color: #fff;
          }
        }
      }
    }
  }

  .filter-footer {
    display: flex;
    gap: 20rpx;
    padding: 24rpx;
    background: #fff;
    border-top: 1rpx solid #eee;

    .btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8rpx;
      padding: 20rpx;
      border-radius: 12rpx;
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
