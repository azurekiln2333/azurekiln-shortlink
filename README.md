# azurekiln-shortlink
简单防红短链接，短链接生成，短链接加密，短链接二维码，短链接API，短链接自定义后缀

二维码API，QQ内部自动替换至浏览器打开，自定义广告

基于Shortny v2.0.1改版

### 资源站拿去发布的请留个链多谢！

### 演示站：https://sssn.top/

<img src="http://img.atusu.cn/images/2020/08/01/QQ20200801121812.png" alt="QQ20200801121812.png" border="0">

<img src="http://img.atusu.cn/images/2020/08/01/QQ20200801121826.png" alt="QQ20200801121826.png" border="0">



### 前端：
简洁、优雅、反应灵敏的设计

创建URL

创建自定义短链URL后缀

密码保护的链接

链接统计

QQ内自动跳转至浏览器,微信跳转至浏览器（默认不带需要本功能需在 https://sssn.top/WoEsz 下载）

支持微信跳转演示站：http://wx.itusu.cn/

2020/08/01更新，已支持QQ微信同时跳转，本程序不放出，需到上方链接获取，如不会搭建的可联系为您搭建

<img src="http://img.atusu.cn/images/2020/08/10/QQ20200810183240.png" alt="QQ20200810183240.png" border="0" />

小书签

复制和共享链接

### 后端：
删除网址

编辑网站设置

添加或编辑广告

分析

使用自定义CSS

### 无需伪静态

页面直接使用 `about.php`、`api-about.php`、`contact.php`、`tos.php` 等真实文件路径。生成的短链使用 `https://你的域名/link.php?id=短码`，包括 API 返回、二维码、用户/后台列表和密码提交地址，均不依赖 URL 重写。

### 安装
环境要求：PHP（启用 mysqli、cURL、GD 扩展）、MySQL、Nginx/Apache 的普通 PHP 站点配置。

将项目上传到支持 PHP 的站点目录，在根目录 `config.php` 填写数据库和 OAuth2 参数，然后打开安装页面：

### 安装路径，记得修改下面域名地址

https://xxx/install/index.html

安装页面使用与 `config.php` 相同的数据库信息。安装器只负责初始化或升级数据表，不会自动修改配置文件；安装完成后必须删除 `install` 文件夹。生产环境默认不启用管理员密码登录。

### 统一配置

部署参数都在根目录 `config.php`：数据库、调试开关、OAuth2 客户端和端点、两个回调地址、管理员白名单、旧密码登录开关、API 跨域白名单，以及访问统计开关、盐值和保留天数。编辑对应数组值即可，不必设置 PHP-FPM 或容器环境变量。已有环境变量仍可使用，优先级为 **环境变量 > config.php**，已设置的空值也会覆盖文件值。

数据库配置示例：

```php
'SHORTNY_DB_HOST' => '127.0.0.1',
'SHORTNY_DB_USER' => 'your-db-user',
'SHORTNY_DB_PASS' => 'your-db-password',
'SHORTNY_DB_NAME' => 'your-db-name',
```

站点名称、Logo、广告和自定义 CSS 等后台可编辑设置仍保存在数据库。实际数据库密码和 OAuth2 secret 只保存在部署环境的 `config.php` 或环境变量中，不要提交或公开包含真实凭据的配置文件。

## 注意

安装后可以在管理面板设置站点名称、Logo 和默认显示地址。运行时短链会使用当前访问请求的域名，不会强制跳回设置中的旧域名。

后台路径：https://xxx/admin/index.php

### 域名和旧短链

短链只保存短码，不绑定创建时使用的域名。多个域名或子域名指向同一套站点目录后，历史短码也可直接通过 `link.php?id=xxxxx` 访问，不需要重新生成。部署在子目录时，生成地址会自动包含该目录。生产反向代理应只接收你实际拥有的域名，并正确传递 `Host`/`X-Forwarded-Proto`。

**只有需要兼容以前已分享的 `/xxxxx` 地址时才需要伪静态。** Apache 项目自带的 `.htaccess` 保留了旧地址的兼容规则，并在未加载重写模块时跳过。Nginx 可继续保留旧部署的规则；下面仅为根目录部署的可选兼容示例，新部署无需添加：

```nginx
location / {
    try_files $uri $uri/ @shortny_link;
}

location @shortny_link {
    rewrite ^/([^/.]+)/?$ /link.php?id=$1 last;
}
```

### OAuth2 管理员登录

管理员登录页支持当前 `azurekiln-oauth2` 的 OAuth2/OIDC 授权码 + PKCE（S256）。在 OAuth2 管理端注册一个启用 `openid`、`profile`、`email` scope 的 confidential client，然后编辑 `config.php` 中以下值：

