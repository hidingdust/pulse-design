# PULSE 设计规格

日期：2026-10-16

状态：待用户审阅

## 1. 项目摘要

PULSE 是一只住在云服务器里的治愈系数字生命体。访客打开网页后，会看到一只会呼吸、会漂浮、会回应互动的生命体。它由 PHP 后端驱动，状态保存在 MySQL 中，前端使用 JavaScript 与 Three.js 渲染 3D 场景。

项目目标不是商业产品，而是一个能给面试官现场演示的 PHP 全栈作品。它必须真实包含后端、数据库、后台 Worker、实时状态、3D 前端和云服务器部署。

项目名称：PULSE

一句话描述：用 PHP 养一只会感知服务器状态的 3D 数字生命体。

## 2. 目标

1. 用 PHP 完成真实后端，包括 API、Session、规则校验、事务、后台 Worker。
2. 用 MySQL 保存生命体状态、指标快照、互动记录、事件和成长信息。
3. 用 JavaScript 与 Three.js 实现具有视觉冲击力的 3D 治愈系生命体。
4. 让服务器真实指标影响生命体状态，包括 CPU、内存、磁盘、在线人数和空闲时间。
5. 让所有访客共享同一只生命体，后访问的人能看到前人的互动影响。
6. 提供 24 小时情绪时间轴、体检报告和成长阶段。
7. 提供面试演示模式，让面试官无需等待真实异常即可看到全部状态。
8. 使用 Docker Compose 部署到腾讯云非大陆地区服务器，不依赖 ICP 备案。
9. 在没有 PHP 本地环境的电脑上使用 Docker 完成开发和运行。
10. 让项目可以在一天内完成 MVP，并在第二天用于面试展示。

## 3. 非目标

1. 不做真实商业宠物系统。
2. 不做 AI 对话、语音交互和复杂人格模型。
3. 不做多宠物、多房间、宠物战斗和好友系统。
4. 不做真实支付、商城、皮肤交易和会员体系。
5. 不做复杂账号体系，MVP 只使用匿名 Session。
6. 不做真实服务器管理操作，不执行任意 Shell 命令。
7. 不做生产级高可用、分布式部署和大规模并发优化。
8. 不在 MVP 阶段引入 Laravel 或 ThinkPHP。

## 4. 目标用户与使用场景

主要用户是访问简历网站的面试官和技术人员。其次是通过个人网站进入的普通访客。

典型场景：

1. 面试官打开个人网站，点击 PULSE 项目链接。
2. 页面加载 3D 生命体，显示当前心情、能量和信任。
3. 面试官点击“喂养”“抚摸”“降温”或“逗它”。
4. 生命体立即产生动画和状态变化。
5. 面试官查看底部时间轴、体检报告和成长阶段。
6. 开发者打开演示模式，快速展示 CPU 高、内存高、磁盘满、访客多和长期无人状态。

## 5. 核心体验

页面只做一件事：让访客感觉这里真的住着一只小生命。

核心循环：

1. 访客进入页面，看到 PULSE 正在呼吸。
2. 页面展示三个核心状态：心情、能量、信任。
3. 访客执行一个互动动作。
4. PHP 校验冷却时间与输入，使用事务更新 MySQL。
5. PHP Worker 更新生命体状态并写入事件。
6. 前端在 1 秒内获取新状态，播放对应动画。
7. 其他访客也能看到同一只生命体的变化。
8. 所有指标和事件沉淀到时间轴、体检报告和成长阶段中。

互动动作：

| 动作 | 作用 | 即时反馈 |
|---|---|---|
| 喂养 | 提升能量 | 食物粒子靠近，生命体满足地弹跳 |
| 抚摸 | 提升信任 | 爱心粒子，生命体靠近鼠标方向 |
| 降温 | 降低暴躁状态，恢复平静 | 柔和蓝色波纹，呼吸变慢 |
| 逗它 | 提升心情，消耗少量能量 | 眼睛变化，短促漂浮和气泡 |

