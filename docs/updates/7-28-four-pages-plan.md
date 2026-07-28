# 四页面设计与制作实施计划

> 2026-07-28。状态：**已实施**（组件/路由/JSON/菜单已落地；品类配图暂复用 `public/images/home/*`，后续可换 AI 专图）。
> 目标：让客户提交设计文件 → 我们定制设计 → 印刷交付。
> 参考：用户提供的设计服务指南 PDF（已存 `docs/image-materials/business_card_design_services_guide.pdf`）、moo.com/postcards、/stickers、/marketing-materials。

---

## 0. 总览

| 页面 | 性质 | 内容来源 |
|---|---|---|
| `/business-card-design-service` | **改版**（页面与表单已存在） | 按 PDF 重构：4 档服务方案 + 流程 + 条款 |
| `/postcards` | 新建 | 自由发挥（本计划已定稿文案框架） |
| `/stickers-and-labels` | 新建 | 同上 |
| `/flyers-and-brochures` | 新建 | 同上 |

**共用策略**：3 个品类页用**同一个可配置落地页组件**（`category-landing.tsx`）+ 每页一份 JSON 配置驱动，避免三倍重复代码。

---

## 1. 页面一：business-card-design-service（改版）

现有页面（intro + 6 步流程 + 8 条须知 + 表单）**保留下半部分**，上半部分按 PDF 重构为**服务方案体系**：

### 1.1 页面结构

1. **Hero**：标题 "Business Card Design Services" + 副文案 + 配图（设计师工作场景，生成）
2. **四档服务方案卡**（核心新增，2×2 或 4 列网格，PDF §一 完整翻译）：

| 方案 | 价格* | 适用对象 | 流程要点 | CTA |
|---|---|---|---|---|
| Canva 自助设计 | Free | 熟悉设计软件、想自主掌控样式的客户 | Canva 完成设计 → 导出 PDF/SVG 发给我们 → 直接选纸下单印刷 | Start with Canva |
| 矢量源文件免费排版 | Free（印刷客户） | 已有矢量源文件、仅需改文字的客户 | 提供 AI/EPS/PDF/SVG → 我们改姓名/联系方式 → 确认材质下单 | Submit your file |
| 精选模板排版 | **热门性价比 $5/款** | 想快速出稿、有参考版式的客户 | 选定版式（Pinterest 参考或模板库）→ 套用 LOGO 与信息 → 修改至满意 → 定稿后提供矢量源文件 | Choose a template |
| 专业原创定制设计 | **品牌高端推荐 $10/项** | 追求独特品牌视觉的客户 | 设计师出 2 个不同风格初稿 → 选 1 深化调整 → 定稿后提供高精度矢量源文件 | Start custom design |

> *价格币种待确认（§4 Q1）；PDF 附注"未在我处印刷的客户收取 $9/人排版费"并入对应卡片。

3. **补充服务说明**：LOGO 矢量描摹（按复杂度计费）、非印刷客户买断费说明
4. **6 步设计流程**（保留现有，按 PDF 校对文案：需求问卷→1-3 工作日初稿 PDF→修改 1 工作日/轮→校对定稿→材质算价→生产）
5. **须知与免责条款表**（保留现有，按 PDF 补充"修改范围界定"与"退款规则"两条）
6. **设计服务表单**（保留现有共享组件，页面底部）

### 1.2 配图

Hero 1 张 + 可选 4 档方案小图，用 image_generation 生成（设计师桌面、名片设计稿特写等，红金配色语境）。

---

## 2. 页面二至四：品类落地页（新建 ×3）

### 2.1 统一页面骨架（`category-landing.tsx` + JSON 配置）

