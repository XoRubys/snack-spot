<template>
   <tab-navbar title="配置管理" :show-back="true"></tab-navbar>
   <view class="container">
      <!-- 店铺配置卡片 -->
      <view class="config-card">
         <view class="card-header">
            <u-icon name="shop-fill" size="40" color="#e1251b"></u-icon>
            <text class="card-title">店铺配置</text>
         </view>

         <view class="form-item">
            <text class="form-label">宿舍楼名称</text>
            <input class="form-input" v-model="config.address" placeholder="请输入宿舍楼名称" placeholder-class="placeholder" />
         </view>
      </view>

      <!-- 配送配置卡片 -->
      <view class="config-card">
         <view class="card-header">
            <u-icon name="car-fill" size="40" color="#1890ff"></u-icon>
            <text class="card-title">配送配置</text>
         </view>

         <view class="form-item">
            <text class="form-label">最低配送费 (元)</text>
            <view class="input-wrap">
               <input class="form-input" v-model="config.deliveryFeeMin" type="digit" placeholder="0.00"
                  placeholder-class="placeholder" />
               <text class="input-suffix">元</text>
            </view>
         </view>

         <view class="form-item">
            <text class="form-label">配送费百分比 (%)</text>
            <view class="input-wrap">
               <input class="form-input" v-model="config.deliveryFeePercent" type="digit" placeholder="0.00"
                  placeholder-class="placeholder" />
               <text class="input-suffix">%</text>
            </view>
            <text class="form-tip">按订单金额的百分比收取配送费</text>
         </view>
      </view>

      <!-- 通知配置卡片 -->
      <view class="config-card">
         <view class="card-header">
            <u-icon name="volume-fill" size="40" color="#52c41a"></u-icon>
            <text class="card-title">首页通知</text>
         </view>

         <view class="form-item">
            <text class="form-label">公告内容</text>
            <textarea class="form-textarea" v-model="config.notice" placeholder="请输入首页公告内容，留空则不显示"
               placeholder-class="placeholder" maxlength="200" />
            <text class="textarea-count">{{ config.notice?.length || 0 }}/200</text>
         </view>
      </view>

      <!-- 营业状态卡片 -->
      <view class="config-card">
         <view class="card-header">
            <u-icon name="clock-fill" size="40" color="#ff9500"></u-icon>
            <text class="card-title">营业状态</text>
         </view>

         <view class="form-item switch-item">
            <view class="switch-info">
               <text class="form-label">{{ config.online ? '店铺营业中' : '店铺已打烊' }}</text>
               <text class="form-desc">{{ config.online ? '用户可以正常下单' : '用户无法下单，首页将显示打烊提示' }}</text>
            </view>
            <u-switch v-model="config.online" active-color="#52c41a" inactive-color="#ff4d4f"></u-switch>
         </view>

         <!-- 打烊提示词 -->
         <view v-if="!config.online" class="form-item">
            <text class="form-label">打烊提示词</text>
            <textarea class="form-textarea" v-model="config.closedNotice"
               placeholder="请输入店铺打烊时给用户看的提示，如：店铺已打烊，明日10:00开始营业" placeholder-class="placeholder" maxlength="100" />
            <text class="textarea-count">{{ config.closedNotice?.length || 0 }}/100</text>
         </view>
      </view>

      <!-- 底部占位 -->
      <view class="footer-space"></view>

      <!-- 提交按钮 -->
      <view class="submit-bar">
         <view class="submit-btn" @click="submitConfig">
            <u-icon v-if="loading" name="reload" size="32" color="#fff" class="loading-icon"></u-icon>
            <text>{{ loading ? '保存中...' : '保存配置' }}</text>
         </view>
      </view>
   </view>
</template>

<script setup>
import { ref } from 'vue';
import { getApi, getAuth } from '@/utils/api';
import { onLoad } from '@dcloudio/uni-app';

const loading = ref(false);

const config = ref({
   address: '',
   deliveryFeeMin: '0.00',
   deliveryFeePercent: '0.00',
   notice: '',
   online: true,
   closedNotice: '',
});

const fetchConfig = () => {
   uni.request({
      url: getApi('/admin/config/get'),
      method: 'GET',
      header: {
         'Content-Type': 'application/json; charset=utf-8',
         'Authorization': getAuth()
      },
      success: (res) => {
         if (res.statusCode === 200 && res.data.code === 200) {
            const data = res.data.data
            config.value.address = data.address
            config.value.deliveryFeeMin = String(data.deliveryFeeMin)
            config.value.deliveryFeePercent = String(data.deliveryFeePercent)
            config.value.notice = data.notice
            config.value.online = data.online
            config.value.closedNotice = data.closedNotice || '店铺已打烊，明日10:00开始营业'
         } else {
            uni.showToast({
               title: res.data.message || '获取配置失败',
               icon: 'none'
            })
         }
      },
      fail: () => {
         uni.showToast({
            title: '网络请求失败',
            icon: 'none'
         })
      }
   })
}

