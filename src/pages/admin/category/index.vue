<template>
   <tab-navbar title="分类管理" :show-back="true"></tab-navbar>
   <view class="container">
      <!-- 页面标题 -->
      <view class="header">
         <text class="title">分类管理</text>
         <view class="add-btn" @click="showAddModal">
            <u-icon name="plus" size="32" color="#fff"></u-icon>
            <text>新增分类</text>
         </view>
      </view>

      <!-- 分类列表 -->
      <view class="category-list">
         <view v-for="(item, index) in categoryList" :key="item.id" class="category-item">
            <view class="item-left">
               <view class="sort-num">{{ index + 1 }}</view>
               <view class="category-info">
                  <text class="category-name">{{ item.name }}</text>
                  <text class="category-value">值: {{ item.value }}</text>
                  <text class="category-remark">{{ item.remark || '暂无备注' }}</text>
               </view>
            </view>
            <view class="item-right">
               <view class="action-btns">
                  <view class="action-btn edit" @click="editCategory(item)">
                     <u-icon name="edit-pen" size="32" color="#1890ff"></u-icon>
                  </view>
                  <view class="action-btn delete" @click="deleteCategory(item)">
                     <u-icon name="trash" size="32" color="#ff4d4f"></u-icon>
                  </view>
               </view>
            </view>
         </view>
      </view>

      <!-- 空状态 -->
      <view v-if="categoryList.length === 0" class="empty-state">
         <u-icon name="list" size="80" color="#ddd"></u-icon>
         <text class="empty-text">暂无分类数据</text>
         <text class="empty-tip">点击右上角按钮添加分类</text>
      </view>

      <!-- 新增/编辑弹窗 -->
      <u-popup v-model="showModal" mode="center" border-radius="16" width="600rpx">
         <view class="modal-content">
            <view class="modal-header">
               <text class="modal-title">{{ isEdit ? '编辑分类' : '新增分类' }}</text>
            </view>
            <view class="modal-body">
               <view class="form-item">
                  <text class="form-label">分类名称</text>
                  <input class="form-input" v-model="form.name" placeholder="请输入分类名称" maxlength="20" />
               </view>
               <view class="form-item">
                  <text class="form-label">分类值 <text class="form-tip">用于程序识别</text></text>
                  <input class="form-input" v-model="form.value" placeholder="请输入分类值" maxlength="20" />
               </view>
               <view class="form-item">
                  <text class="form-label">备注</text>
                  <textarea class="form-textarea" v-model="form.remark" placeholder="请输入备注（选填）" maxlength="50" />
               </view>
            </view>
            <view class="modal-footer">
               <view class="btn cancel" @click="showModal = false">取消</view>
               <view class="btn confirm" @click="submitForm">确定</view>
            </view>
         </view>
      </u-popup>
   </view>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';

const categoryList = ref([]);
const loading = ref(false);

const showModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const form = reactive({
   name: '',
   value: '',
   remark: ''
});

// 获取分类列表
const getCategoryList = () => {
   loading.value = true;
   uni.request({
      url: getApi('/admin/category/get'),
      method: 'GET',
      header: { 'Authorization': getAuth() },
      success: (res) => {
         if (res.data.code === 200) {
            categoryList.value = res.data.data.list;
         } else {
            uni.showToast({ title: res.data.message || '获取失败', icon: 'none' });
         }
      },
      fail: () => {
         uni.showToast({ title: '网络错误', icon: 'none' });
      },
      complete: () => {
         loading.value = false;
      }
   });
};

// 重置表单
const resetForm = () => {
   form.name = '';
   form.value = '';
   form.remark = '';
   currentId.value = null;
   isEdit.value = false;
};

// 显示新增弹窗
const showAddModal = () => {
   resetForm();
   showModal.value = true;
};

const editCategory = (item) => {
   isEdit.value = true;
   currentId.value = item.id;
   form.name = item.name;
   form.value = item.value;
   form.remark = item.remark;
   showModal.value = true;
};

