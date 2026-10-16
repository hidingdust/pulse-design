# PULSE

PULSE 是一只住在云服务器里的治愈系数字生命体。它使用 PHP 采集服务器心跳，用 MySQL 保存状态和历史，用 Three.js 在前端渲染一个会呼吸、会回应互动的 3D 生命体。

这个项目的目标不是商业产品，而是一个可以真实运行的 PHP 全栈作品。它适合用于面试展示，也适合挂到个人网站作为项目入口。

## 技术栈

- PHP 8.3 + PHP-FPM
- MySQL 8
- PDO 预处理与事务
- PHP CLI Worker
- JavaScript、Vite、Three.js
- Caddy 自动 HTTPS
- Docker Compose

## 核心功能

- CPU 高负载时，PULSE 变得暴躁，身体出现暖色脉冲。
- 内存占用高时，PULSE 变得迟钝，动画速度下降。
- 磁盘接近满载时，PULSE 变得焦虑，画面出现柔和碎片。
- 访客变多时，PULSE 变得兴奋，颜色更亮、粒子更多。
- 长时间无人访问时，PULSE 进入睡眠。
- 支持喂养、抚摸、降温、逗它四个互动动作。
- 所有访客共享同一只 PULSE，状态写入同一张状态表。
- 提供 24 小时情绪与指标时间轴。
- 提供体检报告和成长阶段。
- 提供受令牌保护的演示模式，方便面试现场展示全部状态。

## 目录结构

```text
pulse/
├── database/              MySQL 结构与种子数据
├── docker/                Caddy、PHP、MySQL 配置
├── public/                PHP 入口与前端构建产物
├── src/                   PHP 后端
├── worker/                PHP CLI Worker
├── frontend/              Vite + Three.js 前端
├── tests/                 纯 PHP 规则测试
└── docker-compose.yml
```

## 本地运行

前置条件：

- Docker Desktop 已安装并启动
- Node.js 20 或更高版本

第一次运行：

```powershell
cd "D:\CodexSpace\PHP Performance Lab\pulse"
Copy-Item .env.example .env
cd frontend
npm install
npm run build
cd ..
docker compose up -d --build
```

打开：

```text
http://localhost:8080
```

健康检查：

```powershell
Invoke-RestMethod http://localhost:8080/api/health
```

运行 PHP 规则测试：

```powershell
docker compose exec php php /srv/tests/run.php
```

## 本地前端开发

后端使用 Docker 运行，前端使用 Vite 开发服务器：

```powershell
docker compose up -d php mysql caddy
cd frontend
npm run dev
```

打开 `http://localhost:5173`。Vite 会把 `/api` 请求代理到 `http://localhost:8080`。

## 环境变量

复制 `.env.example` 为 `.env` 后按需修改。不要把真实生产密码提交到仓库。

| 变量 | 用途 |
|---|---|
| `SITE_ADDRESS` | Caddy 监听地址，本地默认 `:80` |
| `HTTP_PORT` | 本地默认 `8080`，生产环境改为 `80` |
| `HTTPS_PORT` | 本地默认 `8443`，生产环境改为 `443` |
| `DB_NAME` | MySQL 数据库名 |
| `DB_USER` | MySQL 应用用户 |
| `DB_PASSWORD` | MySQL 应用密码 |
| `MYSQL_ROOT_PASSWORD` | MySQL root 密码 |
| `DEMO_TOKEN` | 面试演示模式令牌 |
| `PULSE_METRIC_MODE` | `auto` 使用真实指标，`simulated` 强制模拟 |

## 演示模式

页面右上角有一个实验瓶按钮。打开后输入 `.env` 中的 `DEMO_TOKEN`，即可切换以下场景：

- CPU 高负载
- 内存高占用
- 磁盘接近满载
- 访客激增
- 长时间无人
- 恢复平静

演示模式会在数据库里标记数据来源，页面右上角会显示“演示数据”。它不会伪装成真实服务器指标。

## 腾讯云非大陆部署

推荐使用腾讯云香港或新加坡地区的轻量应用服务器或 CVM。服务器不在中国大陆地区，因此不需要 ICP 备案。

推荐配置：

| 项目 | 推荐值 |
|---|---|
| 系统 | Ubuntu 24.04 LTS |
| 规格 | 2C4G |
| 磁盘 | 40GB 以上 |
| 开放端口 | 22、80、443 |
| 不开放端口 | 3306 |

部署步骤：

1. 在腾讯云创建香港或新加坡实例。
2. 使用 SSH 密钥登录服务器。
3. 安装 Docker Engine 与 Docker Compose。
4. 上传项目或从 Git 仓库拉取代码。
5. 在服务器根据 `.env.production.example` 创建 `.env`，修改生产密码、域名和演示令牌。
6. 在本地或服务器执行 `cd frontend && npm install && npm run build`。
7. 执行 `docker compose up -d --build`。
8. 把域名 A 记录指向服务器公网 IP。
9. 设置生产环境变量：

```env
SITE_ADDRESS=pulse.example.com
HTTP_PORT=80
HTTPS_PORT=443
APP_ENV=production
APP_URL=https://pulse.example.com
```

10. 等待 Caddy 自动申请 HTTPS 证书。
11. 访问 `/api/health` 和首页完成验收。

## 数据与隐私

- 不执行任意 Shell 命令。
- 不向公网开放 MySQL 端口。
- 数据库密码和演示令牌只放在 `.env`。
- 互动使用 Session、CSRF Token 和冷却时间保护。
- 演示模式必须携带令牌。

## 面试讲解建议

可以按这个顺序演示：

1. 打开首页，让面试官看到生命体正在呼吸。
2. 点击喂养、抚摸、降温、逗它，展示即时反馈。
3. 打开两个浏览器窗口，展示所有访客共享同一只生命体。
4. 打开体检报告和成长阶段。
5. 打开演示模式，依次展示 CPU 高、内存高、磁盘满、访客多、长时间无人。
6. 说明 PHP 负责状态和事务，MySQL 负责持久化，Worker 负责心跳，Three.js 只负责渲染。