自动状态：

| 状态 | 条件 | 表现 |
|---|---|---|
| 平静 | 默认状态 | 正常呼吸与漂浮 |
| 暴躁 | CPU 负载高 | 暖珊瑚色脉冲，动作加快 |
| 迟钝 | 内存占用高 | 动画变慢，眼睛下垂 |
| 焦虑 | 磁盘空间低 | 柔和碎片漂浮，身体轻微抖动 |
| 兴奋 | 在线访客多 | 颜色更亮，粒子增多 |
| 睡眠 | 长时间无人访问 | 呼吸变慢，漂浮降低，气泡缓慢上升 |

## 6. 视觉方向

风格代号：A / Healing Cute

方向：治愈、柔软、安静、安全、亲切。不是幼稚的卡通宠物，而是高级的 3D 生物装置。

颜色：

| 名称 | 色值 | 用途 |
|---|---|---|
| 奶油白 | `#FFF8F2` | 主背景 |
| 薄荷青 | `#7DD3C7` | 生命体主色 |
| 天空蓝 | `#BFE9FF` | 环境光与水流 |
| 腮红粉 | `#FF9FB2` | 心情、爱心、可爱反馈 |
| 柔光黄 | `#FFE7A3` | 能量、睡眠、温暖光 |
| 深灰蓝 | `#31565C` | 正文与数据文字 |

字体：

| 角色 | 字体 | 用途 |
|---|---|---|
| 展示 | Baloo 2 | 标题、状态名、成长阶段 |
| 正文 | Nunito Sans | 按钮、说明、体检报告 |
| 数据 | IBM Plex Mono | 指标、时间轴、事件流 |

布局：

1. 全屏 3D 生态舱作为主视觉。
2. 左侧显示心情、能量、信任和当前状态。
3. 右侧显示四个互动动作和在线访客数。
4. 底部显示最近事件与 24 小时情绪时间轴。
5. 体检报告和成长阶段使用同页抽屉，不跳转到独立后台。
6. 移动端改为上下布局，3D 场景仍保留在首屏。

标志元素：

生命体的心跳与服务器心跳同步。它的呼吸频率、光晕、颜色和粒子强度都来自真实状态。

## 7. 3D 前端方案

技术栈：

1. Vite：构建与开发服务器。
2. JavaScript ES Modules：前端逻辑。
3. Three.js：3D 场景、生命体、粒子、光效。
4. HTML 与 CSS：HUD、按钮、报告、时间轴。
5. 不使用 React，减少构建层和学习成本。
6. 不依赖外部 3D 模型，生命体由程序化几何生成。

生命体构成：

1. 主体使用 IcosahedronGeometry 或 SphereGeometry。
2. 顶点使用自定义 Shader 做柔和呼吸和形变。
3. 材质使用半透明、柔光、低粗糙度效果。
4. 环境使用奶油白与薄荷色柔光。
5. 粒子包括爱心、气泡、柔光碎片。
6. 地台使用柔和阴影，不使用黑暗机房风格。
7. 可选 Bloom 后期效果，移动端默认关闭。

性能策略：

1. 桌面端目标 60 FPS，移动端目标 30 FPS。
2. 移动端降低像素比、粒子数量和后期效果。
3. WebGL 不可用时降级为 CSS 与 Canvas 2.5D 场景。
4. 页面遵循 `prefers-reduced-motion`，减少动画不影响功能。
5. HUD 使用真实 HTML 元素，保证键盘操作与可访问性。

## 8. 技术架构

总体架构：

```text
Browser
  |
  | HTTP / JSON
  v
Caddy
  |----------------------------+
  | static frontend            | PHP-FPM
  v                            v
frontend/dist              public/index.php
                               |
                               | PDO
                               v
                            MySQL 8
                               ^
                               |
                         PHP CLI Worker
```

运行组件：

