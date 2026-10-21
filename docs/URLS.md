# 类目短网址与迁移

1.2.0 起，所有产品类目使用根路径，一级、二级及更深层级采用同一规则：

| 内容 | 规范地址 |
| --- | --- |
| 设备总览 | `/products/` |
| 产品类目 | `/construction-digger/`、`/small-digger/` 等 `/{类目内容页-slug}/` |
| 类目产品分页 | `/construction-digger/page/2/`（确实有第二页时） |
| 产品详情 | `/products/{产品-slug}/`，保持原址 |
| 行业总览与详情 | `/industries/`、`/industries/{slug}/`，保持原址 |
| 文章列表与文章 | `/blog/`、`/{文章-slug}/`，保持原址 |
| 企业、服务及联系页面 | 原生 Page 的现有地址，保持原址 |
| CMS、REST、媒体 | 原 WordPress 地址，保持原址 |

分类树仍由 taxonomy 管理，类目内容、模板、正文、产品查询及媒体仍属于原来的 CPT 和记录 ID。移动分类不会把父级拼到网址，不会替换内容页或切换模板。面包屑、设备菜单和父类卡片继续表达真实分类层级。

## 旧链接与重名

原来的15个 `/product-category/{slug}/` 地址直接以服务器端301转到对应新地址；有效分页保留页码，正常筛选、UTM查询也保留。不经过首页，不制造两份公开正文。日后原生修改已发布内容页slug，旧根路径与旧前缀地址直接转到最新地址。

不存在、未发布、私密、回收站及越界分页不公开内容。WordPress旧slug跳转和模糊猜测不会把撤回类目的旧址转到另一个相似页面。

原生permalink、taxonomy链接、菜单、面包屑、筛选、分页和Rank Math sitemap使用同一组新地址。实现生成精确WordPress rewrite rules，也能由 `url_to_postid()` 识别；没有拦截整个根目录的通配规则。发布、撤回、改slug或删除类目时更新规则，普通浏览不每次刷新。

根类目与根Page、根文章、附件、系统路径、业务archive及类目历史别名共享命名空间。新增内容遇到占用时增加后缀，例如 `about-2`；`wp-` 系统前缀改用 `equipment-wp-…` 可用名称。已有页面不会被新类目抢走，新Page/文章也不能抢走已有类目。嵌套Page叶子名和 `/products/…` 产品slug仍按各自路径处理。发布前确认原生编辑器的实际Permalink。

## 更新旧副本

先备份 `.runtime/`，正常停止原进程，更新源码后在原文件夹启动。入口先检查既有根路径冲突；冲突时停止并列出需处理的类目，保留原拥有者，不静默覆盖或批量改名。

一次性迁移只更新本站已有类目引用的正文、摘要和受支持内容元数据，正确处理PHP序列化与JSON，保留查询和片段。初始演示当前只需改4条元数据：1个自定义菜单链接、3个设备任务卡片链接。外站URL和相似但不同的slug保留；GUID、历史修订、历史询盘及归因、邮箱和初始种子不改。不重导数据库，后续启动不覆盖用户新编辑。

新副本也先导入完整内容，再明确迁移。`site/content.json` 保留原快照；验证脚本把其15条类目计划转换为新规范地址，检查全部75条页面。

## 类目编辑与预览

两套完整ACF组共用存储字段。原生模板切换会异步换字段组，因此有未保存字段时，模板保持原选项，并在旁边提示先保存。先Save Draft / Update，等成功后再切换；不会自动丢弃内容或替你发布。普通Page、产品等编辑器不受本类目脚本影响。

原生预览从当前草稿/自动保存读取可变正文、图片与参数；分类身份从原内容页读取。自动保存未包含分类选择器，也不能因此丢掉产品范围和正文。分类关联仍受一对一和权限保护，不开放随意改绑定。

## SEO依据与验证

Google建议网址简单、可读、符合内容逻辑，没有“去掉一层就提高排名”的保证。本次根路径是本站信息架构和用户选择；其他网站可保留有意义的目录。[Google URL建议](https://developers.google.com/search/docs/crawling-indexing/url-structure)

迁移需要旧新映射、永久跳转、站内链接、canonical和sitemap共同一致，不能把大量旧页转到无关首页。[Google URL迁移说明](https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes)

本地演示继续强制noindex，Rank Math会省略普通页面canonical。测试只在自己的隔离loopback库放临时输出fixture，检查可索引状态的canonical和实际XML后移除；不开放用户站点，不代表Google抓取/收录。

```sh
npm run test:urls
npm run test:categories
npm test
npm run lint
npm run audit:package
npm run verify
```

两个集成检查各自建独立运行库、保留结果，不覆盖用户 `.runtime/`。后台创建、模板保存、正文/图片、改上级与未保存预览另有原生浏览器证据，见[交付验证](VERIFICATION.md)。未覆盖Windows/Linux实机、长期并发、子目录和完整多语言组合。
