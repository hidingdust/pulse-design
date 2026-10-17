# PULSE

PULSE 是一只住在云服务器里的治愈系数字生命体。

它使用 PHP 采集服务器状态，用 MySQL 保存状态和历史，用 PHP CLI Worker 持续更新心跳，用 Three.js 在浏览器里渲染一个会呼吸、会回应互动的 3D 生命体。

## 在线地址

- 主站：<https://pulse.nobeladam.cn>
- 健康检查：<https://pulse.nobeladam.cn/api/health>
- 状态接口：<https://pulse.nobeladam.cn/api/state>

## 仓库地址

- Gitee：<https://gitee.com/hidingdust/pulse-design.git>
- GitHub：<https://github.com/hidingdust/pulse-design.git>

## 技术栈

- PHP 8.3
- PHP-FPM
- MySQL 8
- PDO
- PHP CLI Worker
- JavaScript
- Vite
- Three.js
- Caddy
- Docker Compose

## 目录结构

```text
pulse-design/
├── docs/                 项目设计与学习记录
├── pulse/                PULSE 应用
│   ├── database/         MySQL 结构与种子数据
│   ├── docker/           Caddy 与 PHP 配置
│   ├── frontend/         Vite + Three.js
│   ├── public/           PHP 入口与前端构建产物
│   ├── src/              PHP 后端
│   ├── worker/           PHP CLI Worker
│   ├── tests/            PHP 规则测试
│   └── docker-compose.yml
├── design-refs/          视觉参考
└── .gitignore
```

## 本地运行

前置条件：

- Docker Desktop
- Node.js 20 或更高版本

```powershell
cd "D:\CodexSpace\pulse-design\pulse"
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

## 生产部署

生产环境采用：

- EdgeOne Makers 托管静态前端
- Edge Function 转发 `/api/*`
- 独立服务器运行 PHP、MySQL、Worker
- Caddy 负责 API 域名和 HTTPS

部署与问题记录详见：

- [PULSE 项目学习记录](docs/PULSE项目学习记录.md)
- [PULSE 设计规格](docs/specs/2026-10-16-pulse-design.md)
- [PULSE README](pulse/README.md)

## 最近部署提交

```text
8169177 docs: record edge proxy gzip fix
ba825d8 fix: disable upstream compression for edge proxy
7803173 fix: return buffered edge proxy response
e6d3d7b fix: strip stale encoding headers from edge proxy
1b1b6c1 fix: force identity encoding in edge proxy
000065b docs: record EdgeOne Makers API proxy
21365ef fix: use HTTPS api origin for edge proxy
1a994c7 fix: place edge function in frontend build root
```
