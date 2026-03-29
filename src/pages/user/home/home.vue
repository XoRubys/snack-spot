<template>
   <view class="container">
      <!-- 加载弹窗 -->
      <u-loading-popup v-model="loading" color="#e1251b" mode="circle" text="加载中..." size="56" />
      <!-- 顶部区域 -->
      <view class="top">
         <!-- 地址信息 -->
         <u-icon class="address" name="map" size="30" :label="config.address || '宿舍地址'" label-color="#fff"
            label-size="30" color="#fff"></u-icon>
         <!-- 搜索框 -->
         <u-search bg-color="#fff" class="search" :animation="true" v-model="searchKeyword" @change="onSearch"
            :show-action="false" input-align="center" placeholder="搜索零食"></u-search>
      </view>
      <!-- 公告栏 -->
      <u-notice-bar type="warning" v-if="config.notice" :list="[config.notice]"></u-notice-bar>
      <!-- 分类导航 -->
      <up-sticky offset-top=-45>
         <u-tabs :list="categories" :is-scroll="true" :current="current" @change="change"
            active-color="#e1251b"></u-tabs>
      </up-sticky>
      <!-- 商品列表 -->
      <view class="product-list">
         <!-- 搜索结果商品列表 -->
         <template v-if="searchedProducts.length > 0">
            <view v-for="product in searchedProducts" :key="product.id" class="product-card"
               @click="handleProductClick(product)">
               <view class="card-content">
                  <!-- 商品图片 -->
                  <view class="product-image">
                     <u-image :src="product.image" width="160rpx" height="160rpx" border-radius="12rpx"
                        mode="aspectFill">
                     </u-image>
                  </view>
                  <!-- 商品信息 -->
                  <view class="product-info">
                     <!-- 商品名称 -->
                     <view class="info-row">
                        <rich-text v-if="searchKeyword" :nodes="getHighlightedName(product.name)"
                           class="product-name"></rich-text>
                        <u-text v-else :text="product.name" size="32rpx" color="#333" lines="1"
                           class="product-name"></u-text>
                        <!-- 商品备注 -->
                        <view class="remark-section">
                           <u-text :text="product.remark" size="26rpx" color="#888" class="remark-text"></u-text>
                           <u-icon name="arrow-right" size="26rpx" color="#999"></u-icon>
                        </view>
                     </view>
                     <!-- 商品标签 -->
                     <view class="tags-row">
                        <u-tag :text="'月售' + product.monthlySales" type="primary" size="mini" plain></u-tag>
                        <u-tag :text="'库存' + product.stock" type="warning" size="mini" plain></u-tag>
                     </view>
                     <!-- 商品价格和数量 -->
                     <view class="price-row">
                        <view class="price-section">
                           <u-text mode="price" :text="product.price" size="36rpx" color="red"></u-text>
                        </view>
                        <!-- 数量调整器 -->
                        <view class="quantity-section">
                           <u-number-box :model-value="product.quantity" :min="0" :max="product.stock"
                              @change="(val) => handleQuantityChange(val, product)"></u-number-box>
                        </view>
                     </view>
                  </view>
               </view>
            </view>
         </template>
         <!-- 空状态 -->
         <view v-else class="empty-wrap">
            <view class="empty-icon">
               <u-icon name="file-text" size="80" color="#ddd"></u-icon>
            </view>
            <view class="empty-text">
               <text class="empty-title">暂无商品</text>
               <text class="empty-desc">该分类暂无商品，你可以试试其他的</text>
            </view>
         </view>
      </view>
      <!-- 购物车栏 -->
      <view v-if="totalCount > 0" class="cart-bar">
         <!-- 购物车信息 -->
         <view class="cart-info">
            <!-- 购物车图标 -->
            <view class="cart-icon">
               <u-icon name="shopping-cart" size="48" color="#fff"></u-icon>
               <!-- 购物车数量徽章 -->
               <view v-if="totalCount > 0" class="badge">{{ totalCount }}</view>
            </view>
            <!-- 购物车价格 -->
            <view class="cart-price">
               <view class="price-row">
                  <text class="price-label">合计:</text>
                  <u-text class="price-value" mode="price" color="red" :text="totalPrice"></u-text>
               </view>
               <!-- 起送价提示 -->
               <text v-if="!isMinPriceReached" class="min-price-tip">还差¥{{ minPriceDiff }}起送</text>
            </view>
         </view>
         <!-- 结算按钮 -->
         <view class="cart-action">
            <u-button type="error" :disabled="!isMinPriceReached || totalCount === 0" @click="handleCheckout">
               {{ isMinPriceReached ? '去结算' : `¥${Number(config.startPrice).toFixed(2)}起送` }}
            </u-button>
         </view>
      </view>
   </view>