| 组件 | 技术 | 职责 |
|---|---|---|
| Web Server | Caddy | HTTPS、静态文件、PHP-FPM 转发 |
| Backend | PHP 8.3 + PHP-FPM | API、Session、规则、事务、限流 |
| Worker | PHP CLI | 采集指标、更新状态、写入事件 |
| Database | MySQL 8 | 状态、指标、互动、事件、访客 |
| Frontend | Vite + Three.js | 3D 渲染、HUD、时间轴、报告 |

选择 Caddy 的原因：

1. 自动申请和续期 HTTPS 证书。
2. 配置比 Nginx 与 Certbot 组合更少。
3. 目标是一天部署上线，减少证书和反向代理排错时间。
4. PHP 代码与 PHP-FPM 架构不受影响。
5. Nginx 版本可以在后续替换，不影响业务逻辑。

## 9. 组件职责

Frontend：

1. 渲染 3D 生命体。
2. 每秒获取最新状态。
3. 提交互动动作。
4. 渲染时间轴、体检报告和成长阶段。
5. 不保存权威业务状态。

PHP API：

1. 返回当前状态。
2. 校验互动动作和冷却时间。
3. 使用事务更新生命体状态。
4. 写入互动记录与事件。
5. 返回统一 JSON 结构。

PHP Worker：

1. 每 5 秒采集一次服务器指标。
2. 根据指标计算生命体状态。
3. 写入指标快照、状态变更事件。
4. 处理长时间无访问后的睡眠状态。
5. 保持幂等，重复运行不会造成状态错乱。

MySQL：

1. 保存权威状态。
2. 保存历史指标。
3. 保存互动和事件。
4. 为时间轴与体检报告提供聚合数据。

## 10. 数据流

状态读取：

1. 浏览器请求 `GET /api/state`。
2. PHP 从 `pulse_state` 读取当前状态。
3. PHP 读取最近指标和事件。
4. 返回 JSON。
5. Three.js 根据状态更新生命体颜色、速度、粒子和形态。

互动写入：

1. 浏览器请求 `POST /api/interactions`。
2. PHP 校验 Session、CSRF、动作和冷却时间。
3. PHP 开始事务。
4. PHP 使用 `SELECT ... FOR UPDATE` 锁定 `pulse_state`。
5. PHP 更新心情、能量、信任。
6. PHP 写入 `interactions` 和 `events`。
7. PHP 提交事务并返回新状态。
8. 前端播放对应动画。

指标更新：

1. Worker 读取服务器指标。
2. Worker 写入 `metric_samples`。
3. Worker 计算状态是否变化。
4. 状态变化时写入 `events` 并更新 `pulse_state`。
5. 前端下一次轮询看到变化。

## 11. 数据库设计

数据库名称：`pulse`

字符集：`utf8mb4`

表：`pulse_state`

| 字段 | 类型 | 说明 |
|---|---|---|
| id | TINYINT PRIMARY KEY | 固定为 1 |
| mood | TINYINT UNSIGNED | 心情，0 到 100 |
| energy | TINYINT UNSIGNED | 能量，0 到 100 |
| trust | TINYINT UNSIGNED | 信任，0 到 100 |
| status | VARCHAR(24) | calm、irritated、sluggish、anxious、excited、sleepy |
| stage | VARCHAR(24) | seed、sprout、bloom、guardian |
| last_interaction_at | DATETIME | 最后一次互动时间 |
| last_visit_at | DATETIME | 最后一次访问时间 |
| updated_at | DATETIME | 最后更新时间 |

表：`metric_samples`

