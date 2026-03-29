export const API_BASE_URL = 'https://youdomain.com/api/v1'

// 获取API路径
export function getApi(url) {
   return API_BASE_URL + url
}

// 获取认证Token
export function getAuth() {
   const user = uni.getStorageSync('userInfo') || {}
   const accessToken = user.accessToken ? 'Bearer ' + user.accessToken : ''
   return accessToken
}