# Fashion 修改记录

本文件持续记录每次修改。日期使用马来西亚时间；同一天按批次排序。后续修改应继续追加记录，说明改了什么、涉及文件、如何验证及尚未完成的事项。

## 当前部署状态

- 开发源码：`C:\Users\lemet\Desktop\Mini Project\Fashion`。
- 下述第 1–7 阶段修改写入桌面源码后，由用户复制到 XAMPP。
- XAMPP 当前网站只修复了缺失的 `.installed` 安装标记；没有自动覆盖其业务代码或升级其现有数据库。
- 复制新版时保留 XAMPP 原有的 `.installed`、`config.local.php` 和 `uploads`。
- 旧数据库需要在部署目录执行 `database/migrations/20261120_features.php`；不要重新安装或导入全新 schema 覆盖已有资料。
- 未提供真实 AI API Key，因此尚未验证真实模型服务；未发生付费模型调用。

## 2026-11-20｜批次 1：Promotion 数据库准备

### 修改

- 新增 promotions 表：创建管理员、唯一优惠码、百分比折扣、最低消费、开始／结束时间、启用状态、时间戳。
- orders 新增 promotion_id、promo_code_used、discount_amount。
- 删除优惠时只清空订单的可选关联，保留代码、折扣及历史金额。
- 更新新安装 schema，并提供可重复执行的 CLI 升级脚本。

### 文件

- `database/schema.sql`
- `database/migrations/20261120_promotions.php`
- `docs/PROMOTION-UPGRADE.md`

### 验证／范围

- PHP 语法检查通过。
- 此批次未修改现有数据库，也尚未提供后台／checkout 优惠功能。

## 2026-11-20｜批次 2：Promotion 后台

### 修改

- 后台新增 Promotions 菜单和新增、查看、编辑、删除页面。
- 支持按优惠码及 Active／Scheduled／Expired／Disabled 状态搜索。
- 统一保存大写优惠码，验证唯一性、金额精度、折扣范围及日期顺序。
- 沿用管理员权限及 CSRF 验证。
- 数据库尚未升级时显示明确提示。
- CLI 迁移独立读取数据库配置，不依赖网页 session。

### 文件

- `app/admin.php`、`app/admin-actions.php`
- `app/promotions.php`
- `app/admin-promotions.php`、`app/admin-promotions-actions.php`
- `database/migrations/20261120_promotions.php`
- `tests/promotions.php`、`docs/PROMOTION-UPGRADE.md`

### 验证

- 32 项规则检查、14 项 HTTP 检查通过。
- 独立 MariaDB 验证全新 schema、重复升级、旧订单金额及优惠删除后的订单快照。
- 此时尚未接入 checkout；后续批次完成。

## 2026-11-20｜批次 3：完成第 1–7 阶段业务代码

### 1. 优惠与订单金额

- checkout 支持应用和移除优惠码。
- 后端重新验证优惠启用状态、有效期及最低商品小计。
- 使用整数分计算及四舍五入，折扣不作用于运费。
- 订单保存优惠码和折扣金额快照。
- 运费规则保持 RM8；折扣前商品小计满 RM200 免运。

### 2. 购物车与结账

- 新增持久化勾选、全选及所选商品摘要。
- JavaScript 勾选后提交更新；无 JavaScript 时可用 Update selection。
- 只验证、购买、扣库存和移除已勾选商品，未勾选项目保留。
- 价格、数量、选择或折扣发生变化时要求重新确认。
- 购物车移除增加确认。
- 保留事务、重复结账保护和模拟付款失败处理。

### 3. 顾客与地址

- 个人资料增加电话号码。
- 地址增加第二行、州属、国家及默认地址。
- checkout 可以选择自己的 saved address，或输入新收货地址。
- 验证地址归属，并保留独立订单地址快照。
- 后台支持顾客搜索、资料、订单历史及地址查看。
- 增加账户停用；停用同时阻止已有登录 session 继续使用受保护功能。
- 有订单的顾客继续受到删除保护。

### 4. 产品与库存

- variant 新增 minimum_stock_level 和 active。
- 低库存提醒改用各 variant 自己的门槛。
- 停用 variant 不可购买，也不作为可选 variant 展示。
- 后台新增商品名称搜索。
- 修正颜色与尺码筛选必须匹配同一个有库存 variant。
- 前后台共用尺码选项，补齐 XXL、9–10Y 等。
- 产品增加 style／occasion 标签，供推荐使用。
- 有购买历史的产品／variant 阻止永久删除，改用停用／下架。

### 5. 评论与评分

