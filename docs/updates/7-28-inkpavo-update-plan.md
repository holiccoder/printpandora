# InkPavo 7-28 更新计划 — 实施方案

> 来源：`InkPavo update plan 7-28.pdf`（源文件已存 `docs/updates/InkPavo-update-plan-7-28-source.pdf`）
> 整理日期：2026-07-29。状态：**待确认 §10 的 open 问题后开工**。

---

## 1. 顶部菜单修改

### 1.1 改名（首字母大写 + Cards 复数，见 §10-Q1/Q2）

| 现菜单名 | 改为 |
|---|---|
| standard classic business card | **Classic Standard Business Cards** |
| classic special business cards | **Classic Special Business Cards** |
| classic quality business cards | **Classic Quality Business Cards** |
| classic lush business cards | **Classic Solid Business Cards** |

- 规则：每个单词首字母大写；`card` 单数一律改为 `cards` 复数
- ⚠️ 注意 PDF 示例中写成 "business card" 单数，与复数规则冲突——按规则执行（§10-Q1）

### 1.2 移除 FSC 促销卡

删除 mega menu 中标题为 "Choose from a range of FSC® certified products" 的 promo 卡片（`business_cards_mega_menu.promo_cards` 对应条目）。

### 1.3 菜单项悬停 → 右侧促销卡联动

- **现状**：MegaPanel 已有三级 flyout 的悬停机制（`activeSub`），右侧 promo 卡目前是固定内容
- **改法**：给菜单 JSON 的链接项（或组）增加可选 `promo` 槽位：

```jsonc
{ "label": "Classic Standard Business Cards", "href": "/standard-classic-business-card",
  "promo": { "image_url": "", "title": "", "description": "" } }  // 先留空槽
```

- 悬停时若该槽位有内容则替换右侧卡片（图/标题/描述），**无内容显示默认 promo**
- 组件：悬停事件接入现有 `activeSub` 同一状态流，promo 区随 hover 切换（带淡入过渡）

**涉及文件**：`content/hardcoded-content.json`、`resources/js/components/storefront-header.tsx`

---

## 2. 各产品页价格与图片补全（对照 standard-classic-business-card）

**审计结论**：13 个产品的**定价数据配置全部存在**（控制器 config + pricing.ts 已支持 圆角/烫金/nfc）。缺口在**图片/图库**：

| 产品 | 定价 | 图库现状 | 待办 |
|---|---|---|---|
| standard-classic（试点） | ✅ | 9 galleries / 34 图（联动）✅ | 无 |
| classic special/quality/lush ×3 | ✅ | 10 galleries / 25 图 ✅ | 无（已完成） |
| super / luxe ×2 | ✅ | 6 galleries 但 **14 张全为占位图** | 换真实图（§10-Q4） |
| cotton ×5 | ✅ | 仅 1 个默认 gallery / 2 图 | 补图 + 视需要做选项联动 |
| PVC ×2 | ✅ | 仅 1 个默认 gallery / 2 图 | 补图 |

**另发现**：4 个 **0 字节无效 JSON**（`metal-business-cards.json`、`nfc-business-cards.json`、`cotton-business-cards.json`、`pvc-business-cards.json`）——若被引用会 500，需删除或填充（§10-Q6）。

**涉及文件**：各 `content/product-options/**.json`、`public/images/product-options/**`

---

## 3. 购物车死价修复（价格 = 选项选好的实时价格）

- **根因**：`app/Services/Cart.php:35` → `'price' => 100.00` 硬编码
- **改法（推荐：后端计算，防篡改）**：
  1. 新建 `app/Services/PricingService.php`：把 `pricing.ts` 的梯度算法移植到 PHP（读取 `storage/from-tool/数据文档/**.json`，按 slug+尺寸/覆膜/圆角/特殊工艺/数量 计算小计；公式已验证：start 行全价 base，其余行 base×(1−rate%)，工艺 markup 同理）
  2. `CartController::add` / `Cart::addItem` 调用 PricingService 取价（qty 从 options.quantity 解析）
  3. 前端 `show.tsx` 不再传价（后端为准）
- 静态定价产品（非动态 13 款）回退到静态价格表/现逻辑

**涉及文件**：`app/Services/Cart.php`、`app/Services/PricingService.php`（新）、`app/Http/Controllers/Shop/CartController.php`

---

## 4. design-service 页面布局调整

现状页面已有四档 pricing 卡与 additional services（7-28 计划已实施）。按 PDF 要求：

