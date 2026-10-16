# PULSE 项目学习记录

## 维护约定

这份文档用于记录 PULSE 项目从选题到部署准备过程中，值得复盘的问题、经验和 PHP 学习笔记。

后续维护规则：

1. 每次出现值得记录的 Bug、报错、设计取舍、验证结果或 PHP 知识点时，追加到本文档。
2. 不写入任何真实密码、Token、私钥、数据库连接串和敏感配置。
3. 记录优先写清三件事：发生了什么、为什么发生、怎么解决。
4. 面向初学者解释，不只写命令，还要解释命令背后的概念。

## 项目一句话介绍

PULSE 是一只住在云服务器里的治愈系数字生命体。PHP 后端采集服务器状态并维护生命体状态，MySQL 保存历史，PHP CLI Worker 持续更新心跳，前端使用 Three.js 渲染 3D 生命体。

项目仓库：

```text
https://gitee.com/hidingdust/pulse-design.git
```

首次推送提交：

```text
452a682 feat: implement PULSE PHP digital life form
```

分支：

```text
master
```

## 一、选题是怎么定下来的

### 1. 最初考虑过 PHP 性能实验室

最初目录里有一组 PHP 性能实验文档，内容包括：

- JIT
- OpCache
- PHP-FPM 进程数
- PHP 与 Node 对比
- 常驻内存
- 数据库 N+1 与索引

这个方向技术含量不低，但不适合作为当前求职展示的小作品。

原因：

1. 六个实验全部做完工程量很大。
2. 压测对服务器和网络环境要求高。
3. 数据容易受机器环境影响，复现成本高。
4. 对没有 PHP 基础的人，学习曲线会同时压在性能测试和 PHP 语法上。

结论：放弃完整性能实验室，把精力集中到一个能上线、能演示、能讲清楚的小作品。

### 2. 为什么最终选择 PULSE

PULSE 同时满足下面几个目标：

- 不是纯前端项目。
- 有真实 PHP 后端。
- 有 MySQL 数据库。
- 有后台 Worker。
- 有实时状态。
- 有 3D 视觉冲击。
- 可以部署到云服务器。
- 一天左右能完成 MVP。

更重要的是，它可以把“服务器指标”变成“生命体情绪”，演示时非常直观。

### 3. 最终视觉方向

视觉方向选择 A 风格：治愈可爱。

关键词：

- 奶油白
- 薄荷青
- 天空蓝
- 腮红粉
- 柔光黄
- 圆润、柔软、安静

最终没有选怪异实验室和赛博深海，因为它们在视觉上更有冲击，但更容易偏离“治愈系数字生命体”的定位。

## 二、技术栈总览

| 层 | 技术 | 作用 |
|---|---|---|
| Web Server | Caddy | HTTPS、静态文件、转发 PHP 请求 |
| Backend | PHP 8.3 + PHP-FPM | API、Session、规则、事务 |
| Database | MySQL 8 | 状态、指标、互动、事件 |
| Worker | PHP CLI | 定时采集指标、更新生命体状态 |
| Frontend | JavaScript + Vite | 页面逻辑、数据请求、HUD |
| 3D | Three.js | 生命体、粒子、光效、动画 |
| 部署 | Docker Compose | 本地和服务器统一环境 |

## 三、PHP 基础概念

### 1. PHP 是什么

PHP 是一种主要在服务器端执行的脚本语言。

浏览器请求页面时，PHP 在服务器上运行，查询数据库，生成 JSON 或 HTML，然后返回给浏览器。

浏览器看不到 PHP 源代码，只能看到 PHP 的输出结果。

### 2. PHP-FPM 是什么

PHP-FPM 是 PHP 的 FastCGI 进程管理器。

它负责管理一组 PHP 进程，接收 Caddy 转发过来的请求，然后执行 PHP 文件。

可以粗略理解为：

```text
浏览器 -> Caddy -> PHP-FPM -> PHP 代码 -> MySQL
```