| 字段 | 类型 | 说明 |
|---|---|---|
| id | BIGINT PRIMARY KEY AUTO_INCREMENT | 主键 |
| cpu_load | DECIMAL(6,2) | 负载值 |
| memory_used_pct | DECIMAL(5,2) | 内存占用百分比 |
| disk_used_pct | DECIMAL(5,2) | 磁盘占用百分比 |
| online_count | INT UNSIGNED | 当前在线访客 |
| mood | TINYINT UNSIGNED | 采样时心情 |
| energy | TINYINT UNSIGNED | 采样时能量 |
| trust | TINYINT UNSIGNED | 采样时信任 |
| source | VARCHAR(12) | real 或 demo |
| created_at | DATETIME | 采样时间 |

索引：`INDEX idx_metric_created_at (created_at)`

表：`interactions`

| 字段 | 类型 | 说明 |
|---|---|---|
| id | BIGINT PRIMARY KEY AUTO_INCREMENT | 主键 |
| visitor_id | BIGINT UNSIGNED | 访客 ID |
| action | VARCHAR(24) | feed、pet、cool、play |
| before_state | JSON | 互动前状态 |
| after_state | JSON | 互动后状态 |
| created_at | DATETIME | 互动时间 |

索引：`INDEX idx_interaction_visitor_time (visitor_id, created_at)`

表：`events`

| 字段 | 类型 | 说明 |
|---|---|---|
| id | BIGINT PRIMARY KEY AUTO_INCREMENT | 主键 |
| type | VARCHAR(32) | status、interaction、metric、growth |
| message | VARCHAR(255) | 展示文案 |
| severity | VARCHAR(12) | info、warm、warning |
| metadata | JSON | 结构化数据 |
| created_at | DATETIME | 事件时间 |

索引：`INDEX idx_event_created_at (created_at)`

表：`visitors`

| 字段 | 类型 | 说明 |
|---|---|---|
| id | BIGINT PRIMARY KEY AUTO_INCREMENT | 主键 |
| session_key | CHAR(64) UNIQUE | 匿名 Session 标识 |
| first_seen_at | DATETIME | 首次访问 |
| last_seen_at | DATETIME | 最近访问 |
| interaction_count | INT UNSIGNED | 互动次数 |

成长阶段：

| 阶段 | 条件 | 展示文案 |
|---|---|---|
| seed | 总互动少于 50 | 刚刚醒来 |
| sprout | 总互动 50 到 199 | 开始熟悉这里 |
| bloom | 总互动 200 到 499 | 状态稳定且亲人 |
| guardian | 总互动不少于 500 | 开始守护这台服务器 |

## 12. API 设计

统一响应结构：

成功：

```json
{
  "ok": true,
  "data": {}
}
```

失败：

```json
{
  "ok": false,
  "error": {
    "code": "RATE_LIMITED",
    "message": "它刚刚已经吃过啦，稍后再来。"
  }
}
```

接口列表：

| 方法 | 路径 | 用途 |
|---|---|---|
| GET | `/api/state` | 当前状态、最近指标、最近事件 |
| POST | `/api/interactions` | 执行互动动作 |
| GET | `/api/timeline?hours=24` | 24 小时情绪与指标时间轴 |
| GET | `/api/report` | 体检报告与成长阶段 |
| GET | `/api/events?since_id=0` | 获取新事件 |
| GET | `/api/health` | 服务健康检查 |
| GET | `/api/demo/scenarios` | 可用演示场景 |
| POST | `/api/demo/scenario` | 启用演示场景，需要演示令牌 |

互动请求：

```json
{
  "action": "feed",
  "csrf_token": "..."
}
```

互动响应：

```json
{
  "ok": true,
  "data": {
    "state": {
      "mood": 86,
      "energy": 74,
      "trust": 61,
      "status": "calm",
      "stage": "sprout"
    },
    "animation": "feed",
    "cooldown_seconds": 5
  }
}
```

## 13. 8 个技术点落地

