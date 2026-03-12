# KowloonJudo 九龙柔道

全栈项目框架，包含小程序端、后台管理前端和后端接口三个子项目。

## 📁 项目结构

```
KowloonJudo/
├── miniprogram/     # 微信小程序端（UniApp + Vue3 + Vite）
├── admin/           # 后台管理前端（Vue3 + Vite + Element Plus）
└── api/             # 后端接口（PHP Lumen 10）
```

---

## 🔧 技术栈

| 子项目 | 技术 |
|--------|------|
| 小程序端 | UniApp · Vue3 · Vite · Pinia |
| 后台管理 | Vue3 · Vite · TypeScript · Element Plus · Pinia · Vue Router |
| 后端接口 | PHP 8.x · Lumen 10 · JWT Auth · MySQL |

---

## 🚀 快速开始

### 1. 后端接口 (api/)

```bash
cd api

# 复制环境配置
cp .env.example .env

# 编辑 .env，配置数据库和微信参数
# DB_DATABASE=kowloon_judo
# WX_APPID=your_wx_appid
# WX_SECRET=your_wx_secret

# 安装依赖
composer install

# 生成 JWT Secret（已自动生成，无需重复执行）
php artisan jwt:secret

# 执行数据库迁移
php artisan migrate

# 填充初始数据（创建默认管理员）
php artisan db:seed

# 启动开发服务器（默认 8000 端口）
php -S localhost:8000 -t public
```

**默认管理员账号：**
- 邮箱：`admin@kowloonjudo.com`
- 密码：`Admin@123456`

---

### 2. 后台管理前端 (admin/)

```bash
cd admin
npm install
npm run dev
```

访问 http://localhost:5173

---

### 3. 小程序端 (miniprogram/)

```bash
cd miniprogram
npm install

# 微信小程序开发（使用 HBuilderX 或命令行）
npm run dev:mp-weixin
```

用微信开发者工具导入 `miniprogram/dist/dev/mp-weixin` 目录。

---

## 📡 接口规范

所有接口返回统一 JSON 格式：

```json
{
  "code": 0,
  "message": "success",
  "data": {}
}
```

| code | 含义 |
|------|------|
| 0 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 / Token 过期 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 422 | 表单验证失败 |
| 500 | 服务器错误 |

### 主要接口

| 方法 | 路径 | 描述 | 认证 |
|------|------|------|------|
| POST | `/api/auth/wx-login` | 小程序微信登录 | ❌ |
| POST | `/api/admin/login` | 管理员登录 | ❌ |
| GET  | `/api/user/info` | 获取用户信息 | ✅ JWT |
| POST | `/api/user/info` | 更新用户信息 | ✅ JWT |
| GET  | `/api/admin/profile` | 获取管理员信息 | ✅ JWT |
| POST | `/api/admin/logout` | 退出登录 | ✅ JWT |
| GET  | `/api/admin/users` | 用户列表 | ✅ JWT |
| PUT  | `/api/admin/users/{id}` | 更新用户 | ✅ JWT |
| DELETE | `/api/admin/users/{id}` | 删除用户 | ✅ JWT |

---

## 🗄️ 数据库表

- `users` — 小程序用户（openid / nickname / avatar / phone / status）
- `admins` — 后台管理员（name / email / password / role / status）

---

## 📝 开发说明

- 所有接口需要认证的，请求头加 `Authorization: Bearer {token}`
- 小程序端请求封装在 `miniprogram/src/utils/request.js`
- 管理端请求封装在 `admin/src/utils/request.ts`
- 环境变量通过各子项目 `.env.development` / `.env.production` 配置
