<template>
    <tab-navbar title="信息管理" :show-back="true"></tab-navbar>
    <!-- 编辑用户信息页面容器 -->
    <view class="edit-container">
        <!-- 表单区域 -->
        <view class="form-section">
            <n-form ref="formRef" :model="formData" :rules="rules" size="large">
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
                    <n-input v-model:value="formData.dormitory" placeholder="请输入寝室号" clearable :maxlength="3" />
                </n-form-item>
            </n-form>
        </view>

        <!-- 保存按钮区域 -->
        <view class="button-section">
            <n-button type="error" size="large" block @click="handleSave" :loading="loading">
                保存修改
            </n-button>
        </view>

        <!-- 退出登录按钮区域 -->
        <view class="logout-section">
            <n-button type="default" size="large" block @click="handleLogout">
                退出登录
            </n-button>
        </view>
    </view>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { NForm, NFormItem, NInput, NButton } from 'naive-ui'
import { getApi, getAuth } from '@/utils/api'
import { checkShopStatus, goToLogin } from '@/utils/go'

// 表单引用
const formRef = ref()
// 加载状态
const loading = ref(false)

// 表单数据
const formData = ref({
    username: '',
    phone: '',
    dormitory: ''
})

// 表单验证规则
const rules = {
    username: {
        required: true,
        message: '请输入姓名',
        trigger: ['input', 'blur']
    },
    phone: {
        required: true,
        message: '请输入手机号',
        trigger: ['input', 'blur']
    },
    dormitory: {
        required: true,
        message: '请输入寝室号',
        trigger: ['input', 'blur']
    }
}

/**
 * 获取用户信息
 */
const fetchUserInfo = () => {
    uni.request({
        url: getApi('/user/user/info'),
        method: 'GET',
        header: {
            'Content-Type': 'application/json; charset=utf-8',
            'Authorization': getAuth()
        },
        success: (res) => {
            if (res.statusCode === 200 && res.data.code === 200) {
                const data = res.data.data
                formData.value.username = data.username
                formData.value.phone = data.phone
                formData.value.dormitory = data.dormitory
            } else {
                uni.showToast({
                    title: res.data?.message || '获取用户信息失败',
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

/**
 * 保存修改
 */
const handleSave = async () => {
    try {
        await formRef.value?.validate()
        loading.value = true

        uni.request({
            url: getApi('/user/user/edit'),
            method: 'POST',
            data: {
                username: formData.value.username,
                phone: formData.value.phone,
                dormitory: formData.value.dormitory
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
    } catch (error) {
        console.error('表单验证失败:', error)
    }
}

// 页面加载时获取用户信息
onLoad(() => {
    fetchUserInfo()
})

/**
 * 处理退出登录
 */
const handleLogout = () => {
    uni.showModal({
        title: '提示',
        content: '确定要退出登录吗？',
        success: (res) => {
            if (res.confirm) {
                uni.removeStorageSync('userInfo')
                uni.removeStorageSync('cartGoods')
                uni.showToast({
                    title: '已退出登录',
                    icon: 'success',
                    success: () => {
                        goToLogin(1500)
                    }
                })
            }
        }
    })
}
</script>

<style scoped lang="scss">
// 编辑页面容器
.edit-container {
    min-height: 94vh; // 最小高度：视口高度94%
    background-color: #f5f5f5; // 背景色：浅灰色
    padding: 20rpx; // 内边距：20rpx
}

// 表单区域
.form-section {
    background-color: #ffffff; // 背景色：白色
    border-radius: 16rpx; // 圆角：16rpx
    padding: 32rpx; // 内边距：32rpx
    margin-bottom: 20rpx; // 底部外边距：20rpx
}

// 按钮区域
.button-section {
    padding: 20rpx; // 内边距：20rpx
}

// 退出登录按钮区域
.logout-section {
    padding: 20rpx; // 内边距：20rpx
    margin-top: 40rpx; // 顶部外边距：40rpx
}
</style>
