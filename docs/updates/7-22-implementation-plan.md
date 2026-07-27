# InkPavo 网站改版实施计划

> 基于 `docs/updates/7-20-updates.md` 及 2026-07-22 讨论结论整理。
> 状态：P1 / P2 大部分已落地（站外执行）；P3 运费计算器、P4 配色、附录 A（Super/Luxe，2026-07-23 锁定）**待开工**。

---

## 0. 范围总览

| 包含 | 明确推迟（后期） |
|---|---|
| P1 产品页微调、P2 新产品+导航、P3 运费计算器、P4 全站配色 | 1.4 special finish 色卡图片替换（等素材）；6. 4PX 物流跟踪（等 API 凭证） |

---

## 1. 已锁定决策

### 1.1 产品页（对应 7-20 文档第 1 节）

> 注：讨论回复的编号比原文档顺延一位；原文档 1.1「移除评分和评论数」回复中未提及，按仍需执行处理。

| 子项 | 决策 |
|---|---|
| 移除评分 | 移除产品页 Star 评分与评论数显示 |
| 轮播图 | 高度加高到约 **650px**；保留现有吸顶（sticky）行为；吸顶时顶部增加约 **10px** 间距 |
| 两个说明块 | 轮播图下方，替换现有 "double-sided" / "full colour" 两个块：<br>• 左：绿色圆形美国国旗图标，标题 **"Printed in the USA"**，副标题 "Designed by you and InkPavo, printed in the USA"<br>• 右：绿色包裹线框图标，标题 **"Next Day Delivery"**，副标题两行："On matte finish & square corners." / "Order before 2pm (EST) Mon-Fri." |
| finish 3 列网格 | 仅对拥有 3 个 paper finish 的产品生效（目前仅 standard-classic-business-card，即原 300g-tongbangzhi-uv） |
| section 间距 | 产品图 + 选项区域下方的 section 增加 margin-top（约 mt-12，实现时微调） |
| 标题/描述/要点 | 位置：产品标价（如 "200 cards from $13"）下方。先用假内容：<br>标题："Get Business Cards online that set the standard for 'standard'"<br>描述："Thicker than your average card, ... this is the way to go."（MOO 文案替换品牌名为 InkPavo）<br>要点："Choose Square, Standard, or MOO size" / "130lb weight, 16pt thickness" / "Available in Matte or Gloss finishes at no extra charge"<br>后期改为后台配置（products 表已有 `description_title`/`bullet_points` 字段，预留对接） |
| 上传表单重做 | 两个弹窗（"Upload a full design (free)" 与 "Design for you"）按统一结构重做，字段见 §3.2 |

### 1.2 新产品与导航（对应文档第 2 节）

**棉花卡 × 5**（新建产品页）：

| 产品名 | 数据文件 | slug |
|---|---|---|
| Basic cotton business card | 棉纸/棉纸-基础型.json | basic-cotton-business-card |
| Classic cotton business card | 棉纸/棉纸-经典型.json | classic-cotton-business-card |
| Premium cotton business card | 棉纸/棉纸-高级型.json | premium-cotton-business-card |
| Luxe cotton business card | 棉纸/棉纸-豪华型.json | luxe-cotton-business-card |
| Grand cotton business card | 棉纸/棉纸-奢华型.json | grand-cotton-business-card |

- 数据库新建分类 `cotton-business-cards`
- 内容 JSON 以 `classic-business-cards.json` 为模板复制改造；**无 special finish**
- **NFC 作为 special finish**，仅两个选项：NO NFC（默认）/ WITH NFC
- 定价引擎扩展：识别 `nfc` 工艺（棉纸数据工艺为 `圆角` + `nfc`）

**PVC 卡 × 2**（新建产品页）：

| 产品名 | 数据文件 | 说明 |
|---|---|---|
| Standard PVC card | pvc/pvc0.38.json | 工艺仅 `打码`，无烫金 → 不显示 special finish |
| Premium PVC card | pvc/pvc0.76.json | 工艺 `打码` + `烫金` → 保留 foil 选项 |

- 数据库新建分类 `pvc-business-cards`
- 仅 Standard 尺寸（无方形数据，避免选方形后价格表为空）
- 复用现有定价引擎，单场景按 `rectangle` 处理；`打码`（print_code）**不参与计价**

**产品改名（4 款 classic）**：

| 旧名 / 旧 slug | 新名 | 新 slug |
|---|---|---|
| 300g tongbangzhi+uv / 300g-tongbangzhi-uv | Standard Classic Business card | standard-classic-business-card |
| 300g yishuzhi / 300g-yishuzhi | classic special business cards | classic-special-business-cards |
| 320g-tongbanzhi | classic quality business cards | classic-quality-business-cards |
| 350g-baika | classic lush business cards | classic-lush-business-cards |

