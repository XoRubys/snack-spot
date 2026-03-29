<template>
    <view class="navbar">
        <view class="navbar-left" @click="onBack">
            <u-icon v-if="showBack" name="arrow-left" size="38" color="#333"></u-icon>
        </view>
        <view class="navbar-center">
            <text class="navbar-title">{{ title }}</text>
        </view>
        <view class="navbar-right">
            <slot name="right"></slot>
        </view>
    </view>
</template>

<script setup>
const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    showBack: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['back'])

const onBack = () => {
    emit('back')
    uni.navigateBack()
}
</script>

<style scoped>
.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 6vh;
    padding: 0 15px;
    background-color: #fafafa;
}

.navbar-left {
    width: 40px;
    display: flex;
    align-items: center;
}

.navbar-center {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.navbar-title {
    font-size: 16px;
    font-weight: 500;
    color: #333;
}

.navbar-right {
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
</style>