| 序号 | 需求 | 后端 | 数据库 | 前端 |
|---|---|---|---|---|
| 1 | CPU 高时变暴躁 | Worker 判断负载阈值，状态改为 irritated | 写入 metric_samples 与 events | 暖珊瑚色脉冲，呼吸和漂浮加快 |
| 2 | 内存高时变迟钝 | Worker 判断内存阈值，状态改为 sluggish | 记录内存快照和持续时间 | 动画速度下降，眼睛下垂 |
| 3 | 磁盘快满时焦虑 | Worker 判断磁盘剩余空间，状态改为 anxious | 写入磁盘告警事件 | 柔和纸片碎片漂浮，身体轻微抖动 |
| 4 | 访客多时兴奋 | 统计在线访客，状态改为 excited | 记录在线人数曲线 | 颜色更亮，粒子增多 |
| 5 | 长时间无人时睡着 | 判断最后访问时间，状态改为 sleepy | 记录最后访问和睡眠事件 | 呼吸变慢，漂浮降低 |
| 6 | 四个互动即时反馈 | PHP 校验冷却和事务更新 | 写入 interactions 与 events | 播放喂食、抚摸、降温、逗它动画 |
| 7 | 所有人共享同一只 | 所有请求读写同一行 pulse_state | 单行状态表加事务锁 | 每秒轮询同一状态 |
| 8 | 时间轴、体检报告、成长阶段 | PHP 聚合数据并计算阶段 | 读取 metric_samples、events、interactions | 时间轴、报告抽屉、成长标签 |

## 14. 演示模式

演示模式用于面试现场，不用于替代真实数据。

演示场景：

| 场景 | 模拟值 | 展示重点 |
|---|---|---|
| cpu-high | CPU 负载高于阈值 | 暴躁状态 |
| memory-high | 内存占用高于阈值 | 迟钝状态 |
| disk-full | 磁盘剩余低于阈值 | 焦虑状态 |
| crowded | 在线人数高于阈值 | 兴奋状态 |
| lonely | 最后访问时间超过阈值 | 睡眠状态 |

规则：

1. 演示模式必须使用令牌保护，不能公开任意切换。
2. 数据库中的 `source` 字段记录 real 或 demo。
3. 页面右上角显示当前数据来源。
4. 演示模式持续时间默认 10 分钟，到期后自动恢复真实数据。
5. 面试演示时通过受保护的演示面板触发，不使用可被公开篡改的 URL 参数直接修改状态。
6. 演示面板通过 `POST /api/demo/scenario` 提交场景名称和演示令牌。

## 15. 腾讯云非大陆部署

部署目标：腾讯云香港或新加坡地区轻量应用服务器或 CVM。

不备案原因：服务器不在中国大陆地区，域名可以直接解析到境外实例。

推荐配置：

| 项目 | 推荐值 | 说明 |
|---|---|---|
| 地域 | 香港优先，新加坡备选 | 香港对大陆访问延迟更低 |
| 系统 | Ubuntu 24.04 LTS | Docker 与 Caddy 支持稳定 |
| 规格 | 2C4G | 给 MySQL、PHP、Caddy 留余量 |
| 磁盘 | 40GB 以上 | 镜像、数据库、日志 |
| 带宽 | 按需选择 | 3D 静态资源加载需要一定带宽 |
| 域名 | 复用个人域名子域或新购国际域名 | 例如 `pulse` 子域名 |
| DNS | A 记录指向服务器公网 IP | TTL 使用默认值 |

安全组：

| 端口 | 规则 |
|---|---|
| 22 | 只允许个人固定 IP 访问 |
| 80 | 对公网开放，用于 ACME 验证和跳转 |
| 443 | 对公网开放 |
| 3306 | 不开放 |

部署流程：

1. 在腾讯云创建非大陆地区实例。
2. 使用 SSH 密钥登录。
3. 安装 Docker Engine 与 Docker Compose。
4. 上传项目或从 Git 仓库拉取代码。
5. 创建 `.env`，写入数据库密码、演示令牌等配置。
6. 执行 `docker compose up -d --build`。
7. 配置 DNS A 记录。
8. Caddy 自动申请 HTTPS 证书。
9. 访问 `/api/health` 验证服务。
10. 打开首页验证 3D 场景、互动、时间轴和演示模式。