- 需同步修改：products 表 name+slug、`content/product-options/business-cards/` 对应 JSON 文件名、`ProductController` 定价配置键
- **不做旧 URL 重定向**

**导航菜单**：
- 删除：NFC business card；special finishes 及其子菜单；inkpavo size business cards；mini cards；qrcode business cards；luxe by inkpavo 及其子菜单；business card holders；design a business card
- 新增：cotton business cards 下的 5 个棉花卡条目；PVC business card 下的 standard / premium 两个条目

### 1.3 运费计算器（对应文档第 4 节）

- 路由 `/shipping-calculator`，纯前端静态页，**模拟费率**（明显标注占位，后期用户替换真实数据 / API）
- 输入：目的地国家、产品类型、数量；输出：各运输方式价格与时效

### 1.4 全站配色（对应文档第 5 节）

- 采用方案 **B：深海军蓝 `#1e3a5f` + 铜金点缀 `#b98a3e`**（高端印刷质感，匹配 luxe/金属卡产品线）

---

## 2. 默认处理项（讨论中提出、用户未反对）

1. 参考文案中的 MOO → InkPavo；"Rockdesign's term and condition" → "InkPavo's terms and conditions"，链接到 `/terms`
2. PVC 在数据库同样新建 `pvc-business-cards` 分类
3. 表单 "Business card type" 下拉选项：当前名片类产品名（classic 4 + cotton 5 + PVC 2），后期接后台
4. 棉花卡 / PVC 产品主图与色卡图：先用占位图，素材到位后替换
5. PVC 页面选项结构按 §1.2 所述裁剪

---

## 3. 分阶段实施计划

### P1 — 产品页微调

**改动文件**：`resources/js/pages/shop/show.tsx`、`content/hardcoded-content.json`

1. 删除评分组件（Star 图标 + 评分 + 评论数行）
2. 轮播图容器高度 → ~650px；sticky 定位 `top` 值增加 ~10px
3. 轮播图下方：替换 "double-sided" / "full colour" 块为两个说明块（图标 + 标题 + 副标题，文案见 §1.1）；图标：美国国旗圆形（内联 SVG 或 lucide + 自定义）、包裹（lucide `Package`）
4. paper finish 网格：选项数为 3 时应用 `grid-cols-3`（swatch 卡片一排 3 张）
5. 图 + 选项下方 section 增加 `margin-top`
6. 标价行下方插入 标题 / 描述 / bullet points 块（文案入 `hardcoded-content.json`，便于后期替换）
7. 两个上传表单弹窗重做（结构见下）

**表单结构（两个弹窗共用）**：两栏布局（左 label 右 input）

| 字段 | 控件 |
|---|---|
| Your primary contact email | 单行文本框 |
| Company logo 上传 | 蓝色描边按钮 + 图片图标 "UPLOAD FILES"（vector 格式提示） |
| Name of your business | 单行文本框 |
| Information on the card | 多行 textarea；下方说明："Name, title, contact information, address, website etc you want to have on the card." |
| Business card type | 下拉菜单，占位符 "Please select product" |
| Business card examples you like | 蓝色描边上传按钮 |
| Terms | 未勾选复选框 + "I agree with InkPavo's terms and conditions"（链接 /terms，蓝色） |

**验收**：tsc 零错误；prettier 通过；页面目视检查六项改动生效。

### P2 — 新产品 + 导航

**改动文件**：新迁移、`content/product-options/business-cards/`（新增 7 个 JSON + 4 个改名）、`ProductController.php`、`pricing.ts`、`hardcoded-content.json`

1. **数据库迁移**：建分类 `cotton-business-cards`、`pvc-business-cards`；插入 5 + 2 个产品行；4 个 classic 产品改名 + slug
2. **内容 JSON**：cotton × 5（模板改造：无 special_finish，NFC 两选项）；PVC × 2（仅 Standard 尺寸；0.38 无 special finish，0.76 保留 foil）
3. **pricing.ts**：特殊工艺查找从 `烫金` 扩展为 `烫金 | nfc`
4. **ProductController**：定价配置新增 7 个 slug 映射（cotton 5 个单场景按 rectangle + 无 square？——实施时核对棉纸数据是否含方形文件，若无则同 PVC 裁剪尺寸选项；PVC 2 个）
5. **导航**：按 §1.2 清单删改
6. **数据库快照**：完成后跑 `php artisan db:export-seeders` 更新 seeder 快照

**验收**：`php -l` 迁移与控制器；tsc；7 个新页面可访问且价格表渲染正确（首行 = startQuantity 小计）；改名后 4 个新 URL 可访问；菜单项符合清单。

### P3 — 运费计算器

**改动文件**：新增 `resources/js/pages/shipping-calculator.tsx`、`routes/web.php`、footer/导航入口