- 产品页显示公开评论平均分与数量。
- 修改自己的评论时预填内容及评分。
- 后台支持评论搜索、隐藏／恢复及审核管理员／时间。
- 隐藏及顾客已删除的评论不计入公开平均分。
- 删除采用 customer_deleted 内部标记，保留审核状态；删除后重发不能绕过隐藏审核。

### 6. Fashion Recommendation Assistant

- 导航和账户页新增独立聊天入口，不要求购物车有商品。
- session 保存有限长度对话及偏好，支持英文／中文常见条件和后续修改。
- 根据已勾选购物车商品自动推荐互补类别，改变选择时更新，全部取消时隐藏。
- 只展示发布中、variant 启用且有库存的商品；结果附理由和商品链接。
- 增加服务端模型接口配置、JSON 响应验证、商品 ID 白名单、结果刷新及失败降级。
- 未配置模型时明确显示 Catalogue rules；配置后可使用 AI-assisted。
- 购物车 AI 解释按选择／目录缓存十分钟，可单独关闭。
- API Key 只放本地服务端配置，不进入前端；新增 Git 忽略及 Apache 访问保护。
- 预算在当前界面明确为每件商品预算，不宣称是整套穿搭预算。

### 7. 订单管理

- 后台支持订单参考号／顾客搜索与状态筛选。
- 每张订单记录一笔成功模拟付款，包括金额、参考号和时间。
- 记录状态操作人、最后更新管理员及状态历史。
- 仅 Confirmed／Processing 可取消；库存只恢复一次。
- 仅已取消示范订单可删除；删除不再次恢复库存。
- 升级旧订单时按原有总额补付款记录，不重新计算历史金额。

### 主要文件

- 公共规则：`app/feature-rules.php`、`app/feature-services.php`
- 业务动作：`app/actions.php`、`app/feature-actions.php`、`app/admin-actions.php`、`app/admin-feature-actions.php`
- 页面：`app/shop-checkout.php`、`app/account-profile.php`、`app/address-fields.php`
- 后台：`app/admin-customers.php`、`app/admin-orders.php`、`app/admin-reviews.php`、`app/admin.php`
- 产品及评分：`app/catalog.php`、`app/product-reviews.php`、`app/shop.php`
- 推荐：`app/recommendation-rules.php`、`app/recommendations.php`、`app/assistant.php`、`ai.example.php`
- 入口及共享代码：`index.php`、`app/bootstrap.php`
- 前端：`assets/app.js`、`assets/style.css`
- 数据库：`database/schema.sql`、`database/feature-schema.php`、`database/feature-tables.sql`、`database/migrations/20261120_features.php`
- 验证：`tests/features.php`、`tests/recommendation-service.php`
- 说明：`README.md`、`docs/FEATURES-UPGRADE.md`、`docs/AI-SETUP.md`、`docs/FEATURES-TESTING.md`
- 配置保护：`.gitignore`、`.htaccess`

### 验证结果

- 32 项优惠规则、39 项通用规则、13 项推荐服务合同检查通过。
- 干净测试数据库完整重跑：79 项 HTTP／整合检查通过。
- 16 项桌面／手机浏览器检查通过，无 JavaScript 错误；已检查购物车和库存页面截图。
- 独立数据库验证旧版升级、重复升级、全新 schema、订单及付款记录。
- 复现并修复隐藏评论删除后重发绕过审核的问题，回归检查通过，代码复核确认修复。
- 配置服务连接失败时明确降级，测试确认密钥不会出现在返回页面。
- 没有调用真实付费模型。以上开发检查不能替代团队正式 UAT 和个人贡献证据。

## 2026-11-20｜批次 4：修复 XAMPP Setup 卡住

### 问题

网页反复显示 `An administrator already exists. Setup cannot create another.`。

### 原因及修改

- 只读检查确认 `forme` 已存在 1 位管理员、2 个账户、8 个产品、72 个 variants，订单为 0。
- `C:\xampp\htdocs\Fashion\.installed` 缺失，入口误跳转 Setup。
- 经目录写入授权，只补回该安装标记；没有重建账号、修改密码、删除产品或重置数据库。

### 验证

- `http://localhost/Fashion/index.php?page=login` 返回 HTTP 200，存在登录表单且不含 Setup 错误。
- 数据库上述记录数量保持不变。

## 后续事项

- 将修改后的桌面源码复制到 XAMPP，并执行增量数据库升级。
- 准备实际商品照片、准确标签和库存资料。
- 配置真实模型服务后再验证 AI-assisted 模式。
- 按实际实现同步 ERD、UML 和 data dictionary：账户沿用 users＋role；购物车沿用 cart_items.user_id；付款为每订单一笔成功模拟付款；新增状态历史和评论删除标记。
- 第 8 阶段品牌／Logo／图片视觉统一不在本次第 1–7 阶段范围内。

