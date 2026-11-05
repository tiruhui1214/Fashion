# FORME Windows 安装、搬到另一台电脑运行

这份教程分两种情况。**第一次运行下载包**用 A；**把已经改过的项目搬到另一台电脑**用 B。

## A. 第一次运行

### 1. 下载软件

- XAMPP：https://www.apachefriends.org/ ，选择 Windows、PHP 8.2 的版本。
- VS Code：https://code.visualstudio.com/ ，选择 Windows。
- 浏览器用 Chrome 或 Edge。
- GitHub Desktop 是可选工具，第一次通过 ZIP 下载不需要它。

XAMPP 提供 Apache、PHP 和 MariaDB。控制面板上的数据库按钮仍然叫 **MySQL**。不需要再单独装一套 MySQL，也不需要 Node.js。

如果另一台电脑已经有这些软件，检查 PHP 至少为 8.2、数据库为 MariaDB 10.4 或以上即可。不要卸载或覆盖之前的项目和数据库。

### 2. 放好整个项目

1. 下载 FORME 源码 ZIP。
2. 解压，找到里面含 `index.php`、`setup.php`、`app`、`assets`、`database` 的 **Fashion** 文件夹。
3. 把这个完整文件夹放到 `C:\xampp\htdocs\Fashion`。
4. 最后确认存在 `C:\xampp\htdocs\Fashion\index.php`，不要多套一层 `Fashion\Fashion`。
5. 在 VS Code 选择 **File → Open Folder**，打开这个 Fashion 文件夹。

不要一段一段复制代码；复制整个文件夹才能保留目录结构、样式和图片。

### 3. 开启服务器

打开 XAMPP Control Panel，在 **Apache** 和 **MySQL** 两行点击 **Start**。成功时会显示正在运行。

- 不需要启动 FileZilla、Mercury 或 Tomcat。
- 如果 Apache 不能启动，查看日志，可能是 80/443 端口被其他程序使用。
- 如果改用 Apache 8080 端口，下面的网站地址也要加 `:8080`。
- 如果 MySQL 使用 3307 等其他端口，需要修改项目的数据库端口配置。

### 4. 检查数据库连接

项目默认值适合常见的全新本地 XAMPP：

```text
host: 127.0.0.1
port: 3306
database: forme
username: root
password: 空白
```

如果你的设置不同：

1. 复制 `config.example.php`。
2. 将副本改名为 `config.local.php`。
3. 在 VS Code 修改副本中的 host、port、database、username、password。
4. 保存。不要把密码发在聊天或提交到公开 GitHub。

如果这台电脑已经有名为 `forme` 的数据库，先备份，或在本地配置里换一个新的数据库名称，例如 `forme_assignment`。不要直接在旧数据库上重新安装。

### 5. 初始化新网站

在浏览器地址栏输入：

```text
http://localhost/Fashion/setup.php
```

填写你自己的管理员姓名、邮箱和密码，密码至少 10 个字符。点击 **Create local store**。

安装器会创建表、8 件示例商品、规格库存和示例海报。它不是只创建一个管理员；请只用于新的空数据库。

成功后打开：

```text
http://localhost/Fashion/
```

点击 **Sign in**，使用刚才创建的管理员账户。进入 **Account → Manage store**。

不要双击 `index.php`，也不要使用 VS Code 的 Live Server 来运行 PHP。网站要通过 Apache 打开。

### 6. 更换图片

- **Homepage content**：主海报和首页中段海报。图片、标题、颜色、文字位置和链接分开设置。
- **Departments & categories**：分类入口图片。每个部门的第一条分类图片用作该部门的首页入口图。
- **Products**：商品名称、价格、介绍、材质、是否展示。
- **Colours, images & stock**：每个颜色／尺码的主图和库存；下方添加同色的模特细节图。
- **Brand settings**：品牌文字、Logo、联系方式。

上传格式为 JPG、PNG、WebP，单张最大 5 MB。产品照片建议统一 3:4；大海报建议 1920×1080，手机海报建议 900×1200。网页会裁切图片填满区域，需要自己准备合适构图。

