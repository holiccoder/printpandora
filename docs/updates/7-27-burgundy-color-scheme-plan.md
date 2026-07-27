# 全站配色更换实施方案：勃艮第红 + 香槟金

> 2026-07-27。状态：**待确认色值后开工**。
> 目标配色：主色 勃艮第红 / 辅助 香槟金 / 背景 暖白 / 文字 深灰黑。

---

## 1. 建议色值（确认或微调后锁定）

| 角色 | 色名 | hex | 用途 |
|---|---|---|---|
| 主色 | 勃艮第红 | `#800020` | 按钮、链接强调、选中态、焦点环（白字对比度 ~10:1） |
| 辅助 | 香槟金 | `#C9A96A` | 图标、徽章、分隔线、装饰点缀（**不用于小字号正文**） |
| 背景 | 暖白 | `#FAF7F2` | 页面底色 |
| 文字 | 深灰黑 | `#2A2A28` | 正文（暖调深灰，比纯黑柔和） |

派生色（随主色自动生成）：hover/active 用 `primary/90` 透明度档，无需单独 hex。

## 2. 当前配色实现结构（已盘点）

- **主题 token**：`resources/css/app.css`（Tailwind v4 `@theme` + shadcn CSS 变量，`:root` 与 `.dark` 两套）——主色现为 `#1e3a5f`（L71、L83），暗色 `#2a4a73`（L107）
- **硬编码色值**：18 个文件共 **68 处** `#1e3a5f`（及可能残留的旧绿 `#0f4c3a`、铜金 `#b98a3e`），写法包括 `text-[#1e3a5f]`、`bg-[#1e3a5f]`、`border-[#1e3a5f]`、`hover:text-[#1e3a5f]`、`focus:ring-[#1e3a5f]/20` 等

涉及文件（按出现次数）：storefront-header(13)、show.tsx(11)、contact(9)、hero-carousel(6)、blog-hero(5)、recent-posts(4)、errors/not-found(3)、home-perks(3)、dashboard(2)、cart-drawer(2)、legal-page(2)、storefront-footer(2)，以及 announcement-bar / business-card-design-service / dashboard/orders / dashboard/profile / sample-packs / popular-products 各 1。

## 3. 实施三层

### 第 1 层：主题 token（`resources/css/app.css`）

`:root` 变更：

| token | 现值 | 新值 |
|---|---|---|
| `--primary` | `#1e3a5f` | `#800020` |
| `--primary-foreground` | `#ffffff` | `#ffffff`（不变） |
| `--ring` | `#1e3a5f` | `#800020` |
| `--background` | `oklch(1 0 0)` | `#FAF7F2` |
| `--foreground` | `oklch(0.145 0 0)` | `#2A2A28` |
| `--card` / `--popover` | 白 | 保持纯白（暖白底上层次更好） |
| `--secondary` / `--muted` | `oklch(0.97 0 0)` | 暖调浅灰 `#F5F0E8` |
| `--border` / `--input` | `oklch(0.922 0 0)` | 暖灰 `#E8E2D8` |

新增自定义 token（供组件 `text-gold` / `border-gold` / `bg-gold` 使用）：

```css
@theme {
    --color-gold: #C9A96A;
    --color-gold-foreground: #3a2e14;
}
```

> 香槟金**不**塞进 shadcn 的 `--accent`（accent 语义是"悬停浅底色"，金色会显脏）。

`.dark` 暗色块同步：

| token | 新值 |
|---|---|
| `--primary` | `#A83A50`（勃艮第红提亮） |
| `--ring` | `#C9A96A` |
| `--background` / `--foreground` | 保持现有暗色体系 |

### 第 2 层：硬编码批量替换（18 个文件）

- `#1e3a5f` → `#800020`（全部 68 处，含 hover/focus/border 等变体写法）
- 顺带清查：`#0f4c3a`（旧绿）、`#b98a3e`（铜金）若存在 → 分别替换为 `#800020` / `#C9A96A`
- 替换后全仓 grep 验证零残留

### 第 3 层：香槟金点缀（精修）

机械替换后全站为红+白，挑装饰性位置改金色提升质感：

- 首页 perks 区块 6 个线框图标（`home-perks.tsx`）
- 公告栏（`announcement-bar.tsx`）
- 徽章/价格标签、分割线、hover 下划线类细节

## 4. 设计纪律（避免翻车）

- **金色只做装饰**：图标、徽章、边框、分隔线；不用于正文或小字号文字（`#C9A96A` 在暖白底上对比度仅 ~2:1）
- **红金经典搭配**：红底 + 金色描边/文字可用于 CTA 区（如金色描边按钮）
- 暗色模式如启用切换需一并检查

## 5. 验证与收尾

1. `npm run build` 编译通过；`tsc --noEmit` 无错
2. 逐页目视检查：首页（hero、perks、产品卡）、产品详情页、博客、账户页、页头页脚
3. 对比度抽查：主按钮（白字/红底）、链接、表单焦点环
4. 提交 + 推送；服务器端 deploy.sh 每次部署自动 build，无需额外操作

## 6. 工作量与风险

- 改动面：1 个 CSS 文件 + 18 个 tsx 批量替换 + 2–3 处金色点缀
- 风险低：纯视觉层，无逻辑改动；机械替换后若有观感不佳处，回改单点即可