### 3. 入口文件

项目中的入口文件是：

```text
pulse/public/index.php
```

所有 `/api/*` 请求都会进入这个文件。

它负责：

1. 加载自动加载器。
2. 启动 Session。
3. 解析请求路径。
4. 创建 Repository 和 Service。
5. 把请求交给 ApiController。

### 4. 自动加载

项目没有安装 Composer 依赖，而是写了一个简单的 PSR-4 风格自动加载器：

```php
spl_autoload_register(static function (string $class): void {
    $prefix = 'Pulse\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
```

作用：

- 代码里写 `new Pulse\Service\PulseService()` 时，PHP 自动加载对应文件。
- 不需要手动写几十个 `require`。

### 5. PDO

PDO 是 PHP 访问数据库的统一接口。

项目使用 PDO 连接 MySQL：

```php
$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

关键点：

1. `ERRMODE_EXCEPTION` 让数据库错误抛出异常，避免静默失败。
2. `FETCH_ASSOC` 让查询结果以关联数组返回。
3. `EMULATE_PREPARES = false` 使用真实预处理，减少 SQL 注入风险。

### 6. 预处理语句

不要拼接 SQL。

错误做法：

```php
$sql = "SELECT * FROM users WHERE name = '" . $name . "'";
```

正确做法：

```php
$statement = $pdo->prepare('SELECT * FROM users WHERE name = :name');
$statement->execute([':name' => $name]);
```

预处理语句的作用：

- 参数和 SQL 结构分离。
- 减少 SQL 注入。
- 数据库可以复用执行计划。

### 7. Session

Session 用来识别同一个访客。

项目使用 Session 保存：

- 访客标识 `visitor_key`
- CSRF Token

用户第一次访问时，PHP 生成随机 Session，后续请求浏览器自动带上 Cookie。

### 8. CSRF Token

CSRF 是跨站请求伪造。

简单说，别人可能诱导你点击一个链接，让浏览器悄悄提交请求。

项目在写操作前校验 CSRF Token：

```php
$provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!hash_equals($this->csrfToken(), $provided)) {
    throw new ApiException(419, 'CSRF_INVALID', '页面令牌已失效，请刷新后再试。');
}
```

`hash_equals` 用来进行安全字符串比较。

### 9. 事务

事务用于保证多条数据库操作要么全部成功，要么全部失败。

互动流程包括：

1. 锁定访客。
2. 检查冷却时间。
3. 锁定生命体状态。
4. 更新状态。
5. 写入互动记录。
6. 写入事件。

如果中间失败，必须回滚。

```php
$pdo->beginTransaction();

