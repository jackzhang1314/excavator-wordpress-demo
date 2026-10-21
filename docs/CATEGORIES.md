# 产品类目管理与页面模板

1.1.0 把产品分类结构与落地页样式分开管理。分类树仍是原生 `product_collection` taxonomy，内容仍是 `starter_category` CPT；没有第二棵页面父子树，也不需要 Harness。

## 从一个入口管理

登录后台，进入 **Products → Product Categories**。

列表同时显示完整路径、实际层级、对应内容页及状态、已选模板、直接产品数、含所有下级的去重产品数。支持一级、二级、三级及更深的层级；“Level 3+”视图包含三级及以下。

- 新建一级：点 **Add top-level category**，填写名称与别名，确认 Parent 为 None，再点 **Create category & content draft**。
- 新建子类：在目标行点 **Add subcategory**。上级在初始 HTML 中已经选中，不依赖脚本加载；确认后创建。
- 写内容：点该行 **Edit content**。新建内容页是草稿，编辑、预览并确认后再发布。
- 改结构：点 **Edit structure**。更名、换上级会立即改变分类路径、后代产品范围及面包屑。内容页的 Save Draft 不会撤销结构修改。

创建分类与创建内容页是两个步骤。若第二步失败，原分类保留；从该行的 **Create content draft** 继续。正常重试打开同一页；草稿、待审、定时、私密、发布和回收站状态都占用原关联，不能另建重复页。回收站显示 **Restore content**，恢复原页后再检查其状态与内容。

Products 下原有 **Product Category Pages** 内容列表继续保留，兼容已有编辑链接；新建操作引导回统一入口。

## 页面样式由原生 Template 决定

这是 WordPress 原生 `page_template` 选择器，保存为 `_wp_page_template`，不是另一套 ACF 模板选择字段。右侧选择模板，主编辑区通过 ACF 原生 Location Rules 显示匹配的完整字段组。当前两套已经完成绑定；以后增加模板仍需同时注册模板与对应编辑组，不会仅凭模板名称自动生成字段。

分类结构由 `product_collection` taxonomy 管理；所有类目内容页始终属于同一个 `starter_category` CPT，保留自己的标题、图片、Description、SEO 与修订。模板不创建另一种内容类型，也不建立第二棵上下级树。

打开内容页，在 **Page Template → Template** 原生面板选择：

| 模板 | 用途 | 与层级的关系 |
| --- | --- | --- |
| Category: Overview | 概览、子类导航与设备介绍 | 任意层级可用 |
| Category: Product selection | 产品筛选、选型与详细内容 | 任意层级可用 |

两种模板各绑定一套完整的原生 ACF 编辑字段组，切换时显示匹配的一套；共用的字段名保持一致，已有值保留。先保存当前内容再换模板；1.2.0在有未保存字段时保持原模板并提示保存，防止异步切换丢失输入。默认模板的文件标识是 `default`，选型模板是 `page-templates/category-selection.php`。模板不读取分类有没有父节点来选择布局。

原版本的 Construction Digger 与 Small Digger 曾使用同一套模板，再按层级分支输出不同布局。1.1.0 首次启动把已有默认页面的旧布局保存为明确的 Template：根类保留概览、嵌套类保留选型，包括旧回收站页面。已有非默认模板保留。升级标记写入后，后续启动不会覆盖用户选择；不重新导入初始内容。

改上级不会改页面 ID、页面 slug、已选模板、正文或图片。1.2.0起使用 `/{页面-slug}/`，旧 `/product-category/{页面-slug}/` 301到新址，见[网址规则](URLS.md)。网址没有强制嵌套层级。分类改名不会自动改内容页标题或 slug，请在内容页中自行确认需要的更改。

## 完整 Description 与字段

每个类目保留 `category_long_description` 完整富文本，可用 Visual/Text、标题、段落、表格和媒体；产品仍使用 `product_details_body`。正文不回退到分类结构中的短备注。明确清空正文、图片或可选模块后，旧分类字段不会复活。

参数值 `0` 是有效值。可选模块需要有效内容才显示；移除最后一行并保存后应隐藏。改模板共用同一组数据，不会拷贝、重建或自动发布页面。原生草稿、预览、自动保存与修订仍可使用；修订恢复内容，不恢复 taxonomy 的移动历史。

普通 WordPress 媒体库文件使用公开媒体网址。草稿页面隐藏不等于上传的媒体文件私密；询盘私有附件使用独立保护机制。

## 关联规则与开发接口

`category_collection` 是内容页与分类的身份关联。正常编辑中隐藏已经确定的关联选择器；不能通过 ACF、原生元数据 API 或 Core REST 绑定第二个内容页，也不能任意改成另一个分类。需要重组时改 taxonomy 的 Parent。

需要编程创建时，先创建 taxonomy term，再由已认证且有类目管理及内容编辑权限的请求调用：

```text
POST /wp-json/starter/v1/product-categories/{term_id}/page
```

接口返回同一内容页的 `id`、状态和后台编辑网址，不自动发布。原生 Core REST 仍可更新已有页及创建自动保存；通用 CPT 新建请求会提示使用上述入口。服务层以数据库唯一 option 键与条件释放处理创建占用，并保留失败草稿供重试。冲突应先检查已有内容，不能删库重试。

删除分类会把相关已发布、定时或私密内容转为草稿，保留正文；原网址失效。只有原分类已经不存在、页面未发布且操作者有权限时，才允许显式修复孤立关联。此修复面向开发维护，不是普通页面切换方式。结构父节点不存在、指向其他 taxonomy、自己或后代均拒绝保存。

草稿、回收站、冲突或缺失分类不提供公开类目卡片及导航链接；旧 taxonomy 查询不另输出一份内容。类目内容页继续由 CPT sitemap 提供，taxonomy sitemap 排除，防止两个公开入口。此演示仍禁止正式索引。

## 本地验证

```sh
npm test
npm run lint
npm run audit:package
npm run test:categories
npm run verify
```

`test:categories` 启动一份全新的临时 WordPress 与数据库，测试实际服务、REST 和前台 HTTP，保留结果在 `.test-runtime-categories-*/category-checks.json`；不会使用或覆盖你的 `.runtime/`。它不等于真实浏览器操作。`verify` 检查已经运行的站点，端口不同可使用 `npm run verify -- --url http://127.0.0.1:9401`。

本轮原生浏览器的多层创建、模板切换、媒体上传、富文本保存/预览、修订、移动和回收站实测，以及各版本边界见 [交付验证](VERIFICATION.md)。测试记录留在隔离运行目录，不放进用户第一次启动的演示内容。
