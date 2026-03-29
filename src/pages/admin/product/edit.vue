<template>
	<tab-navbar title="商品编辑" :show-back="true"></tab-navbar>
	<view class="container">
      <!-- 基本信息卡片 -->
      <view class="form-card">
         <view class="card-header">
            <u-icon name="info-circle-fill" size="40" color="#1890ff"></u-icon>
            <text class="card-title">基本信息</text>
         </view>

         <view class="form-item">
            <text class="form-label">商品名称</text>
            <input class="form-input" v-model="product.name" placeholder="请输入商品名称" placeholder-class="placeholder"
               maxlength="25" />
         </view>

         <view class="form-item">
            <text class="form-label">商品备注</text>
            <input class="form-input" v-model="product.remark" placeholder="请输入商品备注" placeholder-class="placeholder"
               maxlength="50" />
         </view>

         <view class="form-item">
            <text class="form-label">商品描述</text>
            <textarea class="form-textarea" v-model="product.description" placeholder="请输入商品描述"
               placeholder-class="placeholder" maxlength="255" />
            <text class="textarea-count">{{ product.description?.length || 0 }}/255</text>
         </view>
      </view>

      <!-- 分类与价格卡片 -->
      <view class="form-card">
         <view class="card-header">
            <u-icon name="grid-fill" size="40" color="#52c41a"></u-icon>
            <text class="card-title">分类与价格</text>
         </view>

         <view class="form-item">
            <text class="form-label">商品分类</text>
            <view class="picker-wrap" @click="showCategoryPicker = true">
               <text class="picker-text" :class="{ 'placeholder': !product.category_value }">
                  {{ selectedCategoryName || '请选择商品分类' }}
               </text>
               <u-icon name="arrow-right" size="28" color="#999"></u-icon>
            </view>
         </view>

         <view class="form-item">
            <text class="form-label">商品售价</text>
            <view class="input-wrap">
               <text class="input-prefix">¥</text>
               <input class="form-input" v-model="product.price" type="digit" placeholder="0.00"
                  placeholder-class="placeholder" />
            </view>
         </view>
      </view>

      <!-- 商品图片卡片 -->
      <view class="form-card">
         <view class="card-header">
            <u-icon name="photo-fill" size="40" color="#ff9500"></u-icon>
            <text class="card-title">商品图片</text>
         </view>

         <view class="form-item">
            <text class="form-label">商品主图链接</text>
            <view class="image-input-wrap">
               <input class="form-input" v-model="product.image" placeholder="请输入第三方图片链接地址"
                  placeholder-class="placeholder" />
               <view v-if="product.image" class="input-action" @click="product.image = ''">
                  <u-icon name="close-circle-fill" size="36" color="#ccc"></u-icon>
               </view>
            </view>
            <view v-if="product.image" class="image-preview-wrap" @click="previewImage(product.image)">
               <image class="preview-img" :src="product.image" mode="aspectFill"></image>
            </view>
         </view>

         <view class="form-item">
            <text class="form-label">商品图片列表</text>
            <view class="image-link-input">
               <input class="form-input" v-model="newImageUrl" placeholder="输入图片链接后点击添加"
                  placeholder-class="placeholder" />
               <view class="add-link-btn" :class="{ 'disabled': !newImageUrl.trim() }" @click="addImageLink">
                  <u-icon name="plus" size="32" color="#fff"></u-icon>
                  <text>添加</text>
               </view>
            </view>
            <view v-if="imageList.length > 0" class="image-list-preview">
               <view v-for="(img, index) in imageList" :key="index" class="image-preview-item"
                  @click="previewImage(img)">
                  <image class="preview-img" :src="img" mode="aspectFill"></image>
                  <view class="image-delete" @click.stop="removeImage(index)">
                     <u-icon name="close" size="24" color="#fff"></u-icon>
                  </view>
                  <view class="image-index">{{ index + 1 }}</view>
               </view>
            </view>
            <text class="form-tip">已添加 {{ imageList.length }}/9 张图片</text>
         </view>
      </view>

      <!-- 商品状态卡片 -->
      <view class="form-card">
         <view class="card-header">
            <u-icon name="checkmark-circle-fill" size="40" color="#e1251b"></u-icon>
            <text class="card-title">商品状态</text>
         </view>

         <view class="form-item switch-item">
            <view class="switch-info">
               <text class="form-label">{{ product.status === 'online' ? '商品上架中' : '商品已下架' }}</text>
               <text class="form-desc">{{ product.status === 'online' ? '用户可以在首页看到并购买此商品' : '商品已下架，用户无法购买' }}</text>
            </view>
            <u-switch v-model="isOnline" active-color="#52c41a" inactive-color="#ff4d4f"
               @change="onStatusChange"></u-switch>
         </view>
      </view>

      <!-- 底部占位 -->
      <view class="footer-space"></view>

      <!-- 提交按钮 -->
      <view class="submit-bar">
         <view class="submit-btn" @click="submitProduct">
            <u-icon v-if="loading" name="reload" size="32" color="#fff" class="loading-icon"></u-icon>
            <text>{{ loading ? '保存中...' : (isEdit ? '保存修改' : '添加商品') }}</text>
         </view>
      </view>

      <!-- 分类选择器 -->
      <u-select v-model="showCategoryPicker" :list="categoryList" @confirm="onCategoryConfirm"></u-select>
   </view>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';