## 后续追加格式

```text
## YYYY-MM-DD｜批次 N：标题
### 修改原因
### 修改内容
### 涉及文件
### 验证结果
### 部署状态／待办
```

## 2026-11-20｜批次 5：修复部署后重复进入 Setup

### 原因及修改
- 当前工作项目已切换至 C:\xampp\htdocs\Fashion。
- 只读检查确认数据库 features_version 为 20261120，Promotion、Payment 及订单状态历史表均存在，升级已完成。
- 部署目录缺少 .installed，导致 index.php 在连接数据库前就跳转 Setup。
- 补回 .installed 安装标记；不重新执行安装、不创建管理员、不修改密码或数据库资料。
- 后续复制文件时保留此标记、config.local.php、ai.local.php 及 uploads。

### 验证
- 修复后检查登录页面 HTTP 状态、登录表单及 Setup 跳转。
- AI 配置本次不修改；密钥内容不记录到日志。

## 2026-11-20｜批次 6：修复登录后数据库字段缺失及升级误判

### 原因
- MySQL 连接实际正常；登录后的查询遇到 users.phone、users.active 等共 24 个缺失字段。
- 旧 Setup 在已有数据库执行 CREATE TABLE IF NOT EXISTS 并写入 features_version，但该语句不会升级旧表，所以版本号不能单独证明升级完整。批次 5 的版本号检查不足，本批次纠正。

### 修复
- 在应用工作目录外保存完整本地 SQL 备份，再执行现有增量迁移，补齐字段并保留账号和已有数据。
- index.php 改为同时检查版本及实际必要字段／表，避免假升级状态进入登录查询。
- setup.php 在运行全新 schema 前检查已有管理员，阻止对已有商店重新安装并误写版本。
- 数据加载错误不再一律描述为 MySQL 无法连接。
- 新增 tests/schema-readiness.php，覆盖完整 schema、缺少 phone、缺少 Payment 表三种情况。

### 文件及验证
- 修改 index.php、setup.php、app/feature-services.php；新增 tests/schema-readiness.php。
- 增量升级后缺失字段为 0；原管理员保留。
- 三项 schema 回归检查及修改文件 PHP 语法检查通过；随后验证实际首页、登录及登录资料查询。
- API Key、AI 配置、密码和商品资料没有在本次修复中更改。


## 2026-11-20｜批次 7：右下角悬浮聊天窗口

### 修改内容
- Chatbot 统一为前台页面右下角的浮动按钮，点击展开窗口；后台管理页不显示。
- 移除导航栏和账户页的独立聊天页面入口；旧 page=assistant 地址转到首页并展开悬浮窗口。
- 发送、继续修改偏好、重置均通过异步接口完成，当前页面不刷新或跳转。
- 关闭／重新打开保留会话；进入推荐商品页面后继续显示窗口与对话。
- 增加输入长度验证、发送中状态、短暂发送间隔、重复请求去重、错误提示、网络失败后保留输入和刷新重试。
- 未登录时在窗口内显示登录入口；服务端保留登录、CSRF 和已停用账户检查。
- 商品卡片显示实时可用款式、价格及链接；文本继续经过转义，不执行用户或模型提供的 HTML。
- 手机宽高、可视窗口和键盘空间适配；Escape 关闭并把焦点返回按钮。
- 重置只清除会话内容，不清除发送间隔，防止重置后重复请求绕过限制。

### 文件
- 修改 index.php、app/account-profile.php、app/feature-actions.php、app/assistant.php。
- 新增 app/assistant-service.php、app/assistant-api.php、app/assistant-content.php、app/assistant-widget.php。
- 新增 assets/chatbot.css、assets/chatbot.js、tests/assistant-service.php。
- 更新 docs/AI-SETUP.md；新增 docs/FLOATING-CHAT.md。

### 验证及部署
- 7 项会话服务检查、17 项 HTTP 接口检查通过。
- 26 项桌面／手机浏览器检查通过：发送不跳页、实际商品结果、窗口尺寸、重开保留、商品跳转、Escape 焦点、断网保留输入及恢复；无 JS 错误。
- 在独立测试数据库和测试账号验证，不修改实际客户资料或数据库。
- 实际模型仍由 ai.local.php 控制；未配置或失败时明确采用规则推荐。本批次没有读取／改写密钥，也未调用付费模型。
- 写入当前 C:/xampp/htdocs/Fashion；不修改数据库结构、Git 分支或提交。
