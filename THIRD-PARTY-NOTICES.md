# 第三方组件

本项目不包含 WordPress Build Skill / Harness 的源代码或安装配置。网站自身的业务插件保留了既有 PHP 命名空间与模块名；这些是站点表单、字段和附件功能的一部分。

| 组件 | 固定版本 | 来源 / 许可 |
| --- | --- | --- |
| WordPress | 6.9.10 | wordpress.org，GPL v2 or later；包内保留 license.txt |
| WordPress Playground / PHP.wasm | 3.1.52 | WordPress/wordpress-playground；npm 包内保留许可证 |
| SQLite Database Integration | 随固定 Playground 版本的集成包 | WordPress SQLite 集成，包内保留许可证 |
| Advanced Custom Fields PRO | 6.8.9 | 已安装站点使用的 GPL v2 or later 代码；保留原版权和许可，不含授权密钥 |
| Classic Editor | 1.7.0 | WordPress 插件，GPL v2 or later |
| Fluent Forms | 6.2.14 | WordPress 插件，GPL v2 or later |
| Rank Math SEO | 1.0.279 | WordPress 插件，GPL v2 or later |
| GTranslate | 5.0.1 | 原站免费语言切换插件，保留其 GPL 代码和原有配置；不附带付费服务账号 |
| fflate | 0.8.3 | MIT，npm 包内保留许可证 |
| Anton / Inter 字体 | 随主题本地字体 | SIL Open Font License，见 `site/theme/assets/fonts/` |

插件包由原本地站点依赖固定打包，SHA-256 见 `packages/manifest.json`。第三方代码不代表获得其商标、官方支持、更新服务或商业账号。ACF 的编辑体验和授权范围参见官方 [License Activations](https://www.advancedcustomfields.com/resources/license-activations/)。

图片为原演示站使用的 AI 概念媒体与虚构示例作者图，供本地演示，不是企业证明材料。站点仍需真实品牌和产品资料才能作为真实商业站发布。
