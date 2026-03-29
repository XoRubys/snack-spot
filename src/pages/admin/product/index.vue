<template>
   <tab-navbar title="商品管理" :show-back="true"></tab-navbar>
   <view class="container">
     
      <!-- 页面标题 -->
      <view class="header">
         <text class="title">商品管理</text>
         <view class="add-btn" @click="addProduct">
            <u-icon name="plus" size="32" color="#fff"></u-icon>
            <text>新增商品</text>
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

      <!-- 统计信息 -->
      <view class="stats-bar">
         <text class="stats-text">共 {{ filteredList.length }} 件商品</text>
         <text class="stats-text">已上架 {{ onlineCount }} 件</text>
      </view>

      <!-- 商品列表 -->
      <view class="product-list">
         <view v-for="item in filteredList" :key="item.id" class="product-item" @click="editProduct(item)">
            <image class="product-image" :src="item.image || '/static/images/default-product.png'" mode="aspectFill">
            </image>
            <view class="product-info">
               <view class="product-header">
                  <text class="product-name">{{ item.name }}</text>
                  <view class="status-tag" :class="item.status">
                     {{ item.status === 'online' ? '上架' : '下架' }}
                  </view>
               </view>
               <text class="product-category">{{ item.categoryName }}</text>
               <view class="product-price-row">
                  <text class="product-price">¥{{ formatPrice(item.price) }}</text>
                  <text class="product-stock" :class="{ 'low': item.stock < 10 }">库存 {{ item.stock }}</text>
               </view>
            </view>
            <view class="product-arrow">
               <u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
            </view>
         </view>
      </view>

      <!-- 空状态 -->
      <view v-if="filteredList.length === 0" class="empty-state">
         <u-icon name="shopping-cart" size="80" color="#ddd"></u-icon>
         <text class="empty-text">暂无商品数据</text>
         <text class="empty-tip">点击右上角按钮添加商品</text>
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
               <!-- 商品状态 -->
               <view class="filter-section">
                  <view class="section-header">
                     <view class="section-icon" style="background: #52c41a15;">
                        <u-icon name="checkmark-circle" size="28" color="#52c41a"></u-icon>
                     </view>
                     <text class="section-label">商品状态</text>
                  </view>
                  <view class="filter-options">
                     <view class="filter-option" :class="{ active: filterStatus === null }"
                        @click="filterStatus = null">
                        <text>全部</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterStatus === 'online' }"
                        @click="filterStatus = 'online'">
                        <text>上架中</text>
                     </view>
                     <view class="filter-option" :class="{ active: filterStatus === 'offline' }"
                        @click="filterStatus = 'offline'">
                        <text>已下架</text>
                     </view>
                  </view>
               </view>
               <!-- 商品分类 -->
               <view class="filter-section">
                  <view class="section-header">
                     <view class="section-icon" style="background: #1890ff15;">
                        <u-icon name="grid" size="28" color="#1890ff"></u-icon>
                     </view>
                     <text class="section-label">商品分类</text>
                  </view>
                  <view class="filter-options">
                     <view class="filter-option" :class="{ active: filterCategory === null }"
                        @click="filterCategory = null">
                        <text>全部</text>
                     </view>
                     <view v-for="cat in categoryList" :key="cat.id" class="filter-option"
                        :class="{ active: filterCategory === cat.value }" @click="filterCategory = cat.value">
                        <text>{{ cat.name }}</text>
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
import { ref, computed } from 'vue'
import { getApi, getAuth } from '@/utils/api'
import { onShow } from '@dcloudio/uni-app'

const searchKeyword = ref('');
const showFilter = ref(false);
const filterStatus = ref(null);
const filterCategory = ref(null);
const loading = ref(false);

const categoryList = ref([]);
const productList = ref([]);

const onlineCount = computed(() => {
   return productList.value.filter(item => item.status === 'online').length;
});

const filteredList = computed(() => {
   return productList.value.filter(item => {
      if (filterStatus.value && item.status !== filterStatus.value) {
         return false;
      }
      if (filterCategory.value && item.categoryValue !== filterCategory.value) {
         return false;
      }
      if (searchKeyword.value && !item.name.includes(searchKeyword.value)) {
         return false;
      }
      return true;
   });
});

