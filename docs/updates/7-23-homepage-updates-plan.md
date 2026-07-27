# 首页修改实施文档（菜单 + perks 区块）

> 2026-07-23 锁定。状态：**待开工**。
> 品牌说明：整站品牌已确认为 **InkPavo**（不是残留，勿改成 PrintPandora）。
> Trustpilot 项：用户决定**不处理**（`perks.trustpilot` 死数据保留）。

---

## 1. 已锁定决策

| 项 | 决策 |
|---|---|
| 顶部菜单第 4 项 | `Flyers` → **`Flyers & Brochures`**（href 不变，仍 `/shop?cat=flyers`） |
| 顶部菜单第 5 项 | `Business Services` → **`Design Service`**，href → `/business-card-design-service` |
| Trustpilot | 不处理 |
| perks 区块 | 整体替换为 **6 列**：图标（藏青线框 SVG，样式同原区块）+ 英文 title + 一行 description |
| 布局 | 响应式：`sm:grid-cols-2` / `lg:grid-cols-3` / `xl:grid-cols-6`（大屏一排 6 列） |
| 品牌 | InkPavo 保留；`section_aria_label` 维持 "Why shop with InkPavo" |

### 默认处理（用户未逐项回答，实施时按此执行，可事后翻改）

- **新列不可点击**（纯展示，去掉整卡 Link）；若后续要加链接（如 1V1 设计服务 → /business-card-design-service）改组件一处即可
- 原 Business Services 的入口除顶部菜单外不做额外保留（footer 现状不动）
- perks 区块第 3 条 "More perks for your business"（含 InkPavo Business Services 文案）随区块替换一并移除

---

## 2. perks 新区块内容（英文）

| # | title | description（一行） | 图标主题 |
|---|---|---|---|
| 1 | Product Variety | From business cards to banners, every print essential in one place. | 货架/多形状组合 |
| 2 | Craftsmanship Variety | Foil, embossing, UV, die-cut and more — pick the finish that fits your brand. | 烫印/工艺工具 |
| 3 | Material Variety | A curated range of stocks, from everyday 300gsm to ultra-thick 700gsm cotton. | 层叠纸张 |
| 4 | Great Value | Premium quality at honest prices, every single order. | 价格标签 |
| 5 | 1-on-1 Design Service | Work directly with a designer to bring your ideas to life. | 铅笔 + 对话气泡 |
| 6 | Global Print Partner | We print and ship worldwide, wherever your business takes you. | 地球 |

图标规格（与原区块一致）：藏青 `#1e3a5f`（currentColor 由父级控制）、64×64 viewBox、stroke 1.5、round cap/join、内联 SVG 硬编码于组件。

---

## 3. 技术实施步骤

| # | 步骤 | 涉及文件 |
|---|---|---|
| 1 | 顶部菜单：第 4 项 label 改 "Flyers & Brochures"；第 5 项 label 改 "Design Service"、href 改 `/business-card-design-service` | `content/hardcoded-content.json`（`global_chrome.header.top_navigation`） |
| 2 | perks 内容：`home_page.perks.items` 替换为 §2 的 6 条（title + description，无 href）；`section_aria_label` 保持 "Why shop with InkPavo" | `content/hardcoded-content.json` |
| 3 | 组件改造：① `ICONS` 数组换成 6 个新 SVG 图标组件；② 网格类改为 `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6`；③ href 改为可选——无 href 时渲染 `<div>` 而非 `<Link>` | `resources/js/components/home-perks.tsx` |
| 4 | 验证：`hardcoded-content.json` JSON 合法；`tsc --noEmit` 目标文件零错误；prettier 通过 | — |
| 5 | 收尾：提交 + 推送 | — |

---

## 4. 验收标准

1. 顶部菜单第 4 项显示 "Flyers & Brochures"，第 5 项显示 "Design Service" 且点击跳转 `/business-card-design-service`
2. 首页 perks 区块为 6 列新内容（xl 屏一排 6 列、lg 两排 3 列、小屏两列/单列），图标为藏青线框风格、视觉与原区块一致
3. 旧的 4 条 perks（Next day delivery! / The InkPavo Promise / More perks / Printfinity）不再出现
4. 页面无 InkPavo → 其他品牌名的误改；Trustpilot 相关现状不变
