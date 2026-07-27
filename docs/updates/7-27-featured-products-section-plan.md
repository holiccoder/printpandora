# 首页产品展示区改版实施计划（轮播下方 section）

> 2026-07-27。状态：**待开工**。
> 参照：用户提供的截图（左文案栏 + 右 4 张产品卡横幅）。
> 位置：首页 `HeroCarousel` 正下方，即现有 `PopularProducts` 组件的改版（不新增区块、不改动其后的 HomePerks 等顺序）。
> 配色沿用 7-27 勃艮第红方案。

---

## 1. 目标结构（参照截图）

**区块整体**：暖白底，左栏文案（约 30%）+ 右侧 4 张产品卡横排（约 70%）。

**左栏**（与 hero 新版式同一套排版语言）：

| 层 | 内容 | 样式 |
|---|---|---|
| 眉题 | "EXPLORE OUR PRODUCTS" | 香槟金、小号大写、宽字距、短横线 |
| 标题 | "Original Business Cards, Designed to Be Remembered" | 衬线（Playfair Display，随 hero 方案引入）、勃艮第红、约 3 行 |
| 描述 | "Discover our range of premium business cards, crafted with exceptional materials and finishes." | 小号深灰、2 行 |
| 链接 | "VIEW ALL PRODUCTS →" | 勃艮第红、小号大写加粗、箭头，链至 `/shop` |

**右侧 4 张产品卡**（每张：圆角照片 + 标题 + 一行描述 + 右下圆形红底箭头按钮）：

| # | 卡 | 描述 | 链接目标（默认，可调） |
|---|---|---|---|
| 1 | Letterpress Business Cards | Timeless impression, touch that lasts. | `/luxe-business-cards` |
| 2 | Foil Stamped Cards | Shine with metallic foil finishes. | `/super-business-cards` |
| 3 | Debossed Business Cards | Subtle depth that speaks elegance. | `/standard-classic-business-card` |
| 4 | Die-cut Business Cards | Unique shapes for bold brands. | `/shop?cat=business-cards` |

> 卡片主题默认沿用截图的 4 个工艺方向（与精品纸产品线吻合）；如想换成现有 10 个产品中的其他款，改 JSON 即可。

## 2. 与现状差异

现有 `PopularProducts`：10 个产品网格 + 4:3 图片。新版：左栏导语 + 固定 4 张横排卡。属于**组件重构**（保留文件名或新建 `featured-products.tsx` 替换引用）。

## 3. 实施步骤

### 3.1 内容入 JSON（`home_page.popular_products` 重构）

```jsonc
"popular_products": {
  "eyebrow": "EXPLORE OUR PRODUCTS",
  "headline": "Original Business Cards, Designed to Be Remembered",
  "description": "Discover our range of premium business cards, crafted with exceptional materials and finishes.",
  "cta_text": "VIEW ALL PRODUCTS",
  "cta_href": "/shop",
  "cards": [
    { "title": "Letterpress Business Cards", "description": "Timeless impression, touch that lasts.", "image_url": "/images/home/card-letterpress.png", "href": "/luxe-business-cards" },
    { "title": "Foil Stamped Cards",         "description": "Shine with metallic foil finishes.",  "image_url": "/images/home/card-foil-stamped.png",  "href": "/super-business-cards" },
    { "title": "Debossed Business Cards",    "description": "Subtle depth that speaks elegance.",  "image_url": "/images/home/card-debossed.png",     "href": "/standard-classic-business-card" },
    { "title": "Die-cut Business Cards",     "description": "Unique shapes for bold brands.",      "image_url": "/images/home/card-die-cut.png",      "href": "/shop?cat=business-cards" }
  ]
}
```

> ⚠️ 现有 10 个产品条目（含我们刚替换的 AI 图）将被此结构取代——如需保留那些产品，可迁移到 `/shop` 列表页（已存在）。实施前确认。

### 3.2 组件重构（`resources/js/components/popular-products.tsx`）

- 左栏：眉题 / 衬线标题 / 描述 / 箭头链接（复用 hero 新版式的排版类）
- 右侧：`grid-cols-4`（xl）/ `grid-cols-2`（sm-lg）卡片
- 卡片：圆角图（aspect 4:3, object-cover）→ 标题 → 一行描述 → 右下角圆形红底白箭头按钮（整卡可点，箭头为视觉指示）
- 移动端：左栏在上，卡片 2×2（或横向滚动，实施时择优）

### 3.3 四张配图生成（image_generation，3:2 1K，写实摄影）

| 文件 | 提示词要点 |
|---|---|
| `card-letterpress.png` | burgundy letterpress business cards, blind debossed texture, stacked, warm studio light |
| `card-foil-stamped.png` | burgundy business cards with gold foil stamped logo, luxury macro photography |
| `card-debossed.png` | white/cream debossed business cards, subtle blind emboss, soft light |
| `card-die-cut.png` | burgundy die-cut business cards with custom cut-out shapes, creative product photography |

存 `public/images/home/` 并写入 JSON。

### 3.4 依赖与顺序

- 衬线字体：随 `7-27-hero-carousel-redesign-plan.md` 的 Playfair Display 引入（若 hero 未做，本区块单独引入亦可）
- 颜色：如全站换色（7-27 配色方案）已完成，直接用 token；未完成则先用 hex（`#800020`/`#C9A96A`/`#F5F0E8`）

## 4. 验证

1. 桌面端版式与截图一致：左栏三层 + VIEW ALL 链接；右 4 卡横排、圆形箭头按钮
2. 各卡片点击跳转正确目标页；VIEW ALL → `/shop`
3. 移动端不破版（左栏上、卡片 2×2/滚动）
4. `tsc --noEmit` 零错误；`npm run build` 通过；JSON 合法
5. 提交 + 推送

## 5. 默认处理（有异议请指出）

1. 卡片主题沿用截图 4 工艺方向，链接目标按 §1 表默认
2. 现有 10 个产品条目从首页移除（`/shop` 列表页仍可访问全部产品）
3. 仅改版本区块，不动 HomePerks / SamplePackBanner / RecentPosts
