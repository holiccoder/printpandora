# 首页新增「客户 Logo 墙」区块实施计划

> 2026-07-27。状态：**待开工**（决策已锁定，见 §6）。
> 参照：截图（居中标题 + 一排品牌 logo 的社会证明区块）。
> 配色：不向截图的绿色靠拢，**按本站勃艮第红方案适配**。

---

## 1. 目标结构（参照截图）

```
┌─────────────────────────────────────────┐
│   Great businesses print with InkPavo.           │  ← 居中标题一行
│                                          │
│  [logo] [logo] [logo] [logo] [logo] [logo] │  ← 一排 6 个 logo，等距
└─────────────────────────────────────────┘
```

- **标题**：居中、单行、sentence case、中等字重
- **Logo 行**：6 个占位文字 wordmark 水平等距排列，移动端换行（3×2 / 2×3）
- 区块无边框无卡片，纯排版；上下留白适中（py-12~16）

## 2. 配色适配（勃艮第红方案，不用截图的墨绿）

| 元素 | 色值 | 说明 |
|---|---|---|
| 区块背景 | `#F5F0E8`（暖米白） | 与上下区块的 `#FAF7F2` 形成轻微层次差 |
| 标题 | `#2A2A28`（深灰黑） | 或勃艮第红 `#800020`——实施时两版对比择优 |
| Logo | 深灰黑 `#2A2A28`（透明度 70–80%） | hover 时恢复 100% 或变勃艮第红 |

## 3. 首页位置（已确认）

```
HeroCarousel → PopularProducts(改版) → 【Logo 墙】→ HomePerks → …
```

放在产品区之后、perks 之前：先展示产品，再用社会证明加强信任。

## 4. Logo 内容（已锁定：占位文字 wordmark）

- 6 个**风格化纯文字商标**（不同字重/字体形态，如截图中 Uber/TED 的文字形态），内容为占位品牌名
- 组件同时兼容 `text` 与 `image_url` 两种条目——后期换真实客户 logo 只需改 JSON，无需改代码
- ⚠️ 不使用截图中的知名品牌（非真实客户使用他人商标有法律风险）

## 5. 实施步骤

| # | 步骤 | 涉及文件 |
|---|---|---|
| 1 | 新建组件 `home-logo-wall.tsx`：标题 + logo 行（flex 等距、响应式换行、hover 交互） | `resources/js/components/home-logo-wall.tsx` |
| 2 | 内容入 JSON：`home_page` 新增 `logo_wall` 键（heading + logos[]） | `content/hardcoded-content.json` |
| 3 | 首页挂载：`<PopularProducts />` 之后插入 `<HomeLogoWall />` | `resources/js/pages/home.tsx` |
| 4 | 样式：背景 `#F5F0E8`、标题色、logo 透明度与 hover（token 或 hex，视全站换色进度） | 组件内 |
| 5 | 验证：tsc / build / JSON 合法 / 移动端换行不破版 → 提交推送 | — |

JSON 结构：

```jsonc
"logo_wall": {
  "heading": "Great businesses print with InkPavo.",
  "logos": [
    { "text": "BRANDONE", "font": "bold-sans" },
    { "text": "mellow", "font": "script" },
    { "text": "Atelier.", "font": "serif" },
    { "text": "Northwind", "font": "serif-bold" },
    { "text": "bluehill", "font": "sans" },
    { "text": "SUMMIT", "font": "black-sans" }
  ]
}
```

## 6. 决策记录（2026-07-27 用户确认）

| 项 | 决策 |
|---|---|
| Logo 内容 | **占位文字 wordmark**（后期可换真实客户 logo） |
| 标题文案 | 改写为贴合 InkPavo 的：**"Great businesses print with InkPavo."**（备选："Trusted by businesses of every size." / "From startups to established brands — they print with InkPavo."） |
| 位置 | 产品区（PopularProducts）之后，HomePerks 之前 |