// 提交表单
const submitForm = () => {
   if (!form.name.trim()) {
      uni.showToast({ title: '请输入分类名称', icon: 'none' });
      return;
   }
   if (!form.value.trim()) {
      uni.showToast({ title: '请输入分类值', icon: 'none' });
      return;
   }

   const valuePattern = /^[a-z0-9_]+$/;
   if (!valuePattern.test(form.value)) {
      uni.showToast({ title: '分类值只能包含小写字母、数字和下划线', icon: 'none' });
      return;
   }

   uni.showLoading({ title: '提交中...' });

   const submitData = {
      name: form.name.trim(),
      value: form.value.trim().toLowerCase(),
      remark: form.remark.trim()
   };

   if (isEdit.value && currentId.value) {
      submitData.id = currentId.value;
   }

   uni.request({
      url: getApi('/admin/category/edit'),
      method: 'POST',
      header: {
         'Content-Type': 'application/json',
         'Authorization': getAuth()
      },
      data: submitData,
      success: (res) => {
         uni.hideLoading();
         if (res.data.code === 200) {
            uni.showToast({ title: isEdit.value ? '修改成功' : '添加成功', icon: 'success' });
            showModal.value = false;
            resetForm();
            getCategoryList();
         } else {
            uni.showToast({ title: res.data.message || '操作失败', icon: 'none' });
         }
      },
      fail: () => {
         uni.hideLoading();
         uni.showToast({ title: '网络错误', icon: 'none' });
      }
   });
};

const deleteCategory = (item) => {
   uni.showModal({
      title: '确认删除',
      content: `确定要删除分类「${item.name}」吗？`,
      confirmColor: '#ff4d4f',
      success: (res) => {
         if (res.confirm) {
            uni.showLoading({ title: '删除中...' });
            uni.request({
               url: getApi('/admin/category/delete'),
               method: 'POST',
               header: {
                  'Content-Type': 'application/json',
                  'Authorization': getAuth()
               },
               data: { id: item.id },
               success: (res) => {
                  uni.hideLoading();
                  if (res.data.code === 200) {
                     uni.showToast({ title: '删除成功', icon: 'success' });
                     getCategoryList();
                  } else {
                     uni.showToast({ title: res.data.message || '删除失败', icon: 'none' });
                  }
               },
               fail: () => {
                  uni.hideLoading();
                  uni.showToast({ title: '网络错误', icon: 'none' });
               }
            });
         }
      }
   });
};

onLoad(() => {
   getCategoryList();
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

.category-list {
   .category-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      border-radius: 16rpx;
      padding: 30rpx;
      margin-bottom: 20rpx;
      box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

      .item-left {
         display: flex;
         align-items: center;
         gap: 20rpx;
         flex: 1;

         .sort-num {
            width: 48rpx;
            height: 48rpx;
            background: #e1251b15;
            color: #e1251b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 600;
            flex-shrink: 0;
         }

         .category-info {
            display: flex;
            flex-direction: column;
            gap: 6rpx;

            .category-name {
               font-size: 30rpx;
               font-weight: 500;
               color: #333;
            }

            .category-value {
               font-size: 24rpx;
               color: #1890ff;
               background: #1890ff15;
               padding: 4rpx 12rpx;
               border-radius: 8rpx;
               align-self: flex-start;
            }

            .category-remark {
               font-size: 24rpx;
               color: #999;
            }
         }
      }

      .item-right {
         display: flex;
         flex-direction: column;
         align-items: flex-end;
         gap: 16rpx;

         .action-btns {
            display: flex;
            gap: 16rpx;

            .action-btn {
               width: 56rpx;
               height: 56rpx;
               border-radius: 50%;
               display: flex;
               align-items: center;
               justify-content: center;

               &.edit {
                  background: #1890ff15;
               }

               &.delete {
                  background: #ff4d4f15;
               }
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

// 弹窗样式
.modal-content {
   padding: 40rpx;

   .modal-header {
      text-align: center;
      margin-bottom: 30rpx;

      .modal-title {
         font-size: 32rpx;
         font-weight: 600;
         color: #333;
      }
   }

   .modal-body {
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

            .form-tip {
               font-size: 24rpx;
               color: #999;
               font-weight: normal;
            }
         }

         .form-input {
            background: #f8f8f8;
            border-radius: 12rpx;
            padding: 20rpx 24rpx;
            font-size: 28rpx;
            color: #333;
         }

         .form-textarea {
            background: #f8f8f8;
            border-radius: 12rpx;
            padding: 20rpx 24rpx;
            font-size: 28rpx;
            color: #333;
            height: 120rpx;
            width: 100%;
            box-sizing: border-box;
         }
      }
   }

   .modal-footer {
      display: flex;
      gap: 20rpx;
      margin-top: 40rpx;

      .btn {
         flex: 1;
         height: 80rpx;
         border-radius: 40rpx;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 28rpx;

         &.cancel {
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