镜像拉取策略：

1. 优先使用腾讯云容器镜像服务或公共镜像加速。
2. 如果服务器拉取 Docker Hub 较慢，在本地构建后导出镜像并上传。
3. 镜像版本固定，不使用 `latest`。

## 16. 安全与隐私

1. 不执行任意 Shell 命令。
2. 只读取白名单指标文件和 PHP 扩展提供的安全数据。
3. 所有 SQL 使用 PDO 预处理语句。
4. 互动接口使用 CSRF Token。
5. Session Cookie 设置 `HttpOnly`、`SameSite=Lax`，生产环境启用 `Secure`。
6. 每个访客互动有冷却时间。
7. 演示模式需要令牌。
8. 不在仓库中保存数据库密码、Token 和证书私钥。
9. 不打印完整 Session、Cookie 或敏感配置。
10. 不向公网暴露 MySQL 端口。

## 17. 错误处理

前端：

1. API 失败时显示温和文案，例如“它现在有点困惑，稍后再试”。
2. 网络断开时保留最后一次成功的生命体状态。
3. WebGL 失败时切换到 2.5D 降级场景。
4. 冷却时间未结束时禁用按钮并显示剩余秒数。

后端：

1. 统一返回 JSON 错误结构。
2. 输入错误返回 400。
3. 未登录或 Session 失效返回 401。
4. CSRF 失败返回 419。
5. 冷却中返回 429。
6. 数据库错误返回 500，并写入服务端日志。
7. Worker 错误写入日志，不中断主站。

## 18. 测试与验收

后端测试：

1. 状态机单元测试。
2. 互动冷却测试。
3. 事务并发测试，防止重复扣减或状态错乱。
4. 演示模式令牌测试。
5. API 返回结构测试。

数据库测试：

1. schema.sql 可在空库执行。
2. 种子数据可初始化单行 pulse_state。
3. 时间轴查询在 24 小时数据下可正常返回。

前端测试：

1. 桌面端与移动端截图。
2. 3D Canvas 非空白检查。
3. 互动按钮可点击并触发状态变化。
4. 冷却时间显示正确。
5. `prefers-reduced-motion` 下页面仍可使用。

部署验收：

1. 公网 HTTPS 可访问。
2. `/api/health` 返回 200。
3. MySQL 端口未暴露。
4. 真实指标持续写入。
5. 所有 8 个技术点可通过演示模式展示。
6. 两名访客同时操作时能看到同一只生命体变化。

## 19. 一天实施计划

第 0 小时到第 1 小时：环境与骨架。

1. 启动 Docker Desktop，确认 WSL2 后端可用。
2. 创建项目目录、Docker Compose、Caddy、PHP-FPM、MySQL。
3. 验证首页和 `/api/health`。

第 1 小时到第 3 小时：PHP 后端与数据库。

1. 创建 schema.sql。
2. 实现 PDO 连接。
3. 实现 `/api/state` 与 `/api/interactions`。
4. 实现 Session、CSRF 和冷却时间。
5. 完成事务与并发锁。

第 3 小时到第 4 小时：Worker 与指标。

1. 实现 PHP CLI Worker。
2. 读取 CPU、内存、磁盘、在线人数。
3. 实现状态机和事件写入。
4. 实现演示模式。

第 4 小时到第 6 小时：3D 前端。

1. 创建 Vite 与 Three.js 项目。
2. 渲染治愈系软体生命。
3. 实现 HUD、互动按钮和状态动画。
4. 实现移动端降级与 reduced motion。

第 6 小时到第 7 小时：时间轴、报告与成长。

1. 实现 `/api/timeline`。
2. 实现 `/api/report`。
3. 实现底部时间轴和体检报告抽屉。

第 7 小时到第 9 小时：部署。

