// 跳转登录页
export const goToLogin = (time) => {
    setTimeout(() => {
        uni.navigateTo({ url: '/pages/user/user/login' })
    }, time || 1500)
}

// 检查店铺状态
// 用法: checkShopStatus(res.data.code, res.data.message)
export const checkShopStatus = (code, message) => {
    if (code === 601) {
        const msg = encodeURIComponent(message || '店铺已关闭')
        uni.reLaunch({
            url: `/pages/user/shop/status?m=${msg}`
        })
    }
}