- 静态页：表单（国家、产品类型、数量）→ 结果表（运输方式 / 价格 / 时效），费率硬编码于页面或 content JSON，标注 "placeholder rates"
- 不接后端

**验收**：页面可访问、交互正常；tsc 通过。

### P4 — 配色方案 B

**改动文件**：`resources/css/app.css`（主色 token）、含硬编码 `#0f4c3a` 的组件（`show.tsx`、`storefront-header.tsx` 等，全仓搜索替换）

- 主色 token → `#1e3a5f`；点缀/CTA hover → `#b98a3e`；检查对比度
- 全站目视回归（首页、产品页、账户页）

**验收**：无残留旧主色；关键页面目视一致。

---

## 4. 风险与依赖

| 项 | 说明 |
|---|---|
| 产品图片素材 | cotton/PVC 主图、色卡图先用占位图；special finish 色卡（推迟项 1.4）等用户提供 |
| 表单后端 | 弹窗表单仍为前端行为（toast）；真实费率、表单提交端点等用户 "几天后提供 API" |
| 4PX 跟踪 | 推迟；届时需 orders 表加 `tracking_number`/`carrier` 迁移 + API 凭证 |
| 改名影响面 | slug 变更影响：DB、内容 JSON 文件名、控制器配置键、导航链接；不做旧链重定向（已确认） |

---

## 5. 决策记录（2026-07-22 问答摘要）

- 轮播图 650px / 吸顶 + 10px 顶距；说明块文案按用户提供的 MOO 参考（品牌名替换）
- 1.4 色卡替换、6. 4PX 跟踪：用户明确"先忽略，放到后期"
- finish 3 列仅 3-finish 产品；表单结构按用户给出的 Rockdesign 参考（品牌名替换）
- 棉花卡映射顺序确认；新建 cotton 分类；NFC = special finish 两选项，默认 NO NFC
- PVC：standard（0.38）/ premium（0.76），复用现有引擎
- 配色选定方案 B


---

## 附录 A：Super / Luxe Business Cards 新建计划（2026-07-23 锁定）

> 状态：**待开工**。页头菜单链接 `/super-business-cards`、`/luxe-business-cards` 已存在（当前 404），产品 slug 对齐后自动恢复，菜单无需改动。

### 决策（用户已确认）

| 项 | 决策 |
|---|---|
| 显示名 | Super Business Cards / Luxe Business Cards |
| slug | `super-business-cards` / `luxe-business-cards`（与现有菜单链接完全一致） |
| 分类 | 现有 `business-cards`（id=1），不新建分类 |
| 页面选项 | 完整复刻 classic 模板（Standard/Square 尺寸、Matte/Gloss 覆膜、圆角、gold/silver foil、print_code、drill） |
| 内容 JSON | 以 `standard-classic-business-card.json` 为模板复制改造 |
| 产品图片 | 占位图，素材到位后替换（同 cotton/PVC 做法） |
| 页脚纸张区文案 | 保持原样不改（"320 gsm" / "cotton rag" 不动） |

### 数据源（引擎可直接复用，pricing.ts 零改动）

| 产品 | 数据文件 | 场景 | startQty | basePrice | 工艺 |
|---|---|---|---|---|---|
| Super（350g精品纸） | `350g精品纸.json` + `350g精品纸-正方形.json` | rectangle + square | 50 | 0.4 / 0.5 | 圆角、烫金 |
| Luxe（700g精品纸） | `700g精品纸.json` + `700g精品纸-正方形.json` | rectangle + square | 50 | 0.7 / 0.8 | 圆角、烫金 |

### 实施步骤

1. **迁移**：`database/migrations/2026_07_23_000001_add_super_luxe_business_cards.php`，插入 2 个产品（参照 cotton/PVC 迁移模式）
2. **内容 JSON × 2**：`content/product-options/business-cards/super-business-cards.json`、`luxe-business-cards.json`；模板改造点：删除 UV finish（无 UV 数据）、按纸张改写 subtitle、galleries 用占位图、起始价由动态计算覆盖（Super "50 cards from $20"，Luxe "50 cards from $35"）
3. **控制器**：`ProductController.php` 的 `$configs` 增加 2 条映射（目录 `350g精品纸` / `700g精品纸`，rectangle + square 两场景）
4. **验证**：`php -l` → `php artisan migrate` → 反射脚本验证两个 slug 返回 `[rectangle, square]` → 真实 pricing.ts 计算（Super 首行 50×$20、Square 50×$25；Luxe 首行 50×$35、Square 50×$40）→ `php artisan db:export-seeders` 刷新快照 → 全量 tsc
5. **收尾**：提交 + 推送（迁移、2 个 JSON、控制器、快照更新）
