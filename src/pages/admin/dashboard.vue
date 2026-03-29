<template>
  <tab-navbar title="管理员控制台" :show-back="true"></tab-navbar>
  <view class="container">
    <!-- 页面标题 -->
    <view class="header">
      <u-icon name="grid-fill" size="40" color="#e1251b"></u-icon>
      <text class="title">控制台</text>
    </view>

    <!-- 数据概览卡片 -->
    <view class="stats-section">
      <text class="section-title">数据概览</text>
      <view class="stats-grid">
        <view class="stat-card primary">
          <text class="stat-label">月订单量</text>
          <text class="stat-value">{{ stats.monthOrders }}</text>
        </view>
        <view class="stat-card primary">
          <text class="stat-label">日订单量</text>
          <text class="stat-value">{{ stats.dayOrders }}</text>
        </view>
        <view class="stat-card success">
          <text class="stat-label">月销售额</text>
          <text class="stat-value">¥{{ formatMoney(stats.monthSales) }}</text>
        </view>
        <view class="stat-card success">
          <text class="stat-label">日销售额</text>
          <text class="stat-value">¥{{ formatMoney(stats.daySales) }}</text>
        </view>
        <view class="stat-card warning">
          <text class="stat-label">用户总数</text>
          <text class="stat-value">{{ stats.totalUsers }}</text>
        </view>
        <view class="stat-card warning">
          <text class="stat-label">商品总数</text>
          <text class="stat-value">{{ stats.totalProducts }}</text>
        </view>
        <view class="stat-card error">
          <text class="stat-label">月利润</text>
          <text class="stat-value">¥{{ formatMoney(stats.monthProfit) }}</text>
        </view>
        <view class="stat-card error">
          <text class="stat-label">日利润</text>
          <text class="stat-value">¥{{ formatMoney(stats.dayProfit) }}</text>
        </view>
        <view class="stat-card info">
          <text class="stat-label">月配送费</text>
          <text class="stat-value">¥{{ formatMoney(stats.monthDeliveryFee) }}</text>
        </view>
        <view class="stat-card info">
          <text class="stat-label">周配送费</text>
          <text class="stat-value">¥{{ formatMoney(stats.weekDeliveryFee) }}</text>
        </view>
      </view>
    </view>

    <!-- 管理入口 -->
    <view class="menu-section">
      <text class="section-title">管理中心</text>
      <view class="menu-grid">
        <view class="menu-item" @click="navigateTo('/pages/admin/product/index')">
          <view class="menu-icon" style="background: #e1251b15;">
            <u-icon name="shopping-cart-fill" size="48" color="#e1251b"></u-icon>
          </view>
          <text class="menu-name">商品管理</text>
        </view>
        <view class="menu-item" @click="navigateTo('/pages/admin/category/index')">
          <view class="menu-icon" style="background: #ff6b6b15;">
            <u-icon name="grid-fill" size="48" color="#ff6b6b"></u-icon>
          </view>
          <text class="menu-name">分类管理</text>
        </view>
        <view class="menu-item" @click="navigateTo('/pages/admin/order/index')">
          <view class="menu-icon" style="background: #07c16015;">
            <u-icon name="order" size="48" color="#07c160"></u-icon>
          </view>
          <text class="menu-name">订单管理</text>
        </view>
        <view class="menu-item" @click="navigateTo('/pages/admin/inventory/index')">
          <view class="menu-icon" style="background: #ff950015;">
            <u-icon name="download" size="48" color="#ff9500"></u-icon>
          </view>
          <text class="menu-name">进货管理</text>
        </view>
        <view class="menu-item" @click="navigateTo('/pages/admin/user/index')">
          <view class="menu-icon" style="background: #5856d615;">
            <u-icon name="account-fill" size="48" color="#5856d6"></u-icon>
          </view>
          <text class="menu-name">用户管理</text>
        </view>
        <view class="menu-item" @click="navigateTo('/pages/admin/inventory-loss/index')">
          <view class="menu-icon" style="background: #af52de15;">
            <u-icon name="bag-fill" size="48" color="#af52de"></u-icon>
          </view>
          <text class="menu-name">库损管理</text>
        </view>
        <view class="menu-item" @click="navigateTo('/pages/admin/config/index')">
          <view class="menu-icon" style="background: #007aff15;">
            <u-icon name="setting-fill" size="48" color="#007aff"></u-icon>
          </view>
          <text class="menu-name">配置管理</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup>
import { ref } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';
// import TabNavbar from '@/components/tab-navbar/tab-navbar.vue';

const stats = ref({
  monthOrders: 0,
  dayOrders: 0,
  monthSales: 0,
  daySales: 0,
  totalUsers: 0,
  totalProducts: 0,
  monthProfit: 0,
  dayProfit: 0,
  monthDeliveryFee: 0,
  weekDeliveryFee: 0
});

const formatMoney = (value) => {
  const num = parseFloat(value) || 0;
  return num.toFixed(2);
};

const navigateTo = (url) => {
  uni.navigateTo({ url });
};

const fetchStats = async () => {
  try {
    const res = await uni.request({
      url: getApi('/admin/dashboard/getStats'),
      method: 'GET',
      header: {
        'Authorization': getAuth()
      }
    });

    const data = res.data;
    if (data.code === 200) {
      stats.value = data.data;
    } else {
      uni.showToast({ title: data.message || '获取失败', icon: 'none' });
    }
  } catch (error) {
    console.error('获取统计数据失败:', error);
    uni.showToast({ title: '网络错误', icon: 'none' });
  }
};

onLoad(() => {
  fetchStats();
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
  align-items: center;
  gap: 16rpx;
  padding: 30rpx 20rpx;
  background: #fff;
  border-radius: 16rpx;
  margin-bottom: 20rpx;

  .title {
    font-size: 36rpx;
    font-weight: 600;
    color: #333;
  }
}

.section-title {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 20rpx;
  padding-left: 10rpx;
}

.stats-section {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx 20rpx;
  margin-bottom: 20rpx;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
}

.stat-card {
  padding: 30rpx 24rpx;
  border-radius: 12rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;

  &.primary {
    background: #e1251b10;

    .stat-label {
      color: #e1251b99;
    }

    .stat-value {
      color: #e1251b;
    }
  }

  &.success {
    background: #07c16010;

    .stat-label {
      color: #07c16099;
    }

    .stat-value {
      color: #07c160;
    }
  }

  &.warning {
    background: #ff950010;

    .stat-label {
      color: #ff950099;
    }

    .stat-value {
      color: #ff9500;
    }
  }

  &.error {
    background: #5856d610;

    .stat-label {
      color: #5856d699;
    }

    .stat-value {
      color: #5856d6;
    }
  }

  &.info {
    background: #007aff10;

    .stat-label {
      color: #007aff99;
    }

    .stat-value {
      color: #007aff;
    }
  }

  .stat-label {
    font-size: 26rpx;
  }

  .stat-value {
    font-size: 36rpx;
    font-weight: 600;
  }
}

.menu-section {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx 20rpx;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30rpx;
}

.menu-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
  padding: 20rpx 10rpx;
  border-radius: 12rpx;
  transition: background 0.2s;

  &:active {
    background: #f5f5f5;
  }

  .menu-icon {
    width: 100rpx;
    height: 100rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .menu-name {
    font-size: 28rpx;
    color: #333;
  }
}
</style>