const isEdit = ref(false);
const productId = ref('');

const loading = ref(false);

const showCategoryPicker = ref(false);
const categoryList = ref([]);

const product = ref({
   name: '',
   remark: '',
   description: '',
   category_value: '',
   price: '',
   image: '',
   images: '',
   status: 'online',
});

const imageList = ref([]);

const newImageUrl = ref('');

const isOnline = computed({
   get: () => product.value.status === 'online',
   set: (val) => {
      product.value.status = val ? 'online' : 'offline';
   }
});

const selectedCategoryName = computed(() => {
   const category = categoryList.value.find(item => item.value === product.value.category_value);
   return category ? category.label : '';
});

const onStatusChange = (val) => {
   product.value.status = val ? 'online' : 'offline';
};

const addImageLink = () => {
   const url = newImageUrl.value.trim();
   if (!url) {
      uni.showToast({ title: '请输入图片链接', icon: 'none' });
      return;
   }
   if (imageList.value.length >= 9) {
      uni.showToast({ title: '最多添加9张图片', icon: 'none' });
      return;
   }
   if (!url.startsWith('http://') && !url.startsWith('https://')) {
      uni.showToast({ title: '请输入有效的图片链接', icon: 'none' });
      return;
   }
   imageList.value.push(url);
   newImageUrl.value = '';
};

const removeImage = (index) => {
   imageList.value.splice(index, 1);
};

const previewImage = (url) => {
   const urls = [url, ...imageList.value.filter(img => img !== url)];
   uni.previewImage({
      urls: urls,
      current: url
   });
};

const onCategoryConfirm = (e) => {
   if (e && e.length > 0) {
      product.value.category_value = e[0].value;
   }
};

const validateForm = () => {
   if (!product.value.name.trim()) {
      uni.showToast({ title: '请输入商品名称', icon: 'none' });
      return false;
   }
   if (!product.value.remark.trim()) {
      uni.showToast({ title: '请输入商品备注', icon: 'none' });
      return false;
   }
   if (!product.value.description.trim()) {
      uni.showToast({ title: '请输入商品描述', icon: 'none' });
      return false;
   }
   if (!product.value.category_value) {
      uni.showToast({ title: '请选择商品分类', icon: 'none' });
      return false;
   }
   if (!product.value.price || parseFloat(product.value.price) <= 0) {
      uni.showToast({ title: '请输入正确的商品售价', icon: 'none' });
      return false;
   }
   if (!product.value.image) {
      uni.showToast({ title: '请上传商品主图', icon: 'none' });
      return false;
   }
   return true;
};

const submitProduct = async () => {
   if (loading.value) return;
   if (!validateForm()) return;

   loading.value = true;

   const submitData = {
      name: product.value.name,
      remark: product.value.remark,
      description: product.value.description,
      category_value: product.value.category_value,
      price: parseFloat(product.value.price).toFixed(2),
      image: product.value.image,
      images: imageList.value.join('|'),
      status: product.value.status,
   };

   if (isEdit.value) {
      submitData.id = parseInt(productId.value);
   }

   try {
      const res = await uni.request({
         url: getApi('/admin/product/edit'),
         method: 'POST',
         header: {
            'Content-Type': 'application/json',
            'Authorization': getAuth()
         },
         data: submitData
      });
      const data = res.data;
      if (data.code === 200) {
         uni.showToast({
            title: isEdit.value ? '修改成功' : '添加成功',
            icon: 'success'
         });
         setTimeout(() => {
            uni.navigateBack();
         }, 1500);
      } else {
         uni.showToast({ title: data.message || '操作失败', icon: 'none' });
      }
   } catch (error) {
      console.error('提交失败:', error);
      uni.showToast({ title: '网络错误，请重试', icon: 'none' });
   } finally {
      loading.value = false;
   }
};

