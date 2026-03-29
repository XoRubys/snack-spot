<template>
	<tab-navbar title="库存编辑" :show-back="true"></tab-navbar>
	<view class="container">
		<!-- 商品选择卡片 -->
		<view class="form-section">
			<view class="section-title">
				<view class="title-icon" style="background: #e1251b15;">
					<u-icon name="shopping-cart-fill" size="32" color="#e1251b"></u-icon>
				</view>
				<text class="title-text">商品信息</text>
			</view>
			<view class="form-card">
				<view class="form-item">
					<text class="form-label">选择商品</text>
					<view class="picker-wrap" @click="showProductPicker = true">
						<view class="picker-left">
							<u-icon name="grid" size="32" color="#999" class="picker-icon"></u-icon>
							<text class="picker-text" :class="{ 'placeholder': !form.product_id }">
								{{ selectedProductName || '请选择商品' }}
							</text>
						</view>
						<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
					</view>
				</view>
			</view>
		</view>

		<!-- 库存信息卡片 -->
		<view class="form-section">
			<view class="section-title">
				<view class="title-icon" style="background: #52c41a15;">
					<u-icon name="grid-fill" size="32" color="#52c41a"></u-icon>
				</view>
				<text class="title-text">库存信息</text>
			</view>
			<view class="form-card">
				<view class="form-row">
					<view class="form-item half">
						<text class="form-label">进货数量</text>
						<view class="input-with-icon">
							<u-icon name="plus-circle" size="28" color="#999" class="input-icon"></u-icon>
							<input class="form-input" v-model="form.quantity" type="number" placeholder="0"
								placeholder-class="placeholder" />
						</view>
					</view>
					<view class="form-item half">
						<text class="form-label">剩余数量</text>
						<view class="input-with-icon">
							<u-icon name="bag" size="28" color="#999" class="input-icon"></u-icon>
							<input class="form-input" v-model="form.remaining_quantity" type="number" placeholder="0"
								placeholder-class="placeholder" />
						</view>
					</view>
				</view>
				<view class="form-tip-row">
					<u-icon name="info-circle" size="24" color="#999"></u-icon>
					<text class="tip-text">添加时剩余数量通常与进货数量相同</text>
				</view>
			</view>
		</view>

		<!-- 进货信息卡片 -->
		<view class="form-section">
			<view class="section-title">
				<view class="title-icon" style="background: #ff950015;">
					<u-icon name="shopping-cart" size="32" color="#ff9500"></u-icon>
				</view>
				<text class="title-text">进货详情</text>
			</view>
			<view class="form-card">
				<view class="form-item">
					<text class="form-label">进货平台</text>
					<view class="picker-wrap" @click="showPlatformPicker = true">
						<view class="picker-left">
							<u-icon name="shop" size="32" color="#999" class="picker-icon"></u-icon>
							<text class="picker-text" :class="{ 'placeholder': !form.platform_name }">
								{{ platformText[form.platform_name] || '请选择进货平台' }}
							</text>
						</view>
						<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
					</view>
				</view>

				<view class="form-row">
					<view class="form-item half">
						<text class="form-label">平台订单号</text>
						<view class="input-with-icon">
							<u-icon name="order" size="28" color="#999" class="input-icon"></u-icon>
							<input class="form-input" v-model="form.platform_order_number" placeholder="订单号"
								placeholder-class="placeholder" maxlength="50" />
						</view>
					</view>
					<view class="form-item half">
						<text class="form-label">快递单号</text>
						<view class="input-with-icon">
							<u-icon name="car" size="28" color="#999" class="input-icon"></u-icon>
							<input class="form-input" v-model="form.tracking_number" placeholder="快递单号"
								placeholder-class="placeholder" maxlength="50" />
						</view>
					</view>
				</view>

				<view class="form-row">
					<view class="form-item half">
						<text class="form-label">批发价</text>
						<view class="input-with-icon price">
							<text class="price-prefix">¥</text>
							<input class="form-input" v-model="form.wholesale_price" type="digit" placeholder="0.00"
								placeholder-class="placeholder" />
						</view>
					</view>
					<view class="form-item half">
						<text class="form-label">商家名称</text>
						<view class="input-with-icon">
							<u-icon name="account" size="28" color="#999" class="input-icon"></u-icon>
							<input class="form-input" v-model="form.merchant_name" placeholder="商家名称"
								placeholder-class="placeholder" maxlength="50" />
						</view>
					</view>
				</view>

				<view class="form-item">
					<text class="form-label">备注</text>
					<textarea class="form-textarea" v-model="form.remark" placeholder="请输入备注（可选）"
						placeholder-class="placeholder" maxlength="255" />
					<text class="textarea-count">{{ form.remark?.length || 0 }}/255</text>
				</view>
			</view>
		</view>

		<!-- 底部占位 -->
		<view class="footer-space"></view>

		<!-- 提交按钮 -->
		<view class="submit-bar">
			<view class="submit-btn" @click="submitForm">
				<u-icon v-if="loading" name="reload" size="32" color="#fff" class="loading-icon"></u-icon>
				<text>{{ loading ? '保存中...' : (isEdit ? '保存修改' : '添加库存') }}</text>
			</view>
		</view>

		<!-- 商品选择器 -->
		<u-select v-model="showProductPicker" :list="productList" @confirm="onProductConfirm"></u-select>

		<!-- 平台选择器 -->
		<u-select v-model="showPlatformPicker" :list="platformList" @confirm="onPlatformConfirm"></u-select>
	</view>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';