try {
    // 多条数据库操作
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}
```

### 10. 行锁

项目使用 `SELECT ... FOR UPDATE` 锁定生命体状态行。

作用：

- 防止两个用户同时修改同一行导致状态错乱。
- 保证互动更新是串行的。

### 11. PHP CLI Worker

Worker 是一个常驻的 PHP 命令行进程：

```text
pulse/worker/pulse-worker.php
```

它每隔几秒执行一次：

1. 采集服务器指标。
2. 写入指标快照。
3. 根据指标计算生命体状态。
4. 写入状态变化事件。

Web 请求负责响应浏览器，Worker 负责持续运行。

### 12. JSON API

PHP 不直接拼 HTML，而是返回 JSON。

例如：

```json
{
  "ok": true,
  "data": {
    "state": {
      "mood": 72,
      "energy": 68,
      "trust": 55,
      "status": "calm"
    }
  }
}
```

前端 JavaScript 拿到 JSON 后更新 3D 生命体。

### 13. OpCache

OpCache 把 PHP 编译后的 OpCode 缓存在共享内存中，避免每个请求重复编译。

项目在 `docker/php/php.ini` 中打开了 OpCache。

### 14. 为什么没有一开始就用 Laravel

本项目没有使用 Laravel 或 ThinkPHP。

原因：

1. 当前目标是做出完整闭环，而不是学习大型框架。
2. 原生 PHP 更适合理解请求、数据库、事务和 Worker。
3. 新手更容易看懂文件职责。
4. 后续可以把 Service 层迁移到框架。

## 四、项目实现过程

### 1. 目录结构

```text
pulse/
├── database/              MySQL 结构与种子数据
├── docker/                Caddy、PHP 配置
├── public/                PHP 入口与前端构建产物
├── src/                   PHP 后端
├── worker/                PHP CLI Worker
├── frontend/              Vite + Three.js 前端
├── tests/                 PHP 规则测试
└── docker-compose.yml
```

### 2. Docker Compose 服务

| 服务 | 镜像 | 作用 |
|---|---|---|
| caddy | caddy:2.8-alpine | Web Server 与 HTTPS |
| php | pulse-php | PHP-FPM API |
| worker | pulse-php | PHP CLI Worker |
| mysql | mysql:8.0 | 数据库 |

### 3. MySQL 表

| 表 | 作用 |
|---|---|
| pulse_state | 生命体当前状态，单行表 |
| metric_samples | 指标与状态历史 |
| visitors | 匿名访客 |
| interactions | 互动记录 |
| events | 事件流 |

### 4. 8 个技术点的实现

| 需求 | 实现 |
|---|---|
| CPU 高时暴躁 | Worker 读取负载，状态改为 irritated，前端暖色脉冲 |
| 内存高时迟钝 | Worker 读取内存，状态改为 sluggish，动画变慢 |
| 磁盘满时焦虑 | Worker 读取磁盘占用，状态改为 anxious，碎片漂浮 |
| 访客多时兴奋 | 统计近两分钟在线访客，状态改为 excited |
| 长时间无人睡眠 | 检查最后访问时间，超过阈值进入 sleepy |
| 四个互动即时反馈 | PHP 校验冷却和事务，前端播放对应动画 |
| 所有人共享同一只 | 所有请求读写同一行 pulse_state |
| 时间轴、报告、成长 | metric_samples、events、interactions 聚合 |

### 5. 演示模式

面试时不可能等待 CPU 真的升高，所以增加受令牌保护的演示模式。

场景：

- cpu-high
- memory-high
- disk-full
- crowded
- lonely
- calm

数据库中的 `source` 字段区分 `real` 和 `demo`。

页面会显示“实时数据”或“演示数据”，不伪装真实指标。

## 五、问题与解决方案

### 问题 1：Docker Hub 拉取 PHP 镜像超时

现象：

```text
failed to authorize: failed to fetch anonymous token
```

原因：

当前网络访问 Docker Hub 的认证服务不稳定。

解决：

1. 使用 DaoCloud 镜像源拉取 PHP、MySQL、Caddy。
2. 拉取后重新打上官方标签。

示例：

```powershell
docker pull docker.m.daocloud.io/library/php:8.3-fpm-bookworm
docker tag docker.m.daocloud.io/library/php:8.3-fpm-bookworm php:8.3-fpm-bookworm
```

经验：

- 国内网络环境下，Docker Hub 经常不稳定。
- 生产部署前要准备镜像加速或提前导出镜像。

### 问题 2：npm 安装依赖提示 EPERM

现象：

```text
npm error code EPERM
```

原因：

沙箱环境不允许写 npm 默认缓存目录，同时需要下载依赖。

解决：

在获得权限后重新执行：

```powershell
npm install
```

经验：

- 本地缓存目录权限会影响 npm。
- 下载依赖属于网络操作，可能触发权限确认。

### 问题 3：Docker Desktop 没有运行

现象：

```text
open //./pipe/docker_engine: Access is denied
```

原因：

Docker CLI 已安装，但 Docker Desktop 守护进程没有启动。

解决：

启动 Docker Desktop，等待 Docker Engine 就绪。

经验：

- 有 `docker.exe` 不代表 Docker 服务在运行。
- 先执行 `docker info` 判断守护进程状态。

### 问题 4：MySQL 与 PHP 时间相差 8 小时

现象：

- PHP 的 `server_time` 是 Asia/Shanghai。
- MySQL 的 `NOW()` 是 UTC。
- 互动时间和访问时间混在一起。

原因：

MySQL 容器时区和 PHP 时区不一致。MySQL 初始化脚本运行时还有可能没有继承最终时区。

解决：

在 SQL 初始化文件中显式设置：

```sql
SET time_zone = '+08:00';
```

同时在 Docker Compose 中设置：

```yaml
command:
  - "--default-time-zone=+08:00"