const submitConfig = async () => {
   if (loading.value) return;

   if (!config.value.address.trim()) {
      uni.showToast({ title: '请输入宿舍楼名称', icon: 'none' });
      return;
   }

   loading.value = true;

   uni.request({
      url: getApi('/admin/config/edit'),
      method: 'POST',
      data: {
         online: config.value.online,
         delivery_fee_min: config.value.deliveryFeeMin,
         delivery_fee_percent: config.value.deliveryFeePercent,
         address: config.value.address,
         notice: config.value.notice,
         online_notice: config.value.closedNotice
      },
      header: {
         'Content-Type': 'application/json; charset=utf-8',
         'Authorization': getAuth()
      },
      success: (res) => {
         loading.value = false
         if (res.statusCode === 200 && res.data.code === 200) {
            uni.showToast({
               title: '保存成功',
               icon: 'success'
            })
            setTimeout(() => {
               uni.navigateBack()
            }, 1500)
         } else {
            uni.showToast({
               title: res.data.message || '保存失败',
               icon: 'none'
            })
         }
      },
      fail: () => {
         loading.value = false
         uni.showToast({
            title: '网络请求失败',
            icon: 'none'
         })
      }
   })
};

onLoad(() => {
   fetchConfig()
});
</script>

<style lang="scss" scoped>
.container {
   min-height: 94vh;
   background: #f5f5f5;
   padding: 20rpx;
}

// 配置卡片
.config-card {
   background: #fff;
   border-radius: 16rpx;
   padding: 30rpx;
   margin-bottom: 20rpx;
   box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

   .card-header {
      display: flex;
      align-items: center;
      gap: 16rpx;
      margin-bottom: 30rpx;
      padding-bottom: 20rpx;
      border-bottom: 1rpx solid #f5f5f5;

      .card-title {
         font-size: 32rpx;
         font-weight: 600;
         color: #333;
      }
   }

   .form-item {
      margin-bottom: 30rpx;

      &:last-child {
         margin-bottom: 0;
      }

      .form-label {
         display: block;
         font-size: 28rpx;
         color: #333;
         margin-bottom: 16rpx;
         font-weight: 500;
      }

      .form-desc {
         display: block;
         font-size: 24rpx;
         color: #999;
         margin-top: 8rpx;
      }

      .input-wrap {
         display: flex;
         align-items: center;
         background: #f8f8f8;
         border-radius: 12rpx;
         padding: 0 24rpx;

         .form-input {
            flex: 1;
            height: 88rpx;
            font-size: 30rpx;
            color: #333;
            background: transparent;
         }

         .input-suffix {
            font-size: 28rpx;
            color: #666;
            margin-left: 16rpx;
         }
      }

      .form-input {
         height: 88rpx;
         background: #f8f8f8;
         border-radius: 12rpx;
         padding: 0 24rpx;
         font-size: 30rpx;
         color: #333;
      }

      .form-textarea {
         width: 100%;
         height: 180rpx;
         background: #f8f8f8;
         border-radius: 12rpx;
         padding: 20rpx 24rpx;
         font-size: 30rpx;
         color: #333;
         box-sizing: border-box;
      }

      .textarea-count {
         display: block;
         text-align: right;
         font-size: 24rpx;
         color: #999;
         margin-top: 12rpx;
      }

      .form-tip {
         display: block;
         font-size: 24rpx;
         color: #999;
         margin-top: 12rpx;
      }

      &.switch-item {
         display: flex;
         justify-content: space-between;
         align-items: center;
         position: relative;

         .switch-info {
            .form-label {
               margin-bottom: 8rpx;
            }
         }

         :deep(.u-switch) {
            position: relative;
            z-index: 1;
         }
      }
   }
}

.placeholder {
   color: #bbb;
}

// 底部占位
.footer-space {
   height: 140rpx;
}

// 提交按钮
.submit-bar {
   position: fixed;
   left: 0;
   right: 0;
   bottom: 0;
   background: #fff;
   padding: 20rpx 30rpx 40rpx;
   box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.06);
   z-index: 100;

   .submit-btn {
      background: linear-gradient(135deg, #e1251b 0%, #ff4d4f 100%);
      border-radius: 44rpx;
      height: 88rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12rpx;

      text {
         font-size: 32rpx;
         color: #fff;
         font-weight: 600;
      }

      .loading-icon {
         animation: rotate 1s linear infinite;
      }

      &:active {
         opacity: 0.9;
      }
   }
}

@keyframes rotate {
   from {
      transform: rotate(0deg);
   }

   to {
      transform: rotate(360deg);
   }
}
</style>
