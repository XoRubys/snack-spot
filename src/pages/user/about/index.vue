<template>
    <tab-navbar title="关于我们" :show-back="true"></tab-navbar>
    <view class="container">
        <!-- 顶部渐变区域 -->
        <view class="gradient-header">
            <text class="page-title">零食铺</text>
        </view>

        <!-- 应用简介 -->
        <view class="info-card">
            <view class="card-header">应用简介</view>
            <view class="card-content">
                零食铺 是一个面向校园的零食售卖平台，提供便捷的在线选购、快速配送服务，让你的校园生活更加美好。
            </view>
        </view>

        <!-- 联系我们 -->
        <view class="info-card">
            <view class="card-header">联系我们</view>
            <view class="card-content">
                <view class="info-row">
                    <text class="info-label">地址</text>
                    <text class="info-value">你的地址</text>
                </view>
                <view class="info-row">
                    <text class="info-label">电话</text>
                    <text class="info-value">+86 xxx xxxx xxxx</text>
                </view>
                <view class="info-row">
                    <text class="info-label">营业时间</text>
                    <text class="info-value">07:00 - 23:30</text>
                </view>
            </view>
        </view>

        <!-- 服务说明 -->
        <view class="info-card">
            <view class="card-header">服务说明</view>
            <view class="card-content">
                <view class="service-item">
                    <text class="service-title">快速配送</text>
                    <text class="service-desc">下单后快速送达宿舍</text>
                </view>
                <view class="service-item">
                    <text class="service-title">价格实惠</text>
                    <text class="service-desc">所有商品价格有保障</text>
                </view>
                <view class="service-item">
                    <text class="service-title">售后服务</text>
                    <text class="service-desc">有问题随时联系客服</text>
                </view>
            </view>
        </view>

        <!-- 版权信息 -->
        <view class="copyright">
            <text class="copyright-text">© 2026 Your Name. All rights reserved.</text>
        </view>
    </view>
</template>

<script setup>
// 关于我们页面，无特殊逻辑
</script>

<style lang="scss" scoped>
.container {
    min-height: 94vh;
    background: #f5f5f5;
}

// 顶部渐变区域
.gradient-header {
    background: linear-gradient(135deg, #e1251b 0%, #ff6b6b 100%);
    padding: 60rpx 30rpx;
    text-align: center;

    .page-title {
        font-size: 36rpx;
        font-weight: 600;
        color: #fff;
    }
}

// 信息卡片
.info-card {
    background: #fff;
    margin: 20rpx 30rpx;
    border-radius: 12rpx;
    overflow: hidden;

    .card-header {
        padding: 24rpx 30rpx;
        font-size: 28rpx;
        font-weight: 600;
        color: #333;
        border-bottom: 1rpx solid #f5f5f5;
    }

    .card-content {
        padding: 0 30rpx 24rpx;

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20rpx 0;
            border-bottom: 1rpx solid #f8f8f8;

            &:last-child {
                border-bottom: none;
            }

            .info-label {
                font-size: 28rpx;
                color: #999;
            }

            .info-value {
                font-size: 28rpx;
                color: #333;
            }
        }

        .service-item {
            padding: 20rpx 0;
            border-bottom: 1rpx solid #f8f8f8;

            &:last-child {
                border-bottom: none;
            }

            .service-title {
                display: block;
                font-size: 28rpx;
                color: #333;
                margin-bottom: 6rpx;
            }

            .service-desc {
                display: block;
                font-size: 24rpx;
                color: #999;
            }
        }
    }
}

// 版本信息
.version-info {
    text-align: center;
    padding: 30rpx 20rpx 10rpx;

    .version-text {
        font-size: 26rpx;
        color: #999;
    }
}

// 版权信息
.copyright {
    padding: 20rpx;
    text-align: center;

    .copyright-text {
        font-size: 24rpx;
        color: #ccc;
    }
}
</style>