const isEdit = ref(false);
const inventoryId = ref('');
const loading = ref(false);

const showProductPicker = ref(false);
const showPlatformPicker = ref(false);

const productList = ref([]);

const platformText = {
	pdd: '拼多多',
	jd: '京东',
	tb: '淘宝',
};

const platformList = [
	{ value: 'pdd', label: '拼多多' },
	{ value: 'jd', label: '京东' },
	{ value: 'tb', label: '淘宝' },
];

const form = ref({
	product_id: '',
	quantity: '',
	remaining_quantity: '',
	platform_name: '',
	platform_order_number: '',
	tracking_number: '',
	wholesale_price: '',
	merchant_name: '',
	remark: '',
});

const selectedProductName = computed(() => {
	const product = productList.value.find(item => item.value === form.value.product_id);
	return product ? product.label : '';
});

const onProductConfirm = (e) => {
	if (e && e.length > 0) {
		form.value.product_id = e[0].value;
	}
};

const onPlatformConfirm = (e) => {
	if (e && e.length > 0) {
		form.value.platform_name = e[0].value;
	}
};

const validateForm = () => {
	if (!form.value.product_id) {
		uni.showToast({ title: '请选择商品', icon: 'none' });
		return false;
	}
	if (!form.value.quantity || parseInt(form.value.quantity) <= 0) {
		uni.showToast({ title: '请输入正确的进货数量', icon: 'none' });
		return false;
	}
	if (form.value.remaining_quantity === '' || parseInt(form.value.remaining_quantity) < 0) {
		uni.showToast({ title: '请输入正确的剩余数量', icon: 'none' });
		return false;
	}
	if (parseInt(form.value.remaining_quantity) > parseInt(form.value.quantity)) {
		uni.showToast({ title: '剩余数量不能大于进货数量', icon: 'none' });
		return false;
	}
	if (!form.value.platform_name) {
		uni.showToast({ title: '请选择进货平台', icon: 'none' });
		return false;
	}
	if (!form.value.platform_order_number.trim()) {
		uni.showToast({ title: '请输入平台订单号', icon: 'none' });
		return false;
	}
	if (!form.value.tracking_number.trim()) {
		uni.showToast({ title: '请输入快递单号', icon: 'none' });
		return false;
	}
	if (!form.value.wholesale_price || parseFloat(form.value.wholesale_price) <= 0) {
		uni.showToast({ title: '请输入正确的批发价', icon: 'none' });
		return false;
	}
	if (!form.value.merchant_name.trim()) {
		uni.showToast({ title: '请输入商家名称', icon: 'none' });
		return false;
	}
	return true;
};