1. 创建腾讯云非大陆实例。
2. 配置安全组和 DNS。
3. 上传代码，启动 Docker Compose。
4. 验证 HTTPS、健康检查和互动。

第 9 小时到第 10 小时：验收与材料。

1. 验证 8 个技术点。
2. 截取桌面端和移动端截图。
3. 编写 README 和简历描述。
4. 准备 3 分钟演示流程。

## 20. 目录结构

```text
pulse/
├── docker/
│   ├── caddy/
│   │   └── Caddyfile
│   └── php/
│       ├── Dockerfile
│       └── php.ini
├── database/
│   ├── schema.sql
│   └── seed.sql
├── public/
│   ├── index.php
│   └── assets/
├── src/
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   ├── Support/
│   └── Config/
├── worker/
│   └── pulse-worker.php
├── frontend/
│   ├── index.html
│   ├── src/
│   │   ├── main.js
│   │   ├── three/
│   │   ├── components/
│   │   └── styles/
│   ├── package.json
│   └── vite.config.js
├── docs/
│   └── specs/
│       └── 2026-10-16-pulse-design.md
├── docker-compose.yml
├── .env.example
└── README.md
```

## 21. 简历描述

项目名称：PULSE 云服务器数字生命体

技术栈：PHP 8.3、PHP-FPM、MySQL 8、PDO、PHP CLI Worker、JavaScript、Three.js、Vite、Caddy、Docker Compose、腾讯云。

简历描述：

> 独立设计并部署 PULSE 云服务器数字生命体。PHP 后端采集 CPU、内存、磁盘和在线访客指标，通过事务与后台 Worker 驱动生命体状态机；MySQL 持久化状态、指标、互动和事件；前端使用 Three.js 实现 3D 治愈系生命体，支持多人共享互动、24 小时情绪时间轴、体检报告、成长阶段与面试演示模式。项目通过 Docker Compose 部署在腾讯云非大陆地区服务器，Caddy 自动配置 HTTPS。

## 22. 风险与回滚

主要风险：

| 风险 | 影响 | 应对 |
|---|---|---|
| Docker Desktop 或 WSL2 不可用 | 无法本地运行 | 第一小时优先验证，必要时改用云服务器开发 |
| Docker Hub 拉取慢 | 构建时间增加 | 使用镜像加速或本地导出镜像 |
| 3D 移动端性能不足 | 页面卡顿 | 降低粒子、像素比和后期效果 |
| 真实指标在容器内受限 | 状态不准确 | 标注 real 或 demo，必要指标使用模拟值 |
| 没有 PHP 经验 | 无法解释代码 | 提供 PHP 代码地图和面试讲解稿 |
| 域名或 DNS 未准备好 | HTTPS 延迟 | 先使用公网 IP 和 HTTP 验证，再补域名 |
| 一天时间不足 | 功能未完成 | 保核心互动，砍排行榜、皮肤和复杂成长 |

回滚方式：

1. 保留上一版 Docker Compose 和镜像标签。
2. 发布前执行 MySQL 逻辑备份。
3. 部署失败时执行 `docker compose down`，恢复上一版镜像。
4. 数据库结构变更采用新增字段，不直接删除旧字段。
5. Caddy 证书失败不影响已有 HTTP 服务排查。

## 23. 已确定决策

1. 项目名称为 PULSE。
2. 视觉风格为 A / Healing Cute。
3. 前端使用 JavaScript、Vite 和 Three.js。
4. 后端使用原生 PHP、PDO 和 PHP CLI Worker。
5. 数据库使用 MySQL 8。
6. 本地使用 Docker，不安装系统级 PHP。
7. 部署在腾讯云非大陆地区，不要求 ICP 备案。
8. MVP 使用 1 秒轮询，不把 WebSocket 作为一天内必做项。
9. 面试展示必须包含 8 个技术点。
10. 所有演示数据必须标记来源，不伪装成真实数据。