```

经验：

- 时间和时区要尽早统一。
- 不要同时混用 PHP 本地时间和数据库 `NOW()`。

### 问题 5：中文种子数据乱码

现象：

事件内容显示成 `PULSE åœ¨äº‘...`。

原因：

MySQL 初始化客户端没有明确使用 `utf8mb4`。

解决：

在 `schema.sql` 和 `seed.sql` 顶部添加：

```sql
SET NAMES utf8mb4;
```

经验：

- 数据库表字符集正确不代表客户端连接字符集正确。
- 同时设置 `SET NAMES` 和服务端默认字符集。

### 问题 6：MySQL 忽略 world-writable 配置文件

现象：

```text
mysql: [Warning] World-writable config file '/etc/mysql/conf.d/my.cnf' is ignored.
```

原因：

Windows 挂载到容器中的文件权限显示为 world-writable，MySQL 出于安全原因忽略它。

解决：

移除 `my.cnf` 挂载，把关键参数直接写进 Docker Compose 的 MySQL 启动参数。

经验：

- Windows 和 Linux 的文件权限语义不同。
- 容器挂载配置文件时要注意 Linux 权限检查。

### 问题 7：PHP 提示 `use Throwable` 无效果

现象：

```text
Warning: The use statement with non-compound name 'Throwable' has no effect
```

原因：

`Throwable` 位于全局命名空间，Worker 文件也位于全局命名空间，不需要 `use`。

解决：

删除无意义的 `use Throwable;`。

经验：

- `use` 只对非全局命名空间类有意义。
- PHP Warning 也要清理，否则日志会被噪音淹没。

### 问题 8：Docker BuildKit 快照错误

现象：

```text
parent snapshot ... does not exist
```

原因：

`php` 和 `worker` 两个服务同时基于同一 Dockerfile 构建，触发构建缓存异常。

解决：

让 `php` 构建镜像，`worker` 直接复用同一个 `pulse-php:latest` 镜像。

经验：

- 多个服务共用同一个镜像时，不要重复构建同一 Dockerfile。
- 可以显式指定 `image` 名称，再让其他服务复用。

### 问题 9：浏览器密码管理器污染演示令牌输入

现象：

输入演示令牌后，输入框内容变成 10 个无关字符，接口返回“演示令牌无效”。

原因：

演示令牌输入框原本使用 `type="password"`，浏览器密码管理器尝试自动填充。

解决：

改为：

```html
<input type="text" autocomplete="off" spellcheck="false" />
```

经验：

- 演示令牌不是用户密码，不需要使用 password 输入框。
- 浏览器的自动填充会干扰自动化测试和演示。

### 问题 10：演示面板加载了错误的旧 Token

现象：

浏览器先从 `sessionStorage` 读取了一个无效 Token，接口报错；重新输入正确 Token 后才成功。

原因：

之前的测试在 `sessionStorage` 中留下了无效值。

解决：

重新输入正确 Token 并触发加载。

经验：

- `sessionStorage` 会保留当前标签页会话的数据。
- 调试登录、令牌、向导流程时，要主动检查浏览器存储。

### 问题 11：Chrome 无头模式截图失败

现象：

```text
GPU process isn't usable. Goodbye.
```

原因：

Windows 环境下 Chrome 无头模式尝试启动 GPU 进程失败。

解决：

使用 SwiftShader 软件渲染参数：

```powershell
--disable-gpu --disable-gpu-sandbox --use-angle=swiftshader
```

经验：

- 无头浏览器不等于无 GPU 问题。
- 需要截图验证时，可以关闭 GPU 并使用软件渲染。

### 问题 12：临时 Chrome 用户目录

现象：

项目中出现了 `.chrome-headless-a` 和 `.chrome-mobile`。

原因：

使用 `--user-data-dir` 让 Chrome 把用户数据隔离到工作目录。

解决：

验证完成后删除这些临时目录。

经验：

- Chrome 的 `--user-data-dir` 适合隔离会话。
- 临时目录不要提交到 Git。

### 问题 13：Caddyfile 格式警告

现象：

```text
Caddyfile input is not formatted
```

原因：

Caddy 对 Caddyfile 有固定格式要求。

解决：

```powershell
docker run --rm -v "D:\CodexSpace\PHP Performance Lab\pulse\docker\caddy:/etc/caddy" caddy:2.8-alpine caddy fmt --overwrite /etc/caddy/Caddyfile
```

经验：

- 配置文件能运行不代表格式规范。
- 使用官方 formatter 可以减少低级错误。

### 问题 14：重置本地 MySQL 数据卷

现象：

修改时区和初始化 SQL 后，旧数据仍然使用错误时间。

原因：

MySQL 初始化脚本只在数据卷为空时执行。

解决：

```powershell
docker compose down -v
docker compose up -d --build
```

经验：

- `docker compose down -v` 会删除数据卷，适合本地演示环境。
- 生产环境不能随便执行，必须先备份。

### 问题 15：前端互动按钮冷却期间仍可点击

现象：

前端虽然显示冷却时间，但按钮可以继续点击，后端返回 429。

解决：

在冷却开始和结束时统一控制按钮禁用状态。

经验：

- 前端体验要和后端规则一致。
- 后端仍然是最终规则来源。

## 六、验证记录

### PHP 语法检查

已对全部 PHP 文件执行：

```powershell
docker compose exec -T php sh -lc "find /srv -path /srv/frontend/node_modules -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l"
```

结果：

```text
No syntax errors detected
```

### 规则测试

```powershell
docker compose exec -T php php /srv/tests/run.php
```

结果：

```text
15 passed, 0 failed.
```

覆盖：

- CPU 高
- 内存高
- 磁盘满
- 访客多
- 长时间无人
- 四个互动动作
- 成长阶段
- 状态衰减

### API 验证

已验证：

```text
GET /api/health
GET /api/state
POST /api/interactions
GET /api/timeline
GET /api/report
GET /api/events
GET /api/demo/scenarios
POST /api/demo/scenario
```

### 共享状态验证

使用两个独立 Session：

1. Session A 执行互动。
2. Session B 读取状态。
3. 两个 Session 看到相同生命体状态。

结果：

```text
Shared = True
```

### 演示模式验证

五个演示场景全部通过：

| 场景 | 预期状态 | 结果 |
|---|---|---|
| cpu-high | irritated | 通过 |
| memory-high | sluggish | 通过 |
| disk-full | anxious | 通过 |
| crowded | excited | 通过 |
| lonely | sleepy | 通过 |

### 浏览器验证

已验证：

- 3D Canvas 存在。
- 桌面端截图非空。
- 移动端截图非空。
- 浏览器 Console 没有 error 和 warning。
- 点击“喂养”和“抚摸”后状态数值变化。
- 演示面板可以切换 CPU 高负载并恢复平静。

截图文件：

```text
pulse/artifacts/pulse-final-desktop.png
pulse/artifacts/pulse-mobile.png
```

## 七、Gitee 仓库

仓库地址：

```text
https://gitee.com/hidingdust/pulse-design.git
```

首次提交：

```text
452a682 feat: implement PULSE PHP digital life form
```

首次推送：

```text
master -> origin/master
```

常用命令：

```powershell
cd "D:\CodexSpace\PHP Performance Lab"

