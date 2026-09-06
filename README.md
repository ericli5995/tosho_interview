# THINK ENGINEERING — 产品展示网站

一个小型齿轮马达厂商的产品展示站：前台展示产品目录、支持产品检索；后台（小后台）登录后可以新增、编辑、删除产品并上传产品图片。


---

## 1. 本地环境搭建

为了保证部署稳定性和一致性，本项目使用Docker Container。只需要 Docker Desktop，本机不用装 PHP、Node 或 MySQL。

```bash
cd ~/{project_folder}/tosho_interview          
docker compose up --build
```

首次启动时 MySQL 容器会自动导入 `sql/init.sql`：建表、10 个演示产品（6 个带图）、以及默认管理员。启动完成后：

| 地址 | 说明 |
| --- | --- |
| <http://localhost:8080> | 首页 |
| <http://localhost:8080/admin> | 小后台 可用 `admin@example.com` `password123` 直接登陆|

---

### 2. 项目结构

```
config/        应用配置、数据库连接、路由表
public/        前后台页面 + API 入口 index.php
sql/           创建数据库 + 导入初始数据
src/           Core 自建核心框架 + 后端代码
storage/       图片上传目录与 session 文件
```

### 5.3 安全要点

- 所有 SQL 参数化，`ORDER BY` 白名单，`LIMIT` 钳制；LIKE 通配符转义。
- 密码 bcrypt，登录成功后 `session_regenerate_id`，登出同样轮换；`use_strict_mode` 开启。
- 公开页面从不创建 session，后台每个写操作都验证 `X-CSRF-Token`（常量时间比较）。
- `src/`、`config/`、`sql/`、`storage/` 都在 DocumentRoot 之外；`/media` 只放行图片后缀且关闭 PHP。
- 上传文件按内容识别、限制大小和像素、GD 重编码、随机命名。
- API 响应带 `X-Content-Type-Options: nosniff` 和 `Cache-Control: no-store`；页面和脚本 `no-cache`，重新构建后浏览器不会用旧缓存。
- 前端所有动态内容走 Vue 的文本插值，没有 `v-html` / `innerHTML`。
