# HIFIVE — 安装、部署与使用指南

HIFIVE 是 PHP 8.2+、MariaDB 10.4+、HTML/CSS/JavaScript 编写的服装店课程项目，付款为模拟。普通顾客直接访问网站，无需安装软件；下面的设置步骤供组员和部署负责人使用。

| 你要做什么 | 阅读位置 |
|---|---|
| 直接使用共同网站 | [线上访问](#online) |
| 在自己的电脑开发 | [本地安装](#local) |
| 配置数据库／AI | [数据库](#database)、[AI](#ai) |
| 上传到 alwaysdata | [部署](#deployment) |
| 更新已有网站 | [安全更新](#updates) |
| 搬迁／备份 | [备份恢复](#backup) |
| 排查错误 | [常见问题](#troubleshooting) |
| 验证功能 | [测试](#testing) |
| 查改动／学习代码 | [CHANGELOG.md](CHANGELOG.md)、[代码讲解](docs/CODE-WALKTHROUGH.md) |

<a id="online"></a>
## 1. 线上访问与小组协作

共同网站：[https://hifive.alwaysdata.net/](https://hifive.alwaysdata.net/)，实际地址以账户所有者在 alwaysdata 的 Web > Sites 设置为准。

- 用户各自注册、登录；私人购物车、地址和订单按账号隔离。
- 网站、数据库、上传图片统一在服务器，电脑关机也可访问。
- 本地源码位置：`C:\xampp\htdocs\Fashion`；本地保存不会自动更新线上。
- 每人使用独立本地数据库开发，功能分支合并到 `testing` 后，由一名负责人部署共同网站。
- GitHub 同步代码，不同步数据库行、忽略的图片、密钥或 `.installed`。
- 已有品牌改为 HIFIVE，不要求数据库同名。已有 `forme` 或云端前缀数据库可继续使用。

<a id="features"></a>
## 2. 功能与重要规则

| 模块 | 功能 |
|---|---|
| Product | 分类、商品、图片及颜色/尺码 variant 管理，搜索、停用、每个 variant 的最低库存提醒 |
| Promotion | 优惠 CRUD/搜索、启用与日期限制、最低消费、结账验证和优惠快照 |
| Customer | 注册登录、个人电话、完整/默认地址、地址选择、后台顾客搜索与订单历史、停用 |
| Shopping cart | 数量、勾选/全选、所选金额、删除确认、仅购买和移除勾选项目 |
| Order | 模拟付款、订单搜索/状态筛选、状态操作记录、取消与库存恢复 |
| Review and Rating | 完成购买后评分评论、编辑、删除、审核隐藏/恢复、公开评论平均分和数量 |

推荐助手有两个独立入口：右下角浮动聊天，以及勾选购物车商品后的互补单品推荐。共享后台还支持首页/分类横幅和品牌/联系设置。

- 商品价格由服务器读取；颜色和尺码必须匹配同一个可用 variant。
- 购物车不预留库存。结账在事务及行锁下重新检查库存、价格和选择；发生变化需重新确认。
- PHP 金额用整数分计算。运费 RM8，折前商品小计达到 RM200 免运费；优惠不折扣运费。
- 一个订单使用一个百分比优惠。优惠码转大写、唯一，时间按马来西亚时间；结束时间精确到保存的时刻。
- 订单保留商品名称、颜色、尺码、图片、价格、送货地址、优惠码和金额快照。
- 相同成功结账请求不会重复创建订单。拒绝模拟付款不创建订单/付款，不扣库存、不清购物车。
- 订单按 Confirmed → Processing → Shipped → Completed 流转。只有 Confirmed/Processing 可以取消，库存只恢复一次。
- 管理员只能删除已取消的示范订单，删除不再次恢复库存。有购买历史的产品/variant 应停用，不物理删除；有订单的顾客不能物理删除。
- 评论要求完成购买，每个顾客每个商品一条；隐藏或顾客删除的评论不计入公开平均分。删除再重建评论不能绕过审核。
- 实际表结构采用 `users.role` 区分管理员/顾客，`cart_items.user_id` 保存购物车归属；一笔订单一条成功模拟付款，并有 `order_status_history`。报告 ERD 应按实际结构绘制。

<a id="local"></a>
## 3. 本地全新安装（已有资料不要走这一步）

1. 安装 [XAMPP](https://www.apachefriends.org/) 和 [VS Code](https://code.visualstudio.com/)，使用 PHP 8.2+、MariaDB 10.4+。XAMPP 的 MySQL 按钮通常运行 MariaDB。
2. 将源码放到 `C:\xampp\htdocs\Fashion`，确认 `index.php` 直接在这个目录，不要多套一层 Fashion。
3. 复制 `config.example.php` 为 `config.local.php`，填写本地数据库设置，使用空的新开发库。已有同名数据库先备份或换新库名。
4. 启动 XAMPP Apache 和 MySQL，不必启动 FileZilla/Mercury/Tomcat。
5. 打开 `http://localhost/Fashion/setup.php`，创建管理员，密码长度 10–72。安装器同时建立表和示例商品/海报，完成后生成 `.installed`。
6. 打开 `http://localhost/Fashion/`，登录后进入 Account → Manage store。另注册一个顾客账号测试购物流程。

不要双击 PHP，也不要用 VS Code Live Server 运行它。不需要 npm 或 Composer。使用云端数据库/API 时需要网络；本地规则推荐可不配置外部 AI。

<a id="database"></a>
## 4. 数据库与私人配置

| 文件 | 作用 | GitHub |
|---|---|---|
| `config.example.php` | 不含真实凭据的数据库模板 | 提交 |
| `config.local.php` | 当前安装的实际数据库连接 | 忽略 |
| `ai.example.php` | 不含真实 key 的 AI 模板 | 提交 |
| `ai.local.php` | 当前安装的实际 AI 设置 | 忽略 |
| `.installed` | 安装锁 | 忽略、部署时保留 |
| `uploads/` 内容 | 后台上传图片 | 通常忽略、单独备份 |

数据库配置示例（仅本地 XAMPP 默认环境）：

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'hifive',
    'username' => 'root',
    'password' => '',
];
```

线上 Host/数据库名/用户名/密码从 alwaysdata 的 Databases > MySQL 取得，不使用本地 root/空密码。密码不是后台登录密码，也不是 SFTP 密码。

默认 SQL 中的旧数据库名是安装占位符，由 setup 按配置替换；不要仅为品牌改名而改 SQL 或已有数据库。账号改名后以后台显示的新连接资料更新配置。

只共享云端数据库、PHP 仍分别在各人电脑上运行时，数据行共享，但图片和 AI 配置仍不共享，且远程多次查询可能慢。共同测试使用整个线上网站。

<a id="ai"></a>
## 5. AI 配置与浮动聊天

1. 选择兼容 Chat Completions、JSON-object 输出和 `max_completion_tokens` 的 provider/model。
2. 复制 `ai.example.php` 为 `ai.local.php`，保留其他设置并填写：

```php
'enabled' => true,
'endpoint' => '服务商提供的HTTPS Chat Completions地址',
'api_key' => '实际key，仅放服务器',
'model' => '该账户可以调用的模型名称',
'cart_ai_enabled' => true,
'timeout_seconds' => 20,
```

3. 示例中的 `getenv` 支持 `FASHION_AI_KEY` 和 `FASHION_AI_MODEL`；只有实际 local 文件保留对应读取表达式时，环境变量才会生效。
4. 确认 PHP cURL、TLS 证书正常。不要关闭证书验证；按服务商能力设置预算/用量限制。
5. 登录顾客账号，打开右下角 Style assistant，输入颜色、尺码、风格、场合或每件预算，再多轮修改。商品链接不会静默加入购物车。

显示 **AI-assisted** 才表示模型参与；没有配置或服务失败时会明确显示 **Catalogue rules**，不能把规则推荐当作真实模型。规则识别常见中英文偏好，不是通用聊天模型。

候选只取已发布商品及启用、有库存的 variant。服务器执行偏好、排除条件、ID/重复项检查，刷新价格和可用性；无匹配不编造商品。互补角色从分类名称推断，无法识别的分类不乱猜。准备准确的分类、标签、照片及足够搭配商品。

购物车模型推荐可单独关闭；相同选择/商品/模型结果缓存约十分钟。聊天不依赖购物车。模型接收商品资料和有限对话，不接收顾客账号/地址/订单记录，也不能修改库存或价格。用户照片虚拟试衣尚未实现。

浮动面板只出现在顾客页面，管理员/setup 不显示。Enter 发送、Shift+Enter 换行、Escape 关闭、重置清除偏好和对话但不绕过冷却。打开状态按浏览器标签保存，对话保存在 PHP session，退出登录清除；需要 JavaScript。

`index.php?page=assistant_api` 是 JSON 接口。GET 读状态，POST message/reset 要求登录与 CSRF；请求 ID 去重。网络错误保留输入，可用 Refresh chat 获取服务器已完成的结果后再重试。旧 assistant 页面链接会转到首页打开面板。

不要把 key 放入 JS、GitHub、截图或报告。共享网站只需服务器一份配置，访客无需收到 key。接口说明：[Chat Completions](https://developers.openai.com/api/reference/resources/chat)。真实 provider 是否可用必须实际验证，历史 fixture 测试不能证明它。

<a id="deployment"></a>
## 6. alwaysdata 全站部署

入口：[后台](https://admin.alwaysdata.com/)、[FileZilla Client](https://filezilla-project.org/download.php?type=client)。下面按现有 `hifive` 账户举例，实际路径和凭据以后台为准。

### 6.1 已有云端数据库

云端已有商品/用户时先导出备份，然后直接使用现有连接。**不要重新导入旧 SQL，也不要运行 setup。** 如从未迁移数据库，按下一节“备份恢复”先导入一次到空云端库。

### 6.2 上传

1. 后台 Remote access > SSH/SFTP 允许用户连接。FileZilla > File > Site Manager：

   | 字段 | 示例 |
   |---|---|
   | Protocol | SFTP – SSH File Transfer Protocol |
   | Host | `ssh-hifive.alwaysdata.net` |
   | Port | `22` |
   | User / Password | SSH/SFTP 页面对应用户，不是数据库用户 |

2. 创建远程目录 `/home/hifive/www/fashion/`，上传 `index.php`、`setup.php`、`app/`、`assets/`、`database/`、根 `.htaccess`。首次迁移另外私下上传 `uploads/`、两个 local 配置和 `.installed`。
3. 目录应直接包含 `index.php`；确认隐藏文件和 `uploads/.htaccess` 都上传，Failed transfers 为空。不上传 `.git`、私人 SQL/压缩备份或日志。README/CHANGELOG/代码讲解可保留在代码仓库供组员使用。
4. 线上 `config.local.php` 指向云端库，AI 设置在服务器配置。后续上传不要覆盖这些服务器文件或删除新图片。

### 6.3 网站入口与 HTTPS

在 Web > Sites 编辑已有网站，不重复绑定同一地址：

| 字段 | 示例 |
|---|---|
| Addresses | `hifive.alwaysdata.net`（不含 https://） |
| Type | PHP |
| Root directory | `/home/hifive/www/fashion/`；若有固定 home 前缀，只填 `www/fashion/` |
| PHP version | 8.2 或以上支持版本 |

若同目录有默认欢迎页 `index.html`，将它改名。网站根目录是 fashion 时，公开网址不用加 `/fashion/`。

Environment > PHP 可设置账户版本和 php.ini，Web > Sites 可有单站覆盖。确认 `pdo_mysql`、`curl`、`mbstring`、`fileinfo` 可用。默认上传额度若已足够不用降低；需要时设置：

```ini
upload_max_filesize = 5M
post_max_size = 16M
```

单张图片由应用限制为 5 MB，post 限制需容纳整个表单。保留正常目录权限，不设全目录 777。

在网站 SSL 设置启用 Force HTTPS，账户 alwaysdata.net 地址使用平台证书。测试配置和 `.installed` URL 返回拒绝访问。关闭本地 XAMPP 后，从电脑和手机移动数据打开线上网址验证独立运行。

官方说明：[SFTP](https://help.alwaysdata.com/en/docs/web-hosting/remote-access/sftp/)、[网站入口](https://help.alwaysdata.com/en/docs/web-hosting/sites/add-a-site/)、[PHP](https://help.alwaysdata.com/en/docs/web-hosting/languages/php/configuration/)、[HTTPS](https://help.alwaysdata.com/en/docs/web-hosting/sites/ssl-tls/redirect-http-to-https/)、[账号改名](https://help.alwaysdata.com/en/docs/admin-billing/accounts/rename-account/)。免费配额与非营利用途限制以当前平台条款为准。

<a id="updates"></a>
## 7. 更新已有网站

1. 备份数据库、代码和上传图片；先合并组员修改，避免覆盖未提交工作。
2. 上传修改的代码到实际网站根目录，保留线上 `config.local.php`、`ai.local.php`、`.installed` 和 `uploads`。
3. 品牌/设计文字修改不需要数据库 migration。HIFIVE 兼容旧品牌设置；后台 Brand & contact 保存 HIFIVE 可持久化，含旧文字的上传 logo/海报要另换图片。
4. 只有旧数据库需要功能升级时，先核对配置指向哪里，再从部署目录执行：

```powershell
Set-Location 'C:\xampp\htdocs\Fashion'
& 'C:\xampp\php\php.exe' '.\database\migrations\20261120_features.php'
```

该脚本包含 earlier promotion migration，补字段/表并按历史订单金额回填付款，可重复执行。MariaDB DDL 隐式提交，部分失败后修正原因再运行，不要清空数据。云端执行需要使用云端 PHP CLI 和部署目录；本地执行若配置指向云端，也会改云端库。

旧地址的 state 无法可靠推断，手动补全；补充商品 style/occasion 标签和 variant 最低库存。更新后 Ctrl+F5，并完成下方测试。不要为更新而重新安装。

<a id="backup"></a>
## 8. 备份、搬迁与恢复

完整备份有三部分：SQL（全部表结构及数据）、`uploads` 图片、代码与私人配置/安装锁。Git clone 不会恢复后二者。

一次性搬迁：暂停资料修改 → 在源数据库 phpMyAdmin Export 全部表结构和数据 → 复制文件 → 目标新建空库 → Import SQL → 设置目标连接 → 保留 `.installed` → 使用原账号登录。

数据库名不同或云端无建库权限时，在导入用副本中移除指定旧库的 CREATE DATABASE/USE 语句，再导入选中的空目标库，保留原始备份。不要运行 setup 重新创建商店。恢复已有资料且安装锁遗漏时，确认库和配置正确后补回 `.installed`。

一次性复制不是持续同步。迁移完成以共同网站为小组数据源，不反复将本地旧 SQL 覆盖线上。备份含个人资料及密钥，私下保存，不提交公开仓库。

<a id="content"></a>
## 9. 后台内容与图片

- Homepage content：主海报、宣传海报、文字/颜色/排序/显示和手机图。
- Departments & categories：分类卡片图；Category page banners：不可点击的分类页横幅，子分类可继承部门图，Reset 清除覆盖设置。
- Products、Colours, images & stock：商品、variant、每色主图及细节图。
- Brand & contact：品牌名、tagline、联系资料。固定品牌图改为 assets/branding 中的三个 SVG，后台不再展示旧的备用 logo 上传字段。

JPG/PNG/WebP，单张最多 5 MB；拒绝上传 SVG 和可执行文件。建议产品图 3:4、横向海报约 1920×1080、手机图约 900×1200，最终构图要按页面裁切实测。内置 SVG 是占位插画，不是真实服装照片。

主题使用 Cormorant Garamond/Manrope，字体和 OFL 授权保留在 assets/fonts。首屏直角海报贴边、透明导航叠加，支持滑动/键盘、不自动播放；宣传海报在部门卡片后。圆角卡片/表单和装饰动画尊重减少动态效果设置。图片压缩、WOFF2 和批量查询是可评估优化，不代表已实现。

<a id="testing"></a>
## 10. 测试

在项目根目录运行以下规则/fixture 检查，不需要实际模型调用：

```powershell
& 'C:\xampp\php\php.exe' tests/promotions.php
& 'C:\xampp\php\php.exe' tests/features.php
& 'C:\xampp\php\php.exe' tests/recommendation-service.php
& 'C:\xampp\php\php.exe' tests/assistant-service.php
& 'C:\xampp\php\php.exe' tests/schema-readiness.php
```

部署验收使用管理员与独立顾客窗口：测试价格/图片共享、正常和无效优惠、勾选结账、拒绝付款、重复提交、库存扣减和取消恢复、订单权限/状态、完成购买后评论及隐藏后的平均分。测试手机号/默认地址、停用账号及 variant、无匹配推荐、AI 失败回退、浮动面板发送/关闭/网络恢复和手机布局。

配置文件 URL 应拒绝访问；测试非法上传、上传限制及新图片跨设备显示。用测试记录，不要对共享库运行重置/破坏性 fixture。历史结果与这次实际执行结果见 [CHANGELOG.md](CHANGELOG.md)，不能代替当前线上验收或真实 API 测试。旧截图可能保留旧品牌。

<a id="troubleshooting"></a>
## 11. 常见问题

| 问题 | 先检查 |
|---|---|
| localhost 无法访问 | Apache、端口、是否通过浏览器 HTTP 打开 |
| 默认 alwaysdata 欢迎页 | Web > Sites 根目录、对应域名和默认 index.html |
| 404 | 实际 URL/地址绑定/包含 index.php 的目录；根目录已是 fashion 时不要再加 /fashion/ |
| FTP 无法连接 SFTP | FileZilla 改 SFTP、22 端口、SSH/SFTP 用户密码 |
| 数据库连接失败 | 当前配置指向本地还是云端；Host/port/database/user/password；账号改名后的连接地址 |
| An administrator already exists / 回到 setup | 已有商店不要重装，核对 `.installed`、配置和升级状态 |
| Database upgrade required | 备份后从正确部署副本运行 migration，不导入 fresh schema |
| 图片缺失／上传失败 | uploads、大小写、目录权限、MIME/尺寸与 PHP 上传限制 |
| 刷新仍显示旧品牌 | 本地代码是否已上传正确目录、SVG 是否更新、数据库品牌设置或自定义 logo 是否仍旧 |
| AI 只有规则推荐 | enabled/key/model/endpoint/cURL/TLS；面板打开不等于模型连接成功 |
| 网站慢 | 测线上响应与资源；区分本地跨网查库、查询数量、图片/字体和主机配额 |
| 登录／删除受限 | 正确数据库、账号 active/role、订单历史/评论购买资格等业务规则 |

查看实际 PHP/Apache/数据库日志，提供错误文字时遮住密码和 key。网页 HTML 下载时间不等于浏览器全部渲染时间。

## 12. 项目范围

没有真实支付/退款处理、邮件找回密码、虚拟试衣或任意拖拽建站功能。课程报告、ERD/UML、组员贡献、测试/UAT 应反映实际实现。代码讲解保留在 [docs/CODE-WALKTHROUGH.md](docs/CODE-WALKTHROUGH.md)，每次修改持续记录在 [CHANGELOG.md](CHANGELOG.md)。


<a id="auto-deploy"></a>
## 13. GitHub testing 自动部署

配置文件 `.github/workflows/deploy-testing.yml` 监听 testing 分支的 push，或在 Actions 手动触发（仍只允许 testing）。本地保存、commit 尚未 push、其他分支的 push、未合并的 pull request 都不会更新网站。

流程：组员提交功能分支 → 合并到 testing → Actions 检查 PHP 和五个规则测试 → SFTP 上传固定应用目录。按同一部署组串行处理，避免两个部署同时覆盖。每个文件先传临时文件再原子替换；不是全站事务，部署失败可能已有部分文件更新，需要修复后重新运行。

### 一次性设置（仓库所有者／有权管理 Actions Secrets 的组员）

1. 在 alwaysdata 的 Remote access > SSH/SFTP 建立部署用户或使用已有有权限的用户，允许密码连接。其可访问路径必须与 ALWAYSDATA_PATH 一致。
2. 仓库 https://github.com/tiruhui1214/Fashion → Settings → Secrets and variables → Actions → New repository secret，逐个添加：

   | Secret 名称 | 值 |
   |---|---|
   | ALWAYSDATA_HOST | ssh-hifive.alwaysdata.net |
   | ALWAYSDATA_USER | SSH/SFTP 用户名，当前通常为 hifive，以后台为准 |
   | ALWAYSDATA_PASSWORD | 该 SSH/SFTP 用户密码，不是 MySQL 或 AI 密码 |
   | ALWAYSDATA_PATH | /home/hifive/www/fashion（以该用户实际可访问路径为准） |
   | ALWAYSDATA_KNOWN_HOSTS | 已核对的 OpenSSH known_hosts 主机公钥行，见下一步 |

3. 用已安装 OpenSSH 的 PowerShell 获取候选公钥及指纹：

   ```powershell
   ssh-keyscan -t ed25519 ssh-hifive.alwaysdata.net 2>$null | Set-Content -Encoding ascii "$env:TEMP\hifive-known-hosts"
   ssh-keygen -lf "$env:TEMP\hifive-known-hosts"
   Get-Content "$env:TEMP\hifive-known-hosts"
   ```

   ssh-keyscan 只是抓取候选公钥，不验证身份。将指纹与之前可信的 FileZilla 连接所显示的同种 ED25519 主机指纹，或平台提供的指纹核对。匹配后，把 Get-Content 输出的完整主机公钥行存入 ALWAYSDATA_KNOWN_HOSTS；不要把 SHA256 指纹字符串或用户私钥当作该值。命令不存在时可使用 Git Bash 的 ssh-keyscan/ssh-keygen。不要关闭主机验证。
4. 提交 workflow、部署脚本及相关项目改动到 testing 并 push。两个 example 配置模板也需要存在于仓库；local 配置不要提交。若没有仓库设置权限，由仓库所有者完成 Secrets。
5. 打开仓库 Actions → Deploy testing to alwaysdata，检查语法/规则步骤和上传步骤都绿色。首次建议大家暂停编辑测试，部署前私人备份当前代码和上传内容。
6. 在 https://hifive.alwaysdata.net/ 检查页面，Ctrl+F5，再验证登录、商品和关键流程。成功日志显示上传文件数和 commit SHA，不等于所有线上业务已验证。

### 会上传什么

仅上传 Git 已跟踪的 app/、assets/、database/ 及根 index.php、setup.php、.htaccess、config.example.php、ai.example.php。不上传 `.github`、`.vscode`、README/CHANGELOG、tests、uploads、真实 local 配置、安装锁和常见日志/备份。提交前仍需确认源码没有误放密钥或私人 SQL。

脚本要求远端已存在 index.php、config.local.php、.installed，防止误指向新目录。不会删除任何远端文件，不会执行 setup 或数据库 migration。删除/改名代码后，旧远端文件需要部署负责人确认后清理；数据库升级仍按第 7 节安排。目录或主机改名后更新对应 Secrets。

若 Actions 红色，网站没有完成本次部署；先看失败步骤。Missing secrets 检查五项 Secrets；SSH/SFTP 问题检查 host/密码/权限/已验证公钥；路径问题检查部署目录及三个必需文件。修复后在 testing 上重新运行。私有仓库 Actions 用量受账户套餐限制，不保证无限免费运行。

保存本地代码本身不会触发部署。只有 push/合并到 testing 才会触发，所以组员仍需提交并推送代码；不需要每人安装 FileZilla 或拿到服务器密码。

参考：[GitHub Secrets](https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets)、[alwaysdata SSH/SFTP](https://help.alwaysdata.com/en/docs/web-hosting/remote-access/sftp/)。


## 品牌 Logo 文件

assets/branding/HIFIVE_PrimaryLogo.svg 用于导航、登录/注册和安装页；HIFIVE_BrandLogo.svg 用于页脚与后台；HIFIVE_Favicon.svg 用于浏览器标签。固定资源采用文件修改时间作版本参数，原始文件未改写。当前后台旧 logo 设置及上传图不会被删除，但不再覆盖固定品牌图。任意 SVG 上传仍不开放，safe_image 仅额外允许这三个固定路径。部署上传三个 SVG、index.php、setup.php、app/bootstrap.php、app/account.php、app/admin.php 和 assets/style.css。浏览器 favicon 可能需要无痕窗口或清缓存查看。三个 SVG 总计约 3 MB，包含内嵌 PNG；可另行优化格式，本次保留原素材。
