# 在本地继续开发

先按 README 启动站点。WordPress Playground 在 Node.js 中运行真实 PHP 和 WordPress；CMS 数据使用 SQLite。前台由 PHP 自定义主题渲染，后台是 WordPress 原生编辑流程。

| 目录 | 用途 |
| --- | --- |
| `site/theme/` | 页面模板、布局、CSS、JS、本地字体 |
| `site/plugin/` | 产品 / 类目 / 行业内容模型、ACF 字段、询盘及私有附件 |
| `site/mu-plugins/local-demo.php` | 演示发信拦截、自动升级控制和 SQLite 日期兼容 |
| `site/content.json` | 原本地站点脱敏后的内容与字段快照 |
| `site/uploads/` | 初始媒体，包含 WordPress 的各尺寸图片 |
| `site/bootstrap.php` | 首次导入和专用演示账号初始化 |
| `site/category-layouts.php` | 一次性把旧类目布局保存为原生模板选择，保留后续用户选择 |
| `site/category-url-migration.php` | 预检根路径冲突并只迁移已知类目URL引用，不重导内容 |
| `site/relocate.php` | 换端口时保留序列化 / JSON 数据并改写本机网址 |
| `scripts/` | 独立的 Playground 启动与检查代码 |
| `packages/` | 固定版本 WordPress、SQLite 集成及所需插件包，含 SHA-256 校验清单 |
| `.runtime/` | 当前电脑上的站点数据，Git 忽略 |

## 修改页面和内容

普通内容通过后台修改。产品入口为 Products，产品分类及图文页从 Products → Product Categories 统一管理，行业入口为 Industry；原生 Pages、Posts、Media 和 Appearance → Menus 分别维护页面、文章、媒体与菜单。

类目层级只在 `product_collection` 中维护，内容是全状态一对一的 `starter_category` CPT；不要复制一棵 `post_parent` 树。原生 `_wp_page_template` 选择概览或选型，各自匹配完整 ACF 字段组，与 taxonomy 层级无关。开发入口、身份保护、创建失败续接和完整 Description 见 [类目管理](CATEGORIES.md)。

页面模板在 `site/theme/page-templates/`；产品与类目的渲染同时依赖主题根模板和 `inc/` 中的组件。字段定义由 `site/plugin/` 注册，保留既有 field key、name 和模板绑定，先找实际调用再改。不要用静态占位文字替换已保存的 CMS 值。

源码目录直接挂载，CSS、JS、PHP 模板改动通常刷新即可。修改运行入口、包或初始化代码后要停止并重新启动。已启动副本不会因修改 `site/content.json` 自动覆盖数据库，这是为了保护你的后台修改。

## 保存修改与协作

Git 可提交主题、业务插件、运行脚本和文档。后台修改、用户上传、测试询盘、登录状态属于 `.runtime/`，不会自动进入 Git。需要迁移这些数据时停机后备份运行目录；需要把新内容做成公开的下一版演示时，应另行导出内容、媒体并脱敏审核，不能直接提交整份运行目录。

新字段或模板应验证保存 → 重开 → 前台原 URL；原有产品和类目的长正文、图片、关联内容与空值行为应保持。浏览页面与后台编辑都不能依赖付费主机或共享 Harness。

## 检查

```bash
npm test
npm run lint
npm run audit:package
npm run test:categories
npm run test:urls
npm run verify -- --url http://127.0.0.1:9400
```

依赖已经安装后，前三项不需要运行中的网站。`test:categories` 自动新建隔离运行库并停止自己的进程；最后一项需要先启动目标副本。验证脚本检查规划 URL 和页面资源，不能代替审美、所有字段组合或真实设备测试。

ACF 字段定义通过 PHP 随业务插件提供。包内没有 ACF 许可证密钥；编辑已经提供的内容字段不需要原作者账号。新建受官方授权限制的 PRO 字段组或使用官方自动更新时，需要自己的有效许可证。
