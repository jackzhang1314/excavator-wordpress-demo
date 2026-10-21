# 给 Codex 的项目说明

## 用户拿到链接后的默认任务

这是完整、已填好内容的本地 WordPress 演示，不是空白主题。用户要求体验时，请完成下载、启动和打开页面，不要停在解释命令。

1. 阅读 README.md。检查当前系统及这个目录的已有改动；已有 `.runtime/` 时保留它。
   若 `.runtime/running.json` 指向一个健康的运行中站点，直接复用；不要再开第二个进程。确需重启时，只停止这个副本已确认的进程。
   如果电脑没有 Git，下载 `https://github.com/jackzhang1314/excavator-wordpress-demo/archive/HEAD.zip` 并解压到新文件夹，再进入包含 package.json 的目录；无需先配置 GitHub 账号或安装 Git。
2. macOS/Linux 运行 `bash start.sh`；Windows 运行 `powershell -NoProfile -ExecutionPolicy Bypass -File .\start.ps1`。入口会在需要时下载项目内 Node.js，并安装固定依赖。无需让用户先安装 PHP、MySQL、Docker 或另建 WordPress。
3. 保持进程运行，等终端输出 Website 和 CMS 地址。默认端口 9400；占用时使用 `--port 9401`，不要终止其他项目进程。
4. 打开前台和 `/wp-admin/`，告诉用户公开演示账号 `demo_admin` / `ExcavatorDemo2026!`。可以使用当前可用的浏览器工具，无固定浏览器依赖。
5. 实际确认首页、一个产品类目、一个产品详情和后台可打开。不要把“依赖安装成功”当成网站启动成功。

## 开发与数据归属

- 页面源代码：`site/theme/`；产品模型、ACF 定义、原有表单与附件模块：`site/plugin/`。它们是网站源码，不需要外部 Harness。
- `site/content.json` 是预填站点快照，`site/uploads/` 是其媒体。首次启动导入；重复启动保留本机数据库。
- `.runtime/wordpress/` 是当前副本的真实 WordPress 目录。后台新增内容和媒体只在 `.runtime/`，不能用快照或全量重导覆盖。
- 样式和插件源目录直接挂载到运行环境，改源码后刷新即可；涉及注册、初始化或启动脚本的改动按影响重启验证。
- 密码为专用演示密码。不得索取原站、主机或企业邮箱密钥来启动本地站。
- 本地询盘存库，发信在 `site/mu-plugins/local-demo.php` 拦截；原企业邮箱、主机及分析 / 聊天账号未携带。正常的 WordPress 插件管理、媒体和原语言控件仍可联网。不要因看到邮件被拦截就连接真实邮箱或“修复”为外发。
- 产品 `product_details_body` 和类目 `category_long_description` 的完整富文本、媒体工具、保存与清空能力必须保留。
- 类目结构由 `product_collection` 唯一维护，内容页为 `starter_category`，全状态一对一。统一入口 Products → Product Categories；新建类目内容使用 `category_ensure_page` 或受保护的 category REST 入口，不直接创建重复 CPT。原生 Template 独立于层级，完整说明见 `docs/CATEGORIES.md`。
- 类目使用根路径，旧前缀保留精确301。保留原生rewrite、url_to_postid、跨根内容的slug保护与旧别名；不要仅改post_type_link或增加吞掉根Page的通配规则。`docs/URLS.md`说明显式升级和验收；不对运行库重导种子或开放索引。
- 不自动购买主机、初始化其他网站、公开端口、开启索引或部署公网。若用户之后另行要求部署，再准备新账号与真实服务配置。
- 不删除或重建 `.runtime/` 来解决普通问题。恢复初始演示前必须说明它会替换本机后续内容；先停机并重命名备份原目录。

## 验证

运行 `npm test`、`npm run lint` 与 `npm run audit:package`；类目业务变化另运行 `npm run test:categories`（独立临时库）。站点启动后运行 `npm run verify -- --url http://127.0.0.1:9400`。后端字段或模板变化要实际保存、重开并查看相应前台。需要样式检查时按变化补齐适当验证，不宣称未运行的检查通过。

详细说明：`docs/DEVELOPMENT.md`、`docs/DATA.md`、`docs/TROUBLESHOOTING.md`。处理用户的新需求时先确认相关源码和已有内容，保留无关改动。