1. **剔除** pricing cards section
2. **剔除** additional services section
3. **Design process → 左右布局**：图在左、文字在右（配 1 张生成图）
4. **表单 + terms & notes → 左右布局**：文字在左、表单在右

**涉及文件**：`resources/js/pages/business-card-design-service.tsx`、content JSON `design_service_page`、新配图 ×1

---

## 5. 产品页价格行下方：title / description / bullet points 接入 Filament

- **审计**：Filament `ProductResource` 字段已齐全（`subtitle`、`price_line`、`description_title`、`bullet_points`(TagsInput)）✅ 无需加字段
- **待办**：`show.tsx` 目前**未渲染**这些字段 → 在价格行（如 "200 cards from $13"）下方渲染：
  `product.description_title`（标题）→ `product.description`（描述，HTML）→ `product.bullet_points`（要点列表）
- 空值处理：字段为空则不渲染该块（7-22 的假内容阶段结束，以后台配置为准）

**涉及文件**：`resources/js/pages/shop/show.tsx`、后台逐产品补填内容（运营动作）

---

## 6. 去掉面包屑导航下方的 border

- `show.tsx:528`：`<nav className="border-b border-neutral-100 ...">` → 移除 `border-b border-neutral-100`

---

## 7. 顶部菜单 sticky（产品页除外）

- 现状：`<header className="[--popover:#ffffff] relative ...">`（非 sticky）
- 改法：`sticky top-0` + 阴影过渡；**产品详情页禁用**（与产品图吸顶冲突）
- 实现：header 组件读取当前页面（`usePage().url` 或路由名），`/…` 产品页（slug 单段或 shop/show）时不加 sticky 类
- 注意：mega menu 面板定位（`!top-[164px]`）在 sticky 后需复核偏移；公告栏是否一并 sticky（默认：只 sticky 主导航行，公告栏随流）

**涉及文件**：`resources/js/components/storefront-header.tsx`

---

## 8. Footer 左栏：logo + 公司简介

- 在 footer 最左列加入：logo 图 + 下方简介文字（PDF 已给全文）：

> Since establishing our presence in Shanghai, China in 2003, we have been a pioneer in China's printing industry for 23 years, building deep technical expertise and industry insight. With over 20 years of offline printing experience and 15 years of online printing services, we have developed a precise understanding of the core needs of individual consumers, corporate clients, advertising agencies, and designers — and we provide tailored solutions for each group.

- logo 默认用已处理的 `logo-wordmark-sm.png`（§10-Q3）
- 文字入 `global_chrome.footer` JSON

---

## 9. Footer 清理与 4 栏布局

- **移除**：`Fonts`、`Company information`、**Paper Stocks 整列**及其全部子项
- **保留为 4 栏**：
  1. **品牌栏**（新，§8：logo + 简介）
  2. **Products**：All Products / Business Cards / Postcards / Stickers and Labels / Brochures / Flyers
  3. **Essential Links**：Contact us / Refer and earn / Shipping policy / About InkPavo / FAQs / Shipping and cost calculator / Blog
  4. **社交/其他**（现有 social_links 区，保留）

**涉及文件**：`content/hardcoded-content.json`（footer 重构）、`resources/js/components/storefront-footer.tsx`

---

## 10. 决策记录（2026-07-29 用户确认）

| 项 | 决策 |
|---|---|
| Q1 菜单文案 | `Classic Standard/Special/Quality/Solid Business Cards`（首字母大写 + Cards 复数）✅ |
| Q2 改名范围 | **DB 产品名、slug、URL 一起改**（含内容 JSON 文件名、控制器配置、全仓引用） |
| Q3 Footer logo | 用户提供：`public/images/footer-logo.png`（到位前先按此路径引用） |
| Q4 产品图片 | **AI 生成占位**（super/luxe/cotton/PVC） |
| Q5 购物车计价 | **后端 PricingService 计算**（防篡改） |
| Q6 0 字节 JSON | **删除**（metal/nfc/cotton/pvc 共 4 个） |

slug 命名（与现有复数风格一致）：`classic-standard-business-cards`、`classic-solid-business-cards`；special/quality slug 已是复数，仅显示名规范为首字母大写。

---

## 附：实施顺序建议

P0（独立小项，先行）：6 面包屑 → 7 sticky → 1.2 删 FSC 卡 → 9 footer → 8 footer 品牌栏
P1（菜单）：1.1 改名 → 1.3 悬停联动
P2（页面）：4 design-service 布局 → 5 产品页字段渲染
P3（定价闭环）：3 后端计价 → 2 图片补全（依赖 Q4 答案）
