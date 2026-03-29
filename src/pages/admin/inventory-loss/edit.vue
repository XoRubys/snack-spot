<template>
	<tab-navbar title="库损编辑" :show-back="true"></tab-navbar>
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
				<view class="form-item" v-if="form.product_id">
					<text class="form-label">选择批次</text>
					<view class="picker-wrap" @click="showBatchPicker = true">
						<view class="picker-left">
							<u-icon name="bag" size="32" color="#999" class="picker-icon"></u-icon>
							<text class="picker-text" :class="{ 'placeholder': !form.batch_id }">
								{{ selectedBatchName || '请选择库存批次' }}
							</text>
						</view>
						<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
					</view>
					<view class="batch-tip" v-if="selectedBatchInfo">
						<text class="tip-text">剩余: {{ selectedBatchInfo.remaining_quantity }} | 批发价: ¥{{
							selectedBatchInfo.wholesale_price }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 库损信息卡片 -->
		<view class="form-section">
			<view class="section-title">
				<view class="title-icon" style="background: #ff4d4f15;">
					<u-icon name="warning-fill" size="32" color="#ff4d4f"></u-icon>
				</view>
				<text class="title-text">库损信息</text>
			</view>
			<view class="form-card">
				<view class="form-item">
					<text class="form-label">损耗类型</text>
					<view class="picker-wrap" @click="showTypePicker = true">
						<view class="picker-left">
							<u-icon name="tags" size="32" color="#999" class="picker-icon"></u-icon>
							<text class="picker-text" :class="{ 'placeholder': !form.loss_type }">
								{{ lossTypeText[form.loss_type] || '请选择损耗类型' }}
							</text>
						</view>
						<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
					</view>
				</view>

				<view class="form-item">
					<text class="form-label">损耗数量</text>
					<view class="input-with-icon">
						<u-icon name="grid" size="28" color="#999" class="input-icon"></u-icon>
						<input class="form-input" v-model="form.quantity" type="number" placeholder="0"
							placeholder-class="placeholder" />
					</view>
				</view>
			</view>
		</view>

		<!-- 损耗详情卡片 -->
		<view class="form-section">
			<view class="section-title">
				<view class="title-icon" style="background: #ff950015;">
					<u-icon name="file-text-fill" size="32" color="#ff9500"></u-icon>
				</view>
				<text class="title-text">损耗详情</text>
			</view>
			<view class="form-card">
				<view class="form-item">
					<text class="form-label">损耗原因</text>
					<textarea class="form-textarea" v-model="form.reason" placeholder="请输入损耗原因"
						placeholder-class="placeholder" maxlength="255" />
					<text class="textarea-count">{{ form.reason?.length || 0 }}/255</text>
				</view>

				<view class="form-item">
					<text class="form-label">备注（可选）</text>
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
				<text>{{ loading ? '保存中...' : (isEdit ? '保存修改' : '添加库损') }}</text>
			</view>
		</view>

		<!-- 商品选择器 -->
		<u-select v-model="showProductPicker" :list="productList" @confirm="onProductConfirm"></u-select>

		<!-- 批次选择器 -->
		<u-select v-model="showBatchPicker" :list="batchList" @confirm="onBatchConfirm"></u-select>

		<!-- 损耗类型选择器 -->
		<u-select v-model="showTypePicker" :list="lossTypeList" @confirm="onTypeConfirm"></u-select>
	</view>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import { getApi, getAuth } from '@/utils/api';

const isEdit = ref(false);
const lossId = ref('');
const loading = ref(false);

const showProductPicker = ref(false);
const showBatchPicker = ref(false);
const showTypePicker = ref(false);

const productList = ref([]);
const batchList = ref([]);

const lossTypeText = {
	damage: '损坏',
	expired: '过期',
	theft: '盗窃',
	error: '盘点错误',
	other: '其他',
};

const lossTypeList = [
	{ value: 'damage', label: '损坏' },
	{ value: 'expired', label: '过期' },
	{ value: 'theft', label: '盗窃' },
	{ value: 'error', label: '盘点错误' },
	{ value: 'other', label: '其他' },
];

const form = ref({
	product_id: '',
	batch_id: '',
	loss_type: '',
	quantity: '',
	reason: '',
	remark: '',
});

const selectedBatchInfo = computed(() => {
	const batch = batchList.value.find(item => item.value === form.value.batch_id);
	return batch ? batch.extra : null;
});

const selectedBatchName = computed(() => {
	const batch = batchList.value.find(item => item.value === form.value.batch_id);
	return batch ? batch.label : '';
});

const selectedProductName = computed(() => {
	const product = productList.value.find(item => item.value === form.value.product_id);
	return product ? product.label : '';
});

const onProductConfirm = (e) => {
	if (e && e.length > 0) {
		form.value.product_id = e[0].value;
		form.value.batch_id = '';
		getBatchList(e[0].value);
	}
};

const onBatchConfirm = (e) => {
	if (e && e.length > 0) {
		form.value.batch_id = e[0].value;
	}
};

const onTypeConfirm = (e) => {
	if (e && e.length > 0) {
		form.value.loss_type = e[0].value;
	}
};

const validateForm = () => {
	if (!form.value.product_id) {
		uni.showToast({ title: '请选择商品', icon: 'none' });
		return false;
	}
	if (!form.value.batch_id) {
		uni.showToast({ title: '请选择库存批次', icon: 'none' });
		return false;
	}
	if (!form.value.loss_type) {
		uni.showToast({ title: '请选择损耗类型', icon: 'none' });
		return false;
	}
	if (!form.value.quantity || parseInt(form.value.quantity) <= 0) {
		uni.showToast({ title: '请输入正确的损耗数量', icon: 'none' });
		return false;
	}
	if (!form.value.reason.trim()) {
		uni.showToast({ title: '请输入损耗原因', icon: 'none' });
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
		batch_id: parseInt(form.value.batch_id),
		loss_type: form.value.loss_type,
		quantity: parseInt(form.value.quantity),
		reason: form.value.reason.trim(),
		remark: form.value.remark.trim(),
	};

	if (isEdit.value) {
		submitData.id = parseInt(lossId.value);
	}

	try {
		const res = await uni.request({
			url: getApi('/admin/inventory-loss/edit'),
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

const getBatchList = async (productId) => {
	if (!productId) return;
	try {
		const res = await uni.request({
			url: getApi('/admin/inventory/getLists?product_id=' + productId),
			method: 'GET',
			header: {
				'Authorization': getAuth()
			}
		});
		const data = res.data;
		if (data.code === 200) {
			batchList.value = data.data.list.map(item => ({
				value: item.id,
				label: `批次#${item.id} - 剩余${item.remainingQuantity}件`,
				extra: {
					remaining_quantity: item.remainingQuantity,
					wholesale_price: item.wholesalePrice
				}
			}));
		}
	} catch (error) {
		console.error('获取批次列表失败:', error);
	}
};

const getLossDetail = async (id) => {
	try {
		const res = await uni.request({
			url: getApi('/admin/inventory-loss/getDetail?id=' + id),
			method: 'GET',
			header: {
				'Authorization': getAuth()
			}
		});
		const data = res.data;
		if (data.code === 200) {
			const detail = data.data;
			form.value = {
				product_id: detail.product_id,
				batch_id: detail.batch_id,
				loss_type: detail.loss_type,
				quantity: detail.quantity,
				reason: detail.reason,
				remark: detail.remark,
			};
			// 获取该商品的批次列表
			await getBatchList(detail.product_id);
		}
	} catch (error) {
		console.error('获取库损详情失败:', error);
		uni.showToast({ title: '获取库损详情失败', icon: 'none' });
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
		lossId.value = options.id;
		getLossDetail(options.id);
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
				color: #ff4d4f;
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

		.batch-tip {
			margin-top: 12rpx;
			padding: 0 20rpx;

			.tip-text {
				font-size: 24rpx;
				color: #ff9500;
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
		background: linear-gradient(135deg, #ff4d4f 0%, #ff7875 100%);
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
