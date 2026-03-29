<template>
   <tab-navbar title="注册" :show-back="true"></tab-navbar>
   <!-- 注册页面容器 -->
   <view class="register-container">
      <!-- 注册卡片 -->
      <view class="register-card">
         <!-- 注册头部 -->
         <view class="register-header">
            <text class="register-title">注册账号</text>
            <text class="register-subtitle">校园零食铺系统</text>
         </view>
         <!-- 注册表单 -->
         <n-form ref="formRef" :model="formData" :rules="rules" size="large" label-placement="top" show-feedback>
            <!-- 姓名输入 -->
            <n-form-item path="username" label="姓名">
               <n-input v-model:value="formData.username" placeholder="请输入姓名" clearable :maxlength="25" />
            </n-form-item>
            <!-- 手机号输入 -->
            <n-form-item path="phone" label="手机号">
               <n-input v-model:value="formData.phone" placeholder="请输入手机号" clearable :maxlength="11" />
            </n-form-item>
            <!-- 寝室号输入 -->
            <n-form-item path="dormitory" label="寝室号">
               <n-input v-model:value="formData.dormitory" placeholder="请输入寝室号" clearable :maxlength="3"
                  @input="filterDormitoryInput" />
            </n-form-item>
            <!-- 密码输入 -->
            <n-form-item path="password" label="密码">
               <n-input v-model:value="formData.password" type="password" placeholder="请输入密码" show-password-on="click"
                  clearable :maxlength="20" @input="filterPasswordInput" />
            </n-form-item>
            <!-- 注册按钮 -->
            <n-button type="error" size="large" block @click="handleRegister" :loading="loading">
               注册
            </n-button>
         </n-form>
      </view>
   </view>
</template>

<script setup>
import { ref } from 'vue'
import { NForm, NFormItem, NInput, NButton } from 'naive-ui'
import { getApi } from '@/utils/api'

const formRef = ref()
const loading = ref(false)

const formData = ref({
   username: '',
   phone: '',
   dormitory: '',
   password: ''
})

const filterPasswordInput = (value) => {
   formData.value.password = value.replace(/[^a-zA-Z0-9]/g, '')
}

const filterDormitoryInput = (value) => {
   formData.value.dormitory = value.replace(/[^\d]/g, '')
}

const validatePhone = (value) => {
   if (!value) {
      return '请输入手机号'
   }
   if (!/^1[3-9]\d{9}$/.test(value)) {
      return '手机号格式不正确'
   }
   return null
}

const validateDormitory = (value) => {
   if (!value) {
      return '请输入寝室号'
   }
   if (!/^\d{1,3}$/.test(value)) {
      return '寝室号格式不正确'
   }
   return null
}

const validatePassword = (value) => {
   if (!value) {
      return '请输入密码'
   }
   if (value.length < 8) {
      return '密码长度不能少于8位'
   }
   if (value.length > 20) {
      return '密码长度不能超过20位'
   }
   if (!/^[a-zA-Z0-9]+$/.test(value)) {
      return '密码只能包含字母和数字'
   }
   return null
}

const rules = {
   phone: {
      required: true,
      message: '请输入手机号',
      trigger: ['input', 'blur']
   },
   dormitory: {
      required: true,
      message: '请输入寝室号',
      trigger: ['input', 'blur']
   },
   password: {
      required: true,
      message: '请输入密码',
      trigger: ['input', 'blur']
   }
}

const handleRegister = async () => {
   const phoneError = validatePhone(formData.value.phone)
   if (phoneError) {
      uni.showToast({
         title: phoneError,
         icon: 'none',
         duration: 2000
      })
      return
   }

   const dormitoryError = validateDormitory(formData.value.dormitory)
   if (dormitoryError) {
      uni.showToast({
         title: dormitoryError,
         icon: 'none',
         duration: 2000
      })
      return
   }

   const passwordError = validatePassword(formData.value.password)
   if (passwordError) {
      uni.showToast({
         title: passwordError,
         icon: 'none',
         duration: 2000
      })
      return
   }

   if (!formData.value.username) {
      uni.showToast({
         title: '请输入姓名',
         icon: 'none',
         duration: 2000
      })
      return
   }

   try {
      await formRef.value?.validate()
      loading.value = true

      uni.request({
         url: getApi('/user/user/register'),
         method: 'POST',
         data: {
            username: formData.value.username,
            phone: formData.value.phone,
            dormitory: formData.value.dormitory,
            password: formData.value.password
         },
         header: {
            'Content-Type': 'application/json'
         },
         success: (res) => {
            if (res.statusCode === 200 && res.data.code === 200) {
               const userInfo = {
                  accessToken: res.data.data.accessToken
               }
               uni.setStorageSync('userInfo', userInfo)

               uni.showToast({
                  title: res.data?.message || '注册成功',
                  icon: 'success'
               })
               setTimeout(() => {
                  uni.navigateBack({
                     delta: 2
                  })
               }, 1500)
            } else {
               uni.showToast({
                  title: res.data?.message || '注册失败',
                  icon: 'none'
               })
            }
            loading.value = false
         },
         fail: (err) => {
            uni.showToast({
               title: err.errMsg || '网络请求失败',
               icon: 'none'
            })
            loading.value = false
         }
      })
   } catch (error) {
      console.error('表单验证失败', error)
   }
}
</script>

<style scoped lang="scss">
.register-container {
   min-height: 94vh;
   background-color: #f5f5f5;
   display: flex;
   align-items: center;
   justify-content: center;
   padding: 40rpx;
}

.register-card {
   width: 100%;
   max-width: 600rpx;
   background-color: #ffffff;
   border-radius: 24rpx;
   padding: 60rpx 40rpx;
   box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
}

.register-header {
   text-align: center;
   margin-bottom: 60rpx;

   .register-title {
      display: block;
      font-size: 48rpx;
      font-weight: bold;
      color: #333;
      margin-bottom: 16rpx;
   }

   .register-subtitle {
      display: block;
      font-size: 28rpx;
      color: #999;
   }
}
</style>