```php
'OAUTH2_ISSUER' => 'https://sso.azurekiln.cn',
'OAUTH2_CLIENT_ID' => 'your-client-id',
'OAUTH2_CLIENT_SECRET' => 'your-client-secret',
'OAUTH2_REDIRECT_URI' => 'https://link.example.com/admin/login.php?op=oauth2_callback',
'OAUTH2_USER_REDIRECT_URI' => 'https://link.example.com/account.php?op=oauth2_callback',
'OAUTH2_SCOPES' => 'openid profile email',
'OAUTH2_ADMIN_EMAILS' => 'admin@example.com',
// 或使用稳定的 OAuth2 subject：
'OAUTH2_ADMIN_SUBJECTS' => 'provider-user-subject',
```

OAuth2 客户端的允许回调地址必须逐字注册并同时设置 `OAUTH2_REDIRECT_URI` 和 `OAUTH2_USER_REDIRECT_URI`，不能依赖当前请求域名自动拼接回调。当前 provider 的 discovery 地址应声明以下端点：`/oauth2/authorize`、`/oauth2/token` 和 `/oauth2/userinfo`；短链侧会自动读取 `/.well-known/openid-configuration`，并发送 `response_type=code`、`code_challenge_method=S256` 和 `state`。身份以 PKCE 交换后的 userinfo 响应为准。

普通用户也通过同一个 OAuth2 客户端登录。首次登录会在本站创建待审核账号，管理员在“用户审核”页批准后才能生成短链；本站使用 OAuth2 返回的 `sub` 作为唯一用户 ID，不复制 OAuth2 管理端的密码。用户回调地址使用显式配置的 `OAUTH2_USER_REDIRECT_URI`。

如果部署环境不能访问 discovery，可将 `OAUTH2_ISSUER` 留空，改为在 `config.php` 直接填写 `OAUTH2_AUTHORIZATION_ENDPOINT`、`OAUTH2_TOKEN_ENDPOINT` 和 `OAUTH2_USERINFO_ENDPOINT`。当前 `azurekiln-oauth2` 的 production 配置要求 token 请求提供 `OAUTH2_CLIENT_SECRET`，因此必须填写该 secret，并只将其保存在服务器端。

管理员白名单必须设置 `OAUTH2_ADMIN_EMAILS` 或 `OAUTH2_ADMIN_SUBJECTS`，多个值用英文逗号分隔。短链域名可以有多个，但 OAuth2 回调建议固定使用一个后台域名，例如 `Link.azurekiln.cn`。

生产环境默认禁用旧的管理员密码登录；只有在 `config.php` 将 `ALLOW_LEGACY_ADMIN_LOGIN` 设置为 `'1'` 才会显示密码回退。API 不再允许匿名创建，单次批量最多 50 条，跨域来源必须通过 `SHORTNY_API_ORIGINS` 以逗号分隔加入白名单。

## 用户短链与生产配置

通过审核的 OAuth2 用户可以在“我的短链”页面编辑目标地址、暂停/恢复短链、设置过期时间和最大成功点击数，并查看来源、设备、浏览器、系统和最近 30 天趋势。历史链接的 `owner_user_id` 为空不影响访问，也不会绑定创建域名。

API Key 只保存 SHA-256 哈希，完整密钥只在创建成功的页面显示一次。API 使用 `Authorization: Bearer <key>`，浏览器会话 API 仍然需要 CSRF token。管理员可以在后台维护目标域名黑名单和查看审计日志。

生产环境建议在 `config.php` 设置：

```php
'SHORTNY_ANALYTICS_ENABLED' => '1',
'SHORTNY_ANALYTICS_SALT' => 'replace-with-a-long-random-secret',
'SHORTNY_API_ORIGINS' => 'https://your-frontend.example.com',
'SHORTNY_ANALYTICS_RETENTION_DAYS' => '180',
```

`SHORTNY_ANALYTICS_SALT` 未设置时不会保存可用于识别来源 IP 的哈希；上线前请先备份数据库，再通过安装页选择“升级”执行 `install/shortny-upgrade.sql`。升级脚本用于一次性升级旧库，重复执行前请先确认新增列和索引是否已经存在。运行时也会尝试增量创建新表和字段，但数据库账号需要具备 `ALTER`、`CREATE` 和 `information_schema` 查询权限。

数据库连接从 `config.php` 的 `SHORTNY_DB_HOST`、`SHORTNY_DB_USER`、`SHORTNY_DB_PASS`、`SHORTNY_DB_NAME` 读取，也兼容同名环境变量。更新已有部署时保持原数据库连接信息即可。

扫码关注微信：

<img src="http://img.atusu.cn/images/2020/08/01/mmqrcode1595591197140.jpg" alt="mmqrcode1595591197140.jpg" border="0" />



您的支持是我最大的动力，欢迎打赏！ 请使用微信打赏哟！！！

<a href="http://img.atusu.cn/image/JKfu"><img src="http://img.atusu.cn/images/2020/08/01/QQ20200801121207.md.png" alt="QQ20200801121207.png" border="0" /></a>