</template>

<script setup>
import { getApi } from '@/utils/api'
import { checkShopStatus } from '@/utils/go'
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

// 搜索关键词
const searchKeyword = ref('')
// 是否已搜索
const hasSearched = ref(false)
// 当前选中的分类索引
const current = ref(0)
// 加载状态
const loading = ref(true)

// 商品数据
const productData = reactive([])
// 分类列表
const categories = ref([])
// 系统配置
const config = ref({
   deliveryFeeMin: 0,
   deliveryFeePercent: 0,
   startPrice: 0,
   address: '',
   notice: ''
})

// 过滤后的商品列表（按分类）
const filteredProducts = computed(() => {
   if (current.value === 0) {
      return productData
   }
   const categoryValue = categories.value[current.value]?.value
   if (!categoryValue) {
      return productData
   }
   return productData.filter(product => product.categoryValue === categoryValue)
})

// 搜索后的商品列表
const searchedProducts = computed(() => {
   const keyword = searchKeyword.value.trim()
   if (!hasSearched.value || !keyword) {
      return filteredProducts.value
   }
   const lowerKeyword = keyword.toLowerCase()
   return filteredProducts.value.filter(product =>
      product.name.toLowerCase().includes(lowerKeyword) ||
      product.remark.toLowerCase().includes(lowerKeyword)
   )
})

/**
 * 处理搜索
 */
const onSearch = () => {
   hasSearched.value = true
}

/**
 * 处理分类切换
 * @param {number} index - 选中的分类索引
 */
const change = (index) => {
   hasSearched.value = false
   current.value = index
}

/**
 * 获取数据
 */
const fetchData = () => {
   uni.request({
      url: getApi('/user/index/list'),
      method: 'GET',
      success: (res) => {
         checkShopStatus(res.data.code, res.data.message)
         if (res.statusCode === 200 && res.data.code === 200) {
            const newProducts = res.data.data.products
            newProducts.forEach((item) => {
               item.quantity = 0
            })
            productData.splice(0, productData.length, ...newProducts)
            categories.value = res.data.data.categories
            config.value = res.data.data.config
            uni.setStorageSync('config', config.value)
            config.value.address = config.value.address || '宿舍地址'
         } else {
            uni.showToast({
               title: res.data?.message || '加载失败',
               icon: 'none'
            })
         }
         loading.value = false
      },
      fail: (err) => {
         loading.value = false
         uni.showToast({
            title: err.message || '加载失败',
            icon: 'none'
         })
      }
   })
}

/**
 * 处理数量变化
 * @param {number} value - 新的数量
 * @param {Object} product - 商品对象
 */
const handleQuantityChange = (value, product) => {
   const quantity = typeof value === 'object' ? value.value : value
   const foundProduct = productData.find(p => p.id === product.id)
   if (foundProduct) {
      foundProduct.quantity = quantity
   }
}

// 计算购物车商品总数
const totalCount = computed(() => {
   return productData.reduce((sum, product) => sum + product.quantity, 0)
})

// 计算购物车总价
const totalPrice = computed(() => {
   return productData
      .reduce((sum, product) => sum + product.quantity * product.price, 0)
      .toFixed(2)
})

// 计算是否达到起送价
const isMinPriceReached = computed(() => {
   const total = productData.reduce((sum, product) => sum + product.quantity * product.price, 0)
   return total >= config.value.startPrice
})

// 计算距离起送价的差额
const minPriceDiff = computed(() => {
   const total = productData.reduce((sum, product) => sum + product.quantity * product.price, 0)
   return (config.value.startPrice - total).toFixed(2)
})

/**
 * 获取高亮显示的商品名称
 * @param {string} name - 商品名称
 * @returns {string} 高亮后的 HTML 字符串
 */
const getHighlightedName = (name) => {
   if (!searchKeyword.value?.trim()) {
      return name
   }

   const text = name
   const keyword = searchKeyword.value.trim()
   const lowerText = text.toLowerCase()
   const lowerKeyword = keyword.toLowerCase()
   let lastIndex = 0
   let index = lowerText.indexOf(lowerKeyword)
   let result = ''

   while (index !== -1) {
      if (index > lastIndex) {
         result += text.slice(lastIndex, index)
      }
      result += `<span style="color: #e1251b; font-weight: bold;">${text.slice(index, index + keyword.length)}</span>`
      lastIndex = index + keyword.length
      index = lowerText.indexOf(lowerKeyword, lastIndex)
   }

   if (lastIndex < text.length) {
      result += text.slice(lastIndex)
   }

   return result
}