const getCategoryList = async () => {
   try {
      const res = await uni.request({
         url: getApi('/admin/category/get'),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });
      console.log('分类接口返回:', res);
      const data = res.data || {};
      if (data.code === 200) {
         categoryList.value = data.data?.list?.map((item) => ({
            id: item.id,
            name: item.name,
            value: item.value,
            remark: item.remark
         })) || [];
      } else {
         uni.showToast({ title: data.message || '获取分类失败', icon: 'none' });
      }
   } catch (error) {
      console.error('获取分类列表失败:', error);
      uni.showToast({ title: '网络错误，请重试', icon: 'none' });
   }
};

const getProductList = async () => {
   loading.value = true;
   try {
      const res = await uni.request({
         url: getApi('/admin/product/getLists'),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });
      const data = res.data || {};
      if (data.code === 200) {
         productList.value = data.data?.list || [];
      } else {
         uni.showToast({ title: data.message || '获取商品列表失败', icon: 'none' });
      }
   } catch (error) {
      console.error('获取商品列表失败:', error);
      uni.showToast({ title: '网络错误，请重试', icon: 'none' });
   } finally {
      loading.value = false;
   }
};

const handleSearch = () => {
};

const addProduct = () => {
   uni.navigateTo({
      url: '/pages/admin/product/edit'
   });
};

const editProduct = (item) => {
   uni.navigateTo({
      url: `/pages/admin/product/edit?id=${item.id}`
   });
};

const resetFilter = () => {
   filterStatus.value = null;
   filterCategory.value = null;
};

const applyFilter = () => {
   showFilter.value = false;
};

// 格式化价格，确保两位小数
const formatPrice = (price) => {
   const num = parseFloat(price) || 0;
   return num.toFixed(2);
};

onShow(() => {
   getCategoryList();
   getProductList();
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
      gap: 16rpx;
      background: #fff;
      border-radius: 12rpx;
      padding: 20rpx 24rpx;

      .search-input {
         flex: 1;
         font-size: 28rpx;
         color: #333;
      }
   }

   .filter-btn {
      width: 88rpx;
      height: 88rpx;
      background: #fff;
      border-radius: 12rpx;
      display: flex;
      align-items: center;
      justify-content: center;
   }
}

.stats-bar {
   display: flex;
   gap: 30rpx;
   padding: 0 10rpx 20rpx;

   .stats-text {
      font-size: 24rpx;
      color: #666;
   }
}

.product-list {
   .product-item {
      display: flex;
      align-items: center;
      gap: 20rpx;
      background: #fff;
      border-radius: 16rpx;
      padding: 24rpx;
      margin-bottom: 20rpx;

      .product-image {
         width: 120rpx;
         height: 120rpx;
         border-radius: 12rpx;
         background: #f5f5f5;
         flex-shrink: 0;
      }

      .product-info {
         flex: 1;
         display: flex;
         flex-direction: column;
         gap: 8rpx;

         .product-header {
            display: flex;
            align-items: center;
            gap: 12rpx;

            .product-name {
               font-size: 30rpx;
               font-weight: 500;
               color: #333;
            }

            .status-tag {
               font-size: 22rpx;
               padding: 4rpx 12rpx;
               border-radius: 8rpx;

               &.online {
                  background: #52c41a15;
                  color: #52c41a;
               }

               &.offline {
                  background: #99999915;
                  color: #999;
               }
            }
         }

         .product-category {
            font-size: 24rpx;
            color: #666;
         }

         .product-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;

            .product-price {
               font-size: 32rpx;
               font-weight: 600;
               color: #e1251b;
            }

            .product-stock {
               font-size: 24rpx;
               color: #666;

               &.low {
                  color: #ff4d4f;
               }
            }
         }
      }

      .product-arrow {
         flex-shrink: 0;
      }
   }
}

.empty-state {
   display: flex;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   padding: 100rpx 40rpx;

   .empty-text {
      font-size: 30rpx;
      color: #999;
      margin-top: 20rpx;
   }

   .empty-tip {
      font-size: 26rpx;
      color: #bbb;
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

      .filter-section {
         background: #fff;
         border-radius: 16rpx;
         padding: 24rpx;
         margin-bottom: 20rpx;

         .section-header {
            display: flex;
            align-items: center;
            gap: 16rpx;
            margin-bottom: 24rpx;

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

            .filter-option {
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
