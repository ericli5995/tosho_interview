# THINK ENGINEERING — 产品展示网站

一个支持厂商产品展示的网站：前台展示产品目录、支持产品检索；小后台登录后可以新增、编辑、删除产品并上传产品图片。


---

## 1. 本地环境搭建

为了保证部署稳定性和一致性，本项目使用Docker Container。只需要 Docker Desktop，本机不用装 PHP、Node 或 MySQL。

```bash
cd tosho_interview          # 进入项目根目录
docker compose up --build
```

本项目只占用宿主机的 8080 端口；MySQL 仅在 Docker 内部网络可见，不会与本机已有的 MySQL 冲突。8080 被占用时把 `docker-compose.yml` 里的 `"8080:80"` 改成其他端口即可。

首次启动时 MySQL 容器会自动导入 `sql/init.sql`：建表、10 个演示产品（6 个带图）和默认管理员。启动完成后：

| 地址 | 说明 |
| --- | --- |
| <http://localhost:8080> | 首页 |
| <http://localhost:8080/admin> | 小后台，可用 `admin@example.com` / `password123` 直接登录 |

## 2. 页面截图

前台首页：代表产品 + 产品一览

![首页](docs/screenshots/home.png)

小后台产品列表：分页、状态、编辑 / 删除

![小后台产品列表](docs/screenshots/admin-products.png)

---

## 3. 项目架构

```
public/                    Apache 根目录，唯一对外的目录
  index.php                API 入口：autoload → Application::run()
  *.html                   前台页面：首页、检索、详情、占位页（Vue 在页面内挂载）
  admin/                   后台页面：login, products, product-form, users
  assets/                  css（app, admin）、js（api.js, layout.js, pages/*, vendor/ 来自 npm）
  media -> ../storage/uploads   符号链接，容器构建时创建
src/                       PSR-4  App\ -> src/
  Core/                    Application, Router, Request, Response, Db, Config, App, Controller
  Http/Controllers/        ProductController（公开）, SessionController（登录 / 登出 / CSRF）
    Admin/                 ProductController（产品增删改）, UserController（管理员 新增 / 改密 / 删除）
  Http/Middleware/         RequireAuth（401）, VerifyCsrf（403）
  Security/                Session, Auth, Csrf, Password
  Entity/                  Product, AdminUser：类型化的行对象，toArray() 即 API 输出
  Repository/              ProductRepository, AdminUserRepository：所有 SQL 在这里
  Services/Image/          ImageUploadService, UploadException
  Services/Product/        ProductService（事务 + 图片文件）, SearchCriteria（检索参数钳制）
  Validation/              Validator（'required|string|max:60' 规则串）
  Support/                 helpers.php：env(), config(), str_slug()
config/                    app.php, database.php, routes.php
sql/init.sql               建表 + 演示数据 + 默认管理员，MySQL 首次启动自动导入
docker/                    apache.conf, php.ini
storage/                   uploads/demo（入 git）、uploads/products（忽略）、sessions（忽略）
Dockerfile, docker-compose.yml, composer.json, package.json
```


---

## 4. 技术要点

1. 前后端分离，后端只返回 JSON

2. session 保护后台，CSRF token 防跨站请求伪造

3. SQL 全部参数化，排序白名单，分页参数钳制，防 SQL 注入

4. 上传图片按内容识别类型、限制大小与像素、GD 重编码、随机命名

5. 检索：关键字命中型号、名称和标签，服务端分页，未发布产品对外不可见

6. 不用框架：路由、请求 / 响应、PDO 封装约 560 行自写，Controller → Service → Repository 分层，无第三方 PHP 包

---
