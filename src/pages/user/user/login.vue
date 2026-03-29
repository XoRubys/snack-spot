<template>
   <tab-navbar title="登录" :show-back="true"></tab-navbar>
   <!-- 登录页面容器 -->
   <view class="login-container">
      <!-- 登录卡片 -->
      <view class="login-card">
         <!-- 登录头部 -->
         <view class="login-header">
            <text class="login-title">欢迎登录</text>
            <text class="login-subtitle">校园零食铺系统</text>
         </view>
         <!-- 登录表单 -->
         <n-form ref="formRef" :model="formData" :rules="rules" size="large" label-placement="top" show-feedback>
            <!-- 手机号输入 -->
            <n-form-item path="phone" label="手机号">
               <n-input v-model:value="formData.phone" placeholder="请输入手机号" clearable :maxlength="11" />
            </n-form-item>
            <!-- 密码输入 -->
            <n-form-item path="password" label="密码">
               <n-input v-model:value="formData.password" type="password" placeholder="请输入密码" show-password-on="click"
                  clearable :maxlength="20" @input="filterPasswordInput" />
            </n-form-item>
            <!-- 登录按钮 -->
            <n-button type="error" size="large" block @click="handleLogin" :loading="loading">
               登录
            </n-button>
         </n-form>
         <!-- 登录底部 -->
         <view class="login-footer">
            <text class="register-link" @click="goToRegister">还没有账号？去注册</text>
         </view>
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
   phone: '',
   password: ''
})

const filterPasswordInput = (value) => {
   formData.value.password = value.replace(/[^a-zA-Z0-9]/g, '')
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
   return null
}

const rules = {
   phone: {
      required: true,
      message: '请输入手机号',
      trigger: ['input', 'blur']
   },
   password: {
      required: true,
      message: '请输入密码',
      trigger: ['input', 'blur']
   }
}

const handleLogin = async () => {
   const phoneError = validatePhone(formData.value.phone)
   if (phoneError) {
      uni.showToast({
         title: phoneError,
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

   try {
      await formRef.value?.validate()
      loading.value = true

      uni.request({
         url: getApi('/user/user/login'),
         method: 'POST',
         data: {
            phone: formData.value.phone,
            password: formData.value.password
         },
         header: {
            'Content-Type': 'application/json; charset=utf-8'
         },
         success: (res) => {
            if (res.statusCode === 200 && res.data.code === 200) {
               const userInfo = {
                  accessToken: res.data.data.accessToken
               }
               uni.setStorageSync('userInfo', userInfo)

               uni.showToast({
                  title: '登录成功',
                  icon: 'success'
               })
               setTimeout(() => {
                  uni.navigateBack()
               }, 1500)
            } else {
               uni.showToast({
                  title: res.data?.message || '登录失败',
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

const goToRegister = () => {
   uni.navigateTo({
      url: '/pages/user/user/register'
   })
}
</script>

<style scoped lang="scss">
.login-container {
   min-height: 94vh;
   background-color: #f5f5f5;
   display: flex;
   align-items: center;
   justify-content: center;
   padding: 40rpx;
}

.login-card {
   width: 100%;
   max-width: 600rpx;
   background-color: #ffffff;
   border-radius: 24rpx;
   padding: 48rpx;
   box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
}

.login-header {
   margin-bottom: 48rpx;
   text-align: center;
}

.login-title {
   font-size: 40rpx;
   font-weight: 600;
   color: #333;
   display: block;
   margin-bottom: 12rpx;
}

.login-subtitle {
   font-size: 28rpx;
   color: #999;
}

.login-footer {
   margin-top: 32rpx;
   text-align: center;
}

.register-link {
   font-size: 26rpx;
   color: #e1251b;
   cursor: pointer;
}
</style>