/**
 * 处理商品点击
 * @param {Object} product - 商品对象
 */
const handleProductClick = (product) => {
   console.log('点击商品:', product)
   uni.navigateTo({
      url: `/pages/user/product/detail?id=${product.id}`
   })
}

/**
 * 处理结算
 */
const handleCheckout = () => {
   uni.setStorageSync('cartGoods', productData.filter(product => product.quantity > 0))
   uni.navigateTo({
      url: '/pages/user/order/submit'
   })
}

// 页面加载时获取数据
onLoad(() => {
   fetchData()
})
</script>

<style scoped lang="scss">
.container {
   height: 94vh;
   background-color: #f8f8f8;
}

.top {
   background-color: #e1251b;

   .address {
      padding: 30rpx 15rpx 10rpx 15rpx;
   }

   .search {
      padding: 10rpx 20rpx 20rpx;
   }
}

.product-list {
   padding: 24rpx;
   padding-bottom: 240rpx;
}

.product-card {
   background: #fff;
   border-radius: 16rpx;
   padding: 24rpx;
   margin-bottom: 24rpx;
   cursor: pointer;
   transition: background-color 0.2s ease;

   &:active {
      background-color: #f9f9f9;
   }
}

.card-content {
   display: flex;
   gap: 24rpx;
}

.product-image {
   flex-shrink: 0;
}

.product-info {
   flex: 1;
   display: flex;
   flex-direction: column;
   justify-content: space-between;
   min-height: 160rpx;
}

.info-row {
   display: flex;
   align-items: center;
   justify-content: space-between;
   gap: 16rpx;
}

.product-name {
   flex: 1;
   min-width: 0;
   font-size: 32rpx;
   color: #333;
}

.remark-section {
   display: flex;
   align-items: center;
   gap: 8rpx;
   flex-shrink: 0;
   padding: 8rpx 16rpx;
   background: #eee;
   border-radius: 50rpx 0 0 50rpx;
   box-shadow: 0 0.5rpx 2rpx rgba(0, 0, 0, 0.05);
}

.remark-text {
   display: flex;
   align-items: center;
}

.tags-row {
   display: flex;
   gap: 16rpx;
   margin-top: 8rpx;
}

.price-row {
   display: flex;
   align-items: center;
   justify-content: space-between;
   margin-top: 16rpx;
}

.price-section {
   display: flex;
   align-items: baseline;
   gap: 4rpx;
}

.empty-wrap {
   display: flex;
   flex-direction: column;
   align-items: center;
   padding: 100rpx 0;
}

.empty-icon {
   margin-bottom: 24rpx;
}

.empty-text {
   display: flex;
   flex-direction: column;
   align-items: center;
   gap: 12rpx;
}

.empty-title {
   font-size: 32rpx;
   color: #333;
}

.empty-desc {
   font-size: 26rpx;
   color: #999;
}

.cart-bar {
   margin: 10rpx;
   border-radius: 10rpx;
   position: fixed;
   bottom: 120rpx;
   left: 0;
   right: 0;
   display: flex;
   align-items: center;
   justify-content: space-between;
   padding: 20rpx 30rpx;
   background-color: #fff;
   box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.1);
   z-index: 999;
}

.cart-info {
   display: flex;
   align-items: center;
   gap: 30rpx;
}

.cart-icon {
   position: relative;
   width: 80rpx;
   height: 80rpx;
   background-color: #e1251b;
   border-radius: 50%;
   display: flex;
   align-items: center;
   justify-content: center;
}

.badge {
   position: absolute;
   top: -10rpx;
   right: -10rpx;
   min-width: 36rpx;
   height: 36rpx;
   padding: 0 8rpx;
   background-color: #ff4d4f;
   color: #fff;
   font-size: 20rpx;
   border-radius: 18rpx;
   display: flex;
   align-items: center;
   justify-content: center;
   border: 2rpx solid #fff;
}

.cart-price {
   display: flex;
   flex-direction: column;
}

.price-label {
   font-size: 28rpx;
   color: #666;
}

.price-value {
   font-size: 40rpx;
   font-weight: bold;
   color: #e1251b;
}

.min-price-tip {
   font-size: 24rpx;
   color: #999;
   margin-top: 4rpx;
}
</style>
