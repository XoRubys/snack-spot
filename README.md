# 零食铺 (Snack Spot)

一个基于 uni-app + PHP 的零食铺小程序系统，支持用户下单、在线支付、库存管理等功能。

> 本项目由非计算机专业学生开发，使用 Vibe Coding 方式完成。由于开发经验和时间有限，项目可能存在一些 Bug 和不足之处。
>
> 欢迎报考安徽职业技术大学！

## 项目结构

```
snack-git/
├── api/                    # 后端 PHP API
│   ├── config/             # 配置文件
│   │   └── database.php    # 数据库配置
│   ├── core/               # 核心类
│   │   ├── Auth.php        # 认证类
│   │   ├── Database.php    # 数据库连接
│   │   ├── Mail.php        # 邮件发送
│   │   ├── Pay.php         # 支付处理
│   │   └── ShopStatus.php  # 店铺状态
│   ├── database/tables/    # 数据库表结构 SQL
│   ├── lib/                # 第三方库 (PHPMailer)
│   ├── utils/              # 工具类
│   └── v1/                 # API 接口
│       ├── admin/          # 管理员接口
│       └── user/           # 用户接口
├── script/                 # 脚本
│   └── check_timeout.py    # 订单超时检查脚本
└── src/                    # 前端 uni-app
    ├── pages/
    │   ├── admin/          # 管理端页面
    │   └── user/           # 用户端页面
    └── utils/
        └── api.js          # API 配置
```

## 功能特性

### 用户端

- 商品浏览与详情查看
- 购物车与下单
- 在线支付 (微信支付，码支付)
- 订单历史记录
- 个人信息管理
- 积分系统（待开发）

### 管理端

- 商品管理 (分类、库存)
- 订单管理
- 库存管理与报损记录
- 用户管理
- 店铺配置
- 数据统计

## 环境要求

### 前端

- Node.js 16+
- npm 或 pnpm

### 后端

- PHP 8.0+
- MySQL 5.6+
- 支持 cURL 扩展
- 支持 PDO 扩展

## 安装部署

### 1. 克隆项目

```bash
git clone https://github.com/your-username/snack-spot.git
cd snack-spot
```

### 2. 前端配置与运行

```bash
# 安装依赖
npm install

# 开发模式 (H5)
npm run dev:h5

# 构建 (H5)
npm run build:h5
```

### 3. 后端配置

#### 3.1 数据库配置

编辑 `api/config/database.php`：

```php
<?php
return [
   'host' => 'localhost',           // 数据库主机
   'database' => 'your_database',   // 数据库名称
   'username' => 'your_username',   // 数据库用户名
   'password' => 'your_password',   // 数据库密码
   'charset' => 'utf8mb4',          // 数据库字符集
   'prefix' => 'snack_',            // 数据库表前缀
];
```

#### 3.2 导入数据库

依次执行 `api/database/tables/` 目录下的 SQL 文件创建数据表：

```sql
-- 按顺序执行
source category.sql;
source config.sql;
source user.sql;
source product.sql;
source inventory.sql;
source inventory_loss.sql;
source order.sql;
source order_item.sql;
source points_log.sql;
```

#### 3.3 邮件服务配置

编辑 `api/core/Mail.php`，修改以下配置：

| 配置项                   | 说明              | 行号   |
| --------------------- | --------------- | ---- |
| `$mail->Host`         | SMTP 服务器地址      | 第20行 |
| `$mail->Username`     | SMTP 用户名 (邮箱地址) | 第22行 |
| `$mail->Password`     | SMTP 密码或授权码     | 第23行 |
| `$mail->SMTPSecure`   | 加密协议 (ssl/tls)  | 第24行 |
| `$mail->Port`         | SMTP 端口         | 第25行 |
| `$mail->setFrom()`    | 发件人邮箱和名称        | 第27行 |
| `$mail->addAddress()` | 收件人邮箱和名称        | 第28行 |

#### 3.4 支付配置

编辑 `api/core/Pay.php`，修改以下配置：

| 配置项          | 说明      | 行号   |
| ------------ | ------- | ---- |
| `PID`        | 支付商户ID  | 第9行  |
| `KEY`        | 支付商户密钥  | 第10行 |
| `API_URL`    | 支付API地址 | 第11行 |
| `notify_url` | 异步通知地址  | 第23行 |
| `return_url` | 同步返回地址  | 第24行 |

#### 3.5 前端 API 地址配置

编辑 `src/utils/api.js`：

```javascript
export const API_BASE_URL = 'https://your-domain.com/api/v1'
```

#### 3.6 定时任务配置

编辑 `script/check_timeout.py`：

```python
URL = "https://your-domain.com/api/v1/user/order/checkTimeout"
INTERVAL = 60  # 检查间隔 (秒)
```

启动定时任务：

```bash
python script/check_timeout.py
```

或在 Windows 上运行：

```bash
script/start_check_timeout.bat
```

### 4. 小程序配置

编辑 `src/manifest.json`：

```json
{
    "name": "零食铺",
    "appid": "__UNI__XXXXXXX",
    "description": "零食铺小程序",
    "mp-weixin": {
        "appid": "你的微信小程序AppID"
    }
}
```

## 配置项汇总

| 配置文件                      | 配置项     | 说明           |
| ------------------------- | ------- | ------------ |
| `api/config/database.php` | 数据库连接信息 | MySQL 数据库配置  |
| `api/core/Mail.php`       | SMTP 配置 | 邮件通知服务       |
| `api/core/Pay.php`        | 支付配置    | 商户ID、密钥、回调地址 |
| `api/core/ShopStatus.php` | 营业时间    | 开始/结束时间、打烊提示 |
| `src/utils/api.js`        | API 地址  | 后端接口基础地址     |
| `src/manifest.json`       | 小程序信息   | AppID、名称等    |
| `script/check_timeout.py` | 定时任务    | 超时检查地址和间隔    |

## 默认配置值

| 配置项       | 默认值           | 文件位置                                      |
| --------- | ------------- | ----------------------------------------- |
| 营业开始时间    | 07:00:00      | `api/core/ShopStatus.php` 第11行            |
| 营业结束时间    | 23:30:00      | `api/core/ShopStatus.php` 第12行            |
| 打烊提示语     | 店铺已打烊         | `api/core/ShopStatus.php` 第13行            |
| Token 有效期 | 90 天          | `api/core/Auth.php` 第54行                  |
| 订单超时时间    | 4 分钟          | `api/v1/user/order/checkTimeout.php` 第30行 |
| 时区        | Asia/Shanghai | `api/v1/common.php` 第5行                   |

## 部署检查清单

上线前请确保已修改以下敏感信息：

- [ ] 数据库连接信息 (`api/config/database.php`)
- [ ] SMTP 邮箱配置 (`api/core/Mail.php`)
- [ ] 支付商户配置 (`api/core/Pay.php`)
- [ ] API 域名地址 (`src/utils/api.js`)
- [ ] 支付回调地址 (`api/core/Pay.php`)
- [ ] 定时任务地址 (`script/check_timeout.py`)
- [ ] 微信小程序 AppID (`src/manifest.json`)

## 技术栈

- **前端**: uni-app + Vue 3 + uview-pro + naive ui（登录与注册）
- **后端**: PHP + MySQL
- **支付**: 码支付 (支持微信支付)
- **邮件**: PHPMailer

## License

MIT