示例 SVG 是占位插画，不是实际商品照片；后台不接受用户上传 SVG。

### 7. 试玩顾客流程

另外注册一个顾客账户，不要只用管理员账户测试。选商品 → 颜色 → 尺码 → Add to bag → Checkout → Approve demo payment。

无需输入银行卡。选择 Decline demo payment 可测试失败提示。管理员可以处理订单；顾客在发货前取消订单，库存会恢复一次。

## B. 把已经做好的项目搬到另一台电脑

需要搬运 **三样内容**：代码、上传图片、数据库。仅复制 PHP 文件不会带走数据库中的商品与订单。

### 原电脑：导出

1. 暂停其他组员在这台电脑新增或修改资料。
2. 打开 `http://localhost/phpmyadmin/`。
3. 左侧点击项目数据库 `forme`（或你设置的名称）。
4. 点击 **Export → Quick → SQL → Export**，保存为 `forme-backup.sql`。
5. 复制完整的 `C:\xampp\htdocs\Fashion`，包含 `uploads` 和隐藏的 `.installed` 文件。
6. 用 U 盘、你自己的云端文件夹或其他安全方式，搬运该文件夹及 SQL 备份。

代码 ZIP 不会自动带走 PHPMyAdmin 里的数据库。不要只复制 `schema.sql`，因为它只有表结构，没有你后来上传的商品和订单。

### 另一台电脑：恢复

1. 安装／启动 XAMPP 的 Apache 和 MySQL。
2. 将 Fashion 文件夹放到新电脑的 XAMPP `htdocs`。
3. 在 phpMyAdmin 新建数据库 `forme`，选择 `utf8mb4_unicode_ci`。
4. 进入该空数据库，点击 **Import**，选择 `forme-backup.sql`。
5. 如果数据库里已有需要保留的内容，先备份，不要盲目覆盖。可以改用新数据库名，并更新 `config.local.php`。
6. 检查本地数据库连接配置，尤其端口和密码。
7. 确认项目有 `.installed` 文件；如果复制时遗漏，在项目文件夹新建名为 `.installed` 的空文件即可。Windows 可在 VS Code 的 Explorer 中右键 → New File 创建。
8. 打开 `http://localhost/Fashion/`，使用原来的账户密码登录。

**恢复旧资料时不要再跑安装器创建示例店铺。** 账户、商品和订单已经在 SQL 备份里。

## C. 用 GitHub 与小组合作

GitHub Desktop 可管理源码。先将代码放入你自己的 Fashion checkout，再查看 Changes、填写修改说明、Commit，并按你们的协作流程 Push。

当前提供的下载包不代表已经上传到 GitHub。不要把数据库连接密码、顾客资料或私人的 SQL 备份放到公开仓库。

`.gitignore` 会忽略 `config.local.php`、`.installed` 和上传图片，所以另一位组员从 GitHub 下载代码后仍需初始化自己的数据库；想完全同步演示资料，就使用 B 的迁移流程。

## D. 常见问题

| 现象 | 先检查 |
|---|---|
| localhost 无法连接 | Apache 是否运行、地址和端口是否正确 |
| Cannot connect to database | MySQL 是否运行，config.local.php 的端口／密码是否正确 |
| 看到 PHP 原始代码 | 是否使用 Apache 的地址打开，而不是双击文件 |
| 图片不见了 | 是否完整复制 assets 和 uploads，数据库是否来自同一次备份 |
| 文件上传失败 | 文件是否超过 5 MB；PHP 的 upload_max_filesize、post_max_size 是否足够 |
| 安装后又回到 setup | `.installed` 是否存在、项目目录是否可写 |
| 账户登录不了 | 是否导入了正确的数据库备份；搬迁后账户密码不会自动重设 |
| 分类不能删除 | 里面还有商品，应先移动或删除相关商品 |
| 顾客不能删除 | 存在订单历史，系统会保护这些记录 |

先读错误信息、查看 XAMPP 的 Apache/PHP/MySQL 日志，再进行修改。遇到报错可以提供报错文字，切勿提供密码。
