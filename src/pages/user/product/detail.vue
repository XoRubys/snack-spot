<template>
    <tab-navbar title="商品详情" :show-back="true"></tab-navbar>
    <view class="container">
        <u-loading-popup v-model="loading" color="#e1251b" mode="circle" text="加载中..." size="56" />

        <block v-if="product">
            <swiper class="image-swiper" :indicator-dots="imageList.length > 1" :autoplay="false"
                :current="currentIndex" @change="onSwiperChange">
                <swiper-item v-for="(img, index) in imageList" :key="index" @click="onPreviewImage(index)">
                    <u-image :src="img" width="100%" height="750rpx" mode="aspectFill"></u-image>
                </swiper-item>
            </swiper>

            <view class="thumbnail-list" v-if="imageList.length > 1">
                <scroll-view scroll-x="true" class="thumbnail-scroll">
                    <view class="thumbnail-wrap">
                        <view v-for="(img, index) in imageList" :key="index" class="thumbnail-item"
                            :class="{ active: currentIndex === index }" @click="onThumbnailClick(index)">
                            <u-image :src="img" width="120rpx" height="120rpx" border-radius="8rpx"
                                mode="aspectFill"></u-image>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <view class="content-section">
                <view class="price-row">
                    <view class="price-info">
                        <text class="price-symbol">¥</text>
                        <text class="price-value">{{ formatPrice(product.price) }}</text>
                    </view>
                    <u-tag :text="product.categoryName || product.categoryValue" type="primary" size="mini" plain>
                    </u-tag>
                </view>

                <view class="name-section">
                    <text class="product-name">{{ product.name }}</text>
                    <text class="product-remark" v-if="product.remark">{{ product.remark }}</text>
                </view>

                <view class="status-section">
                    <view class="status-item">
                        <text class="status-label">库存</text>
                        <text class="status-value" :class="getStockClass">{{ getStockText }}</text>
                    </view>
                    <view class="status-item">
                        <text class="status-label">月售</text>
                        <text class="status-value">{{ product.monthlySales || 0 }} 件</text>
                    </view>
                </view>

                <view class="divider"></view>

                <view class="description-section">
                    <text class="section-title">商品描述</text>
                    <text class="description-text">{{ product.description || '暂无商品描述' }}</text>
                </view>
            </view>
        </block>

        <view v-else-if="!loading" class="empty-state">
            <u-icon name="file-text" size="100" color="#ddd"></u-icon>
            <text class="empty-text">商品不存在或已下架</text>
            <u-button type="error" size="medium" plain @click="goBack">返回首页</u-button>
        </view>
    </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getApi } from '@/utils/api'
import { checkShopStatus } from '@/utils/go'

const loading = ref(true)
const product = ref(null)
const currentIndex = ref(0)

const imageList = computed(() => {
    if (!product.value) return []
    if (product.value.images && typeof product.value.images === 'string') {
        return product.value.images.split('|').slice(0, 9)
    }
    if (Array.isArray(product.value.images)) {
        return product.value.images.slice(0, 9)
    }
    return []
})

const getStockClass = computed(() => {
    if (!product.value) return ''
    const stock = product.value.stock
    if (stock === 0) return 'stock-empty'
    if (stock <= 10) return 'stock-low'
    return 'stock-normal'
})

const getStockText = computed(() => {
    if (!product.value) return ''
    const stock = product.value.stock
    if (stock === 0) return '缺货'
    if (stock <= 10) return `库存紧张(${stock})`
    return `${stock} 件`
})

const fetchProductDetail = (id) => {
    loading.value = true
    uni.request({
        url: getApi('/user/product/detail'),
        method: 'GET',
        data: { id },
        success: (res) => {
            checkShopStatus(res.data.code, res.data.message)
            if (res.statusCode === 200 && res.data.code === 200) {
                product.value = res.data.data
            } else {
                product.value = null
                uni.showToast({
                    title: res.data?.message || '获取商品详情失败',
                    icon: 'none'
                })
            }
            currentIndex.value = 0
            loading.value = false
        },
        fail: (err) => {
            product.value = null
            loading.value = false
            uni.showToast({
                title: '网络错误，请重试',
                icon: 'none'
            })
            console.error('获取商品详情失败:', err)
        }
    })
}

const onSwiperChange = (e) => {
    currentIndex.value = e.detail.current
}

const onThumbnailClick = (index) => {
    currentIndex.value = index
}

const onPreviewImage = (index) => {
    uni.previewImage({
        current: index,
        urls: imageList.value
    })
}

const goBack = () => {
    uni.navigateBack()
}

// 格式化价格，确保两位小数
const formatPrice = (price) => {
    const num = parseFloat(price) || 0;
    return num.toFixed(2);
}

onLoad((options) => {
    const productId = options.id || 1
    fetchProductDetail(productId)
})
</script>

<style lang="scss" scoped>
.container {
    min-height: 94vh;
    background: #f5f5f5;
}

.image-swiper {
    width: 100%;
    height: 750rpx;
    background: #fff;
}

.thumbnail-list {
    background: #fff;
    padding: 16rpx 0;
}

.thumbnail-scroll {
    width: 100%;
    white-space: nowrap;
}

.thumbnail-wrap {
    display: flex;
    padding: 0 24rpx;
    gap: 16rpx;
}

.thumbnail-item {
    flex-shrink: 0;
    border: 2rpx solid #eeeeee;
    border-radius: 8rpx;
    overflow: hidden;
    transition: border-color 0.2s;

    &.active {
        border-color: #e1251b;
    }
}

.content-section {
    background: #fff;
    margin-top: 20rpx;
    padding: 24rpx;
}

.price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24rpx;
}

.price-info {
    display: flex;
    align-items: baseline;
}

.price-symbol {
    font-size: 28rpx;
    color: #e1251b;
    font-weight: 600;
}

.price-value {
    font-size: 48rpx;
    color: #e1251b;
    font-weight: 600;
}

.name-section {
    margin-bottom: 24rpx;
}

.product-name {
    display: block;
    font-size: 32rpx;
    color: #333;
    font-weight: 600;
    margin-bottom: 8rpx;
}

.product-remark {
    display: block;
    font-size: 26rpx;
    color: #888;
}

.status-section {
    display: flex;
    gap: 48rpx;
    margin-bottom: 24rpx;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.status-label {
    font-size: 26rpx;
    color: #999;
}

.status-value {
    font-size: 26rpx;
    color: #52c41a;

    &.stock-normal {
        color: #52c41a;
    }

    &.stock-low {
        color: #ff9800;
    }

    &.stock-empty {
        color: #ff4d4f;
    }
}

.divider {
    height: 1rpx;
    background: #f5f5f5;
    margin: 24rpx 0;
}

.description-section {
    .section-title {
        display: block;
        font-size: 28rpx;
        color: #333;
        font-weight: 600;
        margin-bottom: 16rpx;
    }

    .description-text {
        display: block;
        font-size: 28rpx;
        color: #666;
        line-height: 1.6;
    }
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 200rpx 0;

    .empty-text {
        font-size: 28rpx;
        color: #999;
        margin: 24rpx 0 40rpx;
    }
}
</style>
