<script setup>
import { onLaunch } from '@dcloudio/uni-app'

// 应用启动时执行
onLaunch(() => {
  hideLoading()
})

// 隐藏加载动画
const hideLoading = () => {
  const loading = document.getElementById('app-loading')
  if (loading) {
    loading.classList.add('fade-out')
    setTimeout(() => loading.style.display = 'none', 300)
  }
}
</script>
<style lang="scss">
// 导入uview-pro的组件样式
@import "uview-pro/index.scss";
</style>