const getProductDetail = async (id) => {
   try {
      const res = await uni.request({
         url: getApi('/admin/product/getDetail?id=' + id),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });
      const data = res.data;
      if (data.code === 200) {
         const detail = data.data;
         product.value = {
            name: detail.name,
            remark: detail.remark,
            description: detail.description,
            category_value: detail.categoryValue,
            price: detail.price,
            image: detail.image,
            images: detail.images,
            status: detail.status,
         };
         if (detail.images) {
            imageList.value = detail.images.split('|').filter(img => img.trim());
         }
      }
   } catch (error) {
      console.error('获取商品详情失败:', error);
      uni.showToast({ title: '获取商品详情失败', icon: 'none' });
   }
};

const getCategoryList = async () => {
   try {
      const res = await uni.request({
         url: getApi('/admin/category/get'),
         method: 'GET',
         header: {
            'Authorization': getAuth()
         }
      });
      const data = res.data;
      if (data.code === 200) {
         categoryList.value = data.data.list.map(item => ({
            value: item.value,
            label: item.name
         }));
      } else {
         uni.showToast({ title: data.message || '获取分类失败', icon: 'none' });
      }
   } catch (error) {
      console.error('获取分类列表失败:', error);
      uni.showToast({ title: '网络错误，请重试', icon: 'none' });
   }
};

onLoad((options) => {
	getCategoryList();
	if (options.id) {
		isEdit.value = true;
		productId.value = options.id;
		getProductDetail(options.id);
	}
});
</script>

<style lang="scss" scoped>
.container {
   min-height: 94vh;
   background: #f5f5f5;
   padding: 20rpx;
}

// 表单卡片
.form-card {
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

         .input-prefix {
            font-size: 32rpx;
            color: #e1251b;
            font-weight: 600;
            margin-right: 12rpx;
         }

         .form-input {
            flex: 1;
            height: 88rpx;
            font-size: 30rpx;
            color: #333;
            background: transparent;
         }
      }

      .picker-wrap {
         display: flex;
         align-items: center;
         justify-content: space-between;
         height: 88rpx;
         background: #f8f8f8;
         border-radius: 12rpx;
         padding: 0 24rpx;

         .picker-text {
            font-size: 30rpx;
            color: #333;

            &.placeholder {
               color: #bbb;
            }
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

// 图片链接输入
.image-input-wrap {
   display: flex;
   align-items: center;
   background: #f8f8f8;
   border-radius: 12rpx;
   padding: 0 20rpx 0 0;

   .form-input {
      flex: 1;
      height: 88rpx;
      padding: 0 24rpx;
      font-size: 30rpx;
      color: #333;
      background: transparent;
   }

   .input-action {
      padding: 20rpx;
      display: flex;
      align-items: center;
      justify-content: center;
   }
}

.image-preview-wrap {
   margin-top: 20rpx;
   width: 200rpx;
   height: 200rpx;
   border-radius: 12rpx;
   overflow: hidden;
   border: 2rpx solid #f0f0f0;

   .preview-img {
      width: 100%;
      height: 100%;
   }
}

.image-link-input {
   display: flex;
   align-items: center;
   gap: 16rpx;

   .form-input {
      flex: 1;
      height: 88rpx;
      background: #f8f8f8;
      border-radius: 12rpx;
      padding: 0 24rpx;
      font-size: 30rpx;
      color: #333;
   }

   .add-link-btn {
      height: 88rpx;
      padding: 0 32rpx;
      background: linear-gradient(135deg, #e1251b 0%, #ff4d4f 100%);
      border-radius: 12rpx;
      display: flex;
      align-items: center;
      gap: 8rpx;

      text {
         font-size: 28rpx;
         color: #fff;
         font-weight: 500;
      }

      &.disabled {
         background: #ccc;
      }

      &:active:not(.disabled) {
         opacity: 0.9;
      }
   }
}

.image-list-preview {
   display: flex;
   flex-wrap: wrap;
   gap: 20rpx;
   margin-top: 20rpx;
}

.image-preview-item {
   position: relative;
   width: 200rpx;
   height: 200rpx;
   border-radius: 12rpx;
   overflow: hidden;
   border: 2rpx solid #f0f0f0;

   .preview-img {
      width: 100%;
      height: 100%;
   }

   .image-delete {
      position: absolute;
      top: 8rpx;
      right: 8rpx;
      width: 40rpx;
      height: 40rpx;
      background: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
   }

   .image-index {
      position: absolute;
      bottom: 8rpx;
      left: 8rpx;
      min-width: 36rpx;
      height: 36rpx;
      background: rgba(0, 0, 0, 0.5);
      border-radius: 18rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22rpx;
      color: #fff;
      padding: 0 10rpx;
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