const submitForm = async () => {
	if (loading.value) return;
	if (!validateForm()) return;

	loading.value = true;

	const submitData = {
		product_id: parseInt(form.value.product_id),
		quantity: parseInt(form.value.quantity),
		remaining_quantity: parseInt(form.value.remaining_quantity),
		platform_name: form.value.platform_name,
		platform_order_number: form.value.platform_order_number.trim(),
		tracking_number: form.value.tracking_number.trim(),
		wholesale_price: parseFloat(form.value.wholesale_price).toFixed(2),
		merchant_name: form.value.merchant_name.trim(),
		remark: form.value.remark.trim(),
	};

	if (isEdit.value) {
		submitData.id = parseInt(inventoryId.value);
	}

	try {
		const res = await uni.request({
			url: getApi('/admin/inventory/edit'),
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

const getInventoryDetail = async (id) => {
	try {
		const res = await uni.request({
			url: getApi('/admin/inventory/getDetail?id=' + id),
			method: 'GET',
			header: {
				'Authorization': getAuth()
			}
		});
		const data = res.data;
		if (data.code === 200) {
			const detail = data.data;
			form.value = {
				product_id: detail.productId,
				quantity: detail.quantity,
				remaining_quantity: detail.remainingQuantity,
				platform_name: detail.platformName,
				platform_order_number: detail.platformOrderNumber,
				tracking_number: detail.trackingNumber,
				wholesale_price: detail.wholesalePrice,
				merchant_name: detail.merchantName,
				remark: detail.remark,
			};
		}
	} catch (error) {
		console.error('获取库存详情失败:', error);
		uni.showToast({ title: '获取库存详情失败', icon: 'none' });
	}
};

const getProductList = async () => {
	try {
		const res = await uni.request({
			url: getApi('/admin/product/getLists'),
			method: 'GET',
			header: {
				'Authorization': getAuth()
			}
		});
		const data = res.data;
		if (data.code === 200) {
			productList.value = data.data.list.map(item => ({
				value: item.id,
				label: item.name
			}));
		}
	} catch (error) {
		console.error('获取商品列表失败:', error);
	}
};

onLoad((options) => {
	getProductList();
	if (options.id) {
		isEdit.value = true;
		inventoryId.value = options.id;
		getInventoryDetail(options.id);
	}
});
</script>

<style lang="scss" scoped>
.container {
	min-height: 94vh;
	background: #f8f9fa;
	padding: 20rpx;
}

// 表单分区
.form-section {
	margin-bottom: 30rpx;

	.section-title {
		display: flex;
		align-items: center;
		gap: 16rpx;
		margin-bottom: 20rpx;
		padding: 0 10rpx;

		.title-icon {
			width: 56rpx;
			height: 56rpx;
			border-radius: 14rpx;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.title-text {
			font-size: 30rpx;
			font-weight: 600;
			color: #333;
		}
	}
}

// 表单卡片
.form-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 30rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);

	.form-row {
		display: flex;
		gap: 24rpx;

		.form-item {
			flex: 1;

			&.half {
				flex: 1;
			}
		}
	}

	.form-item {
		margin-bottom: 30rpx;

		&:last-child {
			margin-bottom: 0;
		}

		.form-label {
			display: block;
			font-size: 26rpx;
			color: #666;
			margin-bottom: 12rpx;
		}

		.input-with-icon {
			display: flex;
			align-items: center;
			background: #f8f9fa;
			border-radius: 12rpx;
			padding: 0 20rpx;
			height: 80rpx;

			.input-icon {
				margin-right: 12rpx;
			}

			.price-prefix {
				font-size: 28rpx;
				color: #e1251b;
				font-weight: 600;
				margin-right: 8rpx;
			}

			.form-input {
				flex: 1;
				height: 100%;
				font-size: 28rpx;
				color: #333;
				background: transparent;
			}
		}

		.picker-wrap {
			display: flex;
			align-items: center;
			justify-content: space-between;
			height: 80rpx;
			background: #f8f9fa;
			border-radius: 12rpx;
			padding: 0 20rpx;

			.picker-left {
				display: flex;
				align-items: center;

				.picker-icon {
					margin-right: 12rpx;
				}

				.picker-text {
					font-size: 28rpx;
					color: #333;

					&.placeholder {
						color: #bbb;
					}
				}
			}
		}

		.form-input {
			height: 80rpx;
			background: #f8f9fa;
			border-radius: 12rpx;
			padding: 0 20rpx;
			font-size: 28rpx;
			color: #333;
		}

		.form-textarea {
			width: 100%;
			height: 160rpx;
			background: #f8f9fa;
			border-radius: 12rpx;
			padding: 20rpx;
			font-size: 28rpx;
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
	}

	.form-tip-row {
		display: flex;
		align-items: center;
		gap: 8rpx;
		padding: 16rpx;
		background: #f0f0f0;
		border-radius: 10rpx;

		.tip-text {
			font-size: 24rpx;
			color: #999;
		}
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
