<template>
  <tab-navbar title="订单详情" :show-back="true"></tab-navbar>
  <view class="container">
    <!-- 订单状态卡片 -->
    <view class="status-card">
      <view class="status-content">
        <view class="status-icon" :style="{ background: getStatusColor(order.status) + '15' }">
          <u-icon :name="getStatusIcon(order.status)" size="48" :color="getStatusColor(order.status)"></u-icon>
        </view>
        <view class="status-info">
          <text class="status-text">{{ getStatusText(order.status) }}</text>
          <text class="status-desc">{{ getStatusDesc(order.status) }}</text>
        </view>
      </view>
      <view v-if="order.status === 'paid'" class="action-btns">
        <view class="action-btn cancel" @click="handleCancel">
          <u-icon name="close-circle" size="24" color="#fff"></u-icon>
          <text>取消订单</text>
        </view>
        <view class="action-btn complete" @click="handleComplete">
          <u-icon name="checkmark-circle" size="24" color="#fff"></u-icon>
          <text>完成订单</text>
        </view>
      </view>
    </view>

    <!-- 订单信息 -->
    <view class="info-card">
      <view class="card-header">
        <u-icon name="info-circle" size="32" color="#1890ff"></u-icon>
        <text class="card-title">订单信息</text>
      </view>
      <view class="info-list">
        <view class="info-item">
          <text class="info-label">订单编号</text>
          <text class="info-value">{{ order.orderNo }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">下单时间</text>
          <text class="info-value">{{ order.createTime || '-' }}</text>
        </view>
        <view class="info-item" v-if="order.status !== 'pending'">
          <text class="info-label">支付时间</text>
          <text class="info-value">{{ order.payTime || '-' }}</text>
        </view>
        <view class="info-item" v-if="order.status === 'completed'">
          <text class="info-label">完成时间</text>
          <text class="info-value">{{ order.completeTime || '-' }}</text>
        </view>
        <view class="info-item" v-if="order.status === 'cancelled' || order.status === 'timeout'">
          <text class="info-label">{{ order.status === 'cancelled' ? '取消时间' : '超时时间' }}</text>
          <text class="info-value">{{ order.completeTime || '-' }}</text>
        </view>
      </view>
    </view>

    <!-- 商品信息 -->
    <view class="info-card">
      <view class="card-header">
        <u-icon name="shopping-cart" size="32" color="#e1251b"></u-icon>
        <text class="card-title">商品信息</text>
      </view>
      <view class="product-list">
        <view v-for="(item, index) in order.items" :key="index" class="product-item">
          <view class="product-info">
            <text class="product-name">{{ item.product_name }}</text>
            <text class="product-spec">¥{{ formatPrice(item.product_price) }}</text>
          </view>
          <view class="product-quantity">x {{ item.quantity }}</view>
        </view>
      </view>
    </view>

    <!-- 金额信息 -->
    <view class="info-card">
      <view class="card-header">
        <u-icon name="rmb-circle" size="32" color="#07c160"></u-icon>
        <text class="card-title">金额信息</text>
      </view>
      <view class="info-list">
        <view class="info-item">
          <text class="info-label">订单金额</text>
          <text class="info-value">¥{{ formatPrice(order.totalAmount) }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">配送费</text>
          <text class="info-value">¥{{ deliveryFee }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">实付金额</text>
          <text class="info-value highlight">¥{{ formatPrice(order.payAmount) }}</text>
        </view>
      </view>
    </view>

    <!-- 收货信息 -->
    <view class="info-card">
      <view class="card-header">
        <u-icon name="map" size="32" color="#ff9500"></u-icon>
        <text class="card-title">收货信息</text>
      </view>
      <view class="info-list">
        <view class="info-item">
          <text class="info-label">收货人</text>
          <text class="info-value">{{ order.receiverName }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">联系电话</text>
          <text class="info-value">{{ order.receiverPhone }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">收货地址</text>
          <text class="info-value address">{{ order.receiverAddress }}</text>
        </view>
        <view class="info-item" v-if="order.remark">
          <text class="info-label">用户备注</text>
          <text class="info-value">{{ order.remark }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">管理员备注</text>
          <input class="info-input" v-model="order.remarkAdmin" placeholder="点击添加备注" @blur="saveRemarkAdmin" />
        </view>
      </view>
    </view>

    <!-- 底部占位 -->
    <view class="footer-space"></view>
  </view>


</template>

<script setup>
import { ref, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';

const orderId = ref(0);
const loading = ref(false);

const order = ref({
  id: 0,
  orderNo: '',
  status: '',
  createTime: '',
  payTime: '',
  completeTime: '',
  productName: '',
  productSpec: '',
  quantity: 1,
  totalAmount: '0.00',
  payAmount: '0.00',
  receiverName: '',
  receiverPhone: '',
  receiverAddress: '',
  remark: '',
  remarkAdmin: '',
  items: []
});

// 计算配送费：实付金额 - 订单金额
const deliveryFee = computed(() => {
  const pay = Math.round((parseFloat(order.value.payAmount) || 0) * 100);
  const total = Math.round((parseFloat(order.value.totalAmount) || 0) * 100);
  const fee = (pay - total) / 100;
  return fee > 0 ? fee.toFixed(2) : '0.00';
});

const statusConfig = {
  pending: {
    label: '待支付',
    desc: '等待用户支付',
    icon: 'clock-fill',
    color: '#ff9500'
  },
  paid: {
    label: '已支付',
    desc: '用户已支付，待处理',
    icon: 'checkmark-circle-fill',
    color: '#1890ff'
  },
  timeout: {
    label: '已超时',
    desc: '支付超时，订单关闭',
    icon: 'close-circle-fill',
    color: '#af52de'
  },
  cancelled: {
    label: '已取消',
    desc: '用户取消订单',
    icon: 'close-circle-fill',
    color: '#ff4d4f'
  },
  completed: {
    label: '已完成',
    desc: '订单已完成',
    icon: 'checkmark-circle-fill',
    color: '#07c160'
  }
};

const getStatusText = (status) => {
  return statusConfig[status]?.label || status;
};

const getStatusDesc = (status) => {
  return statusConfig[status]?.desc || '';
};

const getStatusIcon = (status) => {
  return statusConfig[status]?.icon || 'info-circle';
};

const getStatusColor = (status) => {
  return statusConfig[status]?.color || '#999';
};

// 格式化价格，确保两位小数
const formatPrice = (price) => {
  const num = parseFloat(price) || 0;
  return num.toFixed(2);
};

const fetchOrderDetail = async () => {
  if (!orderId.value) return;

  try {
    const res = await uni.request({
      url: getApi('/admin/order/getDetail?id=' + orderId.value),
      method: 'GET',
      header: {
        'Authorization': getAuth()
      }
    });

    const data = res.data;
    if (data.code === 200) {
      order.value = data.data;
    } else {
      uni.showToast({ title: data.message || '获取失败', icon: 'none' });
    }
  } catch (error) {
    console.error('获取订单详情失败:', error);
    uni.showToast({ title: '网络错误', icon: 'none' });
  }
};

const updateStatus = async (status) => {
  try {
    const res = await uni.request({
      url: getApi('/admin/order/updateStatus'),
      method: 'POST',
      header: {
        'Content-Type': 'application/json',
        'Authorization': getAuth()
      },
      data: {
        id: orderId.value,
        status: status
      }
    });

    const data = res.data;
    if (data.code === 200) {
      uni.showToast({ title: '修改成功', icon: 'success' });
      setTimeout(() => {
        uni.navigateBack();
      }, 1500);
    } else {
      uni.showToast({ title: data.message || '修改失败', icon: 'none' });
    }
  } catch (error) {
    console.error('修改状态失败:', error);
    uni.showToast({ title: '网络错误', icon: 'none' });
  }
};

const saveRemarkAdmin = async () => {
  try {
    const res = await uni.request({
      url: getApi('/admin/order/updateRemark'),
      method: 'POST',
      header: {
        'Content-Type': 'application/json',
        'Authorization': getAuth()
      },
      data: {
        id: orderId.value,
        remarkAdmin: order.value.remarkAdmin
      }
    });

    const data = res.data;
    if (data.code === 200) {
    } else {
      uni.showToast({ title: data.message || '保存失败', icon: 'none' });
    }
  } catch (error) {
    console.error('保存备注失败:', error);
  }
};

const handleCancel = () => {
  uni.showModal({
    title: '提示',
    content: '确定取消该订单？',
    success: (res) => {
      if (res.confirm) {
        updateStatus('cancelled');
      }
    }
  });
};

const handleComplete = () => {
  uni.showModal({
    title: '提示',
    content: '确定完成该订单？',
    success: (res) => {
      if (res.confirm) {
        updateStatus('completed');
      }
    }
  });
};

onLoad((options) => {
  orderId.value = parseInt(options.id) || 0;
  if (orderId.value) {
    fetchOrderDetail();
  }
});
</script>

<style lang="scss" scoped>
.container {
  min-height: 94vh;
  background: #f5f5f5;
  padding: 20rpx;
}

.status-card {
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);

  .status-content {
    display: flex;
    align-items: center;
    gap: 24rpx;

    .status-icon {
      width: 96rpx;
      height: 96rpx;
      border-radius: 24rpx;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .status-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 8rpx;

      .status-text {
        font-size: 32rpx;
        font-weight: 600;
        color: #333;
      }

      .status-desc {
        font-size: 24rpx;
        color: #999;
      }
    }
  }

  .action-btns {
    margin-top: 24rpx;
    display: flex;
    gap: 20rpx;

    .action-btn {
      flex: 1;
      padding: 20rpx;
      border-radius: 12rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12rpx;
      color: #fff;
      font-size: 28rpx;
      font-weight: 500;

      &.cancel {
        background: #ff4d4f;
      }

      &.complete {
        background: #07c160;
      }
    }
  }
}

.info-card {
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);

  .card-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 24rpx;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #f0f0f0;

    .card-title {
      font-size: 30rpx;
      font-weight: 600;
      color: #333;
    }
  }

  .info-list {
    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16rpx 0;
      border-bottom: 1rpx solid #f8f8f8;

      &:last-child {
        border-bottom: none;
      }

      .info-label {
        font-size: 26rpx;
        color: #999;
      }

      .info-value {
        font-size: 28rpx;
        color: #333;
        text-align: right;

        &.highlight {
          color: #e1251b;
          font-weight: 600;
        }

        &.address {
          max-width: 400rpx;
          text-align: right;
        }
      }

      .info-input {
        flex: 1;
        font-size: 28rpx;
        color: #333;
        text-align: right;
        padding: 0;
        margin: 0;
        border: none;
        background: transparent;
        outline: none;

        &::placeholder {
          color: #bbb;
        }
      }
    }
  }

  .product-list {
    .product-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16rpx 0;

      .product-info {
        display: flex;
        flex-direction: column;
        gap: 8rpx;

        .product-name {
          font-size: 28rpx;
          color: #333;
          font-weight: 500;
        }

        .product-spec {
          font-size: 24rpx;
          color: #999;
        }
      }

      .product-quantity {
        font-size: 28rpx;
        color: #999;
      }
    }
  }
}

.footer-space {
  height: 40rpx;
}
</style>