| 区块 | 内容 | 说明 |
|---|---|---|
| ① Hero | 产品摄影大图 + 标题 + 一句话副文案 + CTA | 参考 moo 各品类页头 |
| ② 产品系列网格 | 4–6 张卡（图 + 名称 + 特性要点 + "from $X" + 链接） | moo 的 by shape/format/material 结构 |
| ③ **设计服务区块**（转化核心） | 三路径卡：**Upload a full design**（已有完整设计稿）/ **Template layout**（快速套版）/ **Design for you**（专业定制） → 指向 `/business-card-design-service` | 与名片产品页的设计 CTA 一脉相承 |
| ④ 品质承诺条 | 3–4 个图标点（纸张/工艺/打样/配送） | 复用首页 perks 视觉语言 |
| ⑤ FAQ | 3–5 条（尺寸、最小起订量、文件格式、周期、打样） | 我编写 |

### 2.2 各页内容框架（文案我按此撰写，可改）

**Postcards**（`/postcards`）
- Hero："Custom Postcards that get kept, not tossed."（草稿）
- 系列卡：Standard 4"×6" / Square 4.72" / Medium 5"×7" / Large 6"×9" / Rack Cards（4–6 张，配生成图）

**Stickers & Labels**（`/stickers-and-labels`）
- Hero："Make your brand stick."
- 系列卡：Die-Cut / Circle / Rectangle / Square / Vinyl / Matte Paper（按 moo 结构简化）

**Flyers & Brochures**（`/flyers-and-brochures`）
- Hero："Make your ideas fly."
- 系列卡：US Letter Flyers / Half Page / Long / Square / Tri-Fold Brochures / Bi-Fold Brochures
- 可链接现有 2 个产品（flyer-printing-1000、tri-fold-brochure-500）到 `/shop`

### 2.3 技术实现

| # | 项 | 说明 |
|---|---|---|
| 1 | 组件 `resources/js/components/category-landing.tsx` | 读 JSON 配置渲染 §2.1 五区块；复用既有排版语言（眉题/衬线标题/红金） |
| 2 | 页面壳 ×3 | `resources/js/pages/postcards.tsx` 等，仅传配置 key |
| 3 | 路由 | `routes/web.php` 三条 `Route::inertia` |
| 4 | 内容 | `hardcoded-content.json` 新增 `postcards_page` / `stickers_page` / `flyers_page` 三个键 |
| 5 | 布局解析 | `app.tsx` / `ssr.tsx` 的 null-layout case 补三个页面名 |
| 6 | 图片 | 每页 Hero 1 张 + 系列卡 4–6 张，全部 image_generation 生成（约 18 张），存 `public/images/categories/` |
| 7 | 顶部菜单 | 三项链接改为新页面（§4 Q2） |

---

## 3. 验收标准

1. 4 个页面可访问、无破图；移动端不破版
2. 设计服务页四档方案卡完整展示，价格/附注与 §4 Q1 结论一致
3. 品类页设计服务三路径卡均指向 `/business-card-design-service`
4. tsc 零错误、`npm run build` 通过、JSON 合法
5. 菜单新链接生效

## 4. 决策记录（2026-07-28 用户确认）

| 项 | 决策 |
|---|---|
| Q1 价格与语言 | 英文页面 + **美元定价**：精选模板排版 **$5/款**、专业原创定制设计 **$10/项**（按 ¥29/¥69 近似换算；金额存于 JSON，一行可调）。免费两档与 $9/人非印刷客户排版费说明保留 |
| Q2 菜单指向 | 顶部菜单三项改指：**Postcards → `/postcards`**、**Stickers & Labels → `/stickers-and-labels`**、**Flyers & Brochures → `/flyers-and-brochures`** |
| Q3 表单触达 | 品类页设计 CTA **只做链接**到 `/business-card-design-service`，不内嵌表单 |

## 5. 默认处理（无异议即按此执行）

- postcards / stickers 数据库暂无产品：系列卡链接先指 `/shop?cat=…` 或设计服务页，后期建产品再换
- FAQ/品质条/系列卡文案由我编写英文草稿，实施文档中可逐条改
- 图片全部 AI 生成（红金配色语境、写实产品摄影）