git status
git add -A
git commit -m "描述本次改动"
git push
```

注意：

- `.env` 不能提交。
- `frontend/node_modules` 不能提交。
- 真实密码和 Token 不能提交。
- 构建产物 `pulse/public/app` 已提交，方便部署。

## 八、腾讯云部署清单

推荐：

- 地域：香港优先，新加坡备选。
- 系统：Ubuntu 24.04 LTS。
- 规格：2C4G。
- 磁盘：40GB 以上。
- 端口：22、80、443。
- 不开放：3306。

部署步骤：

1. 创建非大陆地区服务器。
2. SSH 登录。
3. 安装 Docker Engine 和 Docker Compose。
4. 拉取 Gitee 仓库。
5. 复制 `.env.production.example` 为 `.env`。
6. 修改生产密码、域名、演示令牌。
7. 启动容器。
8. 配置域名 A 记录。
9. 访问 `/api/health`。
10. 验证首页、3D、互动、时间轴和演示模式。

## 九、面试讲解思路

推荐按下面的顺序讲：

1. 这个项目解决什么展示问题。
2. 为什么选择 PHP 做后端。
3. PHP 如何采集真实指标。
4. MySQL 如何保存状态和历史。
5. Worker 为什么不能写在普通 HTTP 请求里。
6. 为什么互动要用事务和行锁。
7. 前端为什么用 JS 和 Three.js。
8. 演示模式如何保证面试现场可展示。
9. 部署在腾讯云非大陆地区，不需要备案。

可以用一句话总结：

```text
PHP 负责让它活着，MySQL 负责让它记得，Worker 负责让它持续呼吸，Three.js 负责让它被看见。
```

## 十、下一步学习路线

### PHP 基础

1. 变量、数组、函数、类。
2. 命名空间和自动加载。
3. PDO 预处理。
4. Session 和 Cookie。
5. 异常处理。
6. 事务和锁。

### MySQL

1. 主键和索引。
2. JSON 字段。
3. 事务隔离级别。
4. 慢查询和 `EXPLAIN`。
5. 备份与恢复。

### Docker

1. 镜像、容器、数据卷、网络。
2. Dockerfile。
3. Docker Compose。
4. 环境变量与密钥管理。

### Three.js

1. 场景、相机、渲染器。
2. 几何体、材质、灯光。
3. 动画循环。
4. 粒子系统。
5. 性能优化。

### 工程化

1. 单元测试。
2. CI。
3. 日志和监控。
4. 安全加固。
5. 自动化部署。

## 附录：常用命令

启动：

```powershell
cd "D:\CodexSpace\PHP Performance Lab\pulse"
docker compose up -d --build
```

停止：

```powershell
docker compose down
```

查看状态：

```powershell
docker compose ps
```

查看 Worker 日志：

```powershell
docker compose logs -f worker
```

运行 PHP 测试：

```powershell
docker compose exec -T php php /srv/tests/run.php
```

构建前端：

```powershell
cd frontend
npm run build
```

## 附录：术语表

| 术语 | 解释 |
|---|---|
| PHP-FPM | PHP 进程管理器 |
| PDO | PHP 访问数据库的统一接口 |
| Session | 服务器端保存的访客状态 |
| CSRF | 跨站请求伪造 |
| Worker | 常驻后台进程 |
| Caddy | 自动 HTTPS 的 Web Server |
| Three.js | 浏览器 3D 渲染库 |
| Docker Compose | 多容器编排工具 |
| OpCache | PHP OpCode 缓存 |
| 事务 | 多条数据库操作的原子执行 |
| 行锁 | 锁定某一行，防止并发修改冲突 |

