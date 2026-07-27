# Hero 轮播改版实施方案（参照截图：左文右图 + 特性条）

> 2026-07-27。状态：**待开工**。
> 参照：用户提供的横幅截图（凸版名片摄影 + "MADE TO BE REMEMBERED" 排版）。
> 配色与 `7-27-burgundy-color-scheme-plan.md` 一致：勃艮第红 / 香槟金 / 暖白。

---

## 1. 目标结构（参照截图）

横幅整体（桌面端约 21:8，左右两栏）：

**左栏（约 40%，暖米白底 `#F5F0E8`）**，内容垂直居中，四层 + 底部特性条：

| 层 | 内容 | 样式 |
|---|---|---|
| 眉题 eyebrow | "FINE PRINT CRAFT" | 小号大写、宽字距、香槟金 `#C9A96A`，配短细横线 |
| 主标题 | "MADE TO BE REMEMBERED" | **衬线字体**（Playfair Display），两行特大号，勃艮第红 `#800020` |
| 副文案 | "Exquisite letterpress craftsmanship that turns every connection into a lasting impression." | 小号深灰 |
| CTA | "EXPLORE COLLECTION →" | 勃艮第红实底、白字、小按钮带箭头 |

**左栏底部特性条**（三列，图标在上）：

| 图标（勃艮第红线框） | 标题 | 说明 |
|---|---|---|
| 名片/证件 | BUSINESS CARDS | Premium & Professional |
| 印刷机 | FINE PRINT CRAFT | Quality in Every Detail |
| 铅笔 | CUSTOM DESIGN | Tailored for You |

**右栏（约 60%）**：整幅摄影图铺满，无文字覆盖——凸版/盲压名片 + 粗石板 + 尤加利叶 + 酒红道具，暖调光影。

## 2. 与现状的差异（`resources/js/components/hero-carousel.tsx`）

现有结构 = 主标题 + 副标题 + CTA 三件套。需新增：

1. 金色眉题（eyebrow）层
2. 主标题改**衬线字体**（需引入 Playfair Display，见 §3.2）
3. 左栏底部三列图标特性条
4. 右栏配图替换为对应产品摄影

## 3. 实施步骤

### 3.1 结构改造（hero-carousel.tsx）

- slide 渲染增加 `eyebrow`、`features`（3 条）字段的渲染
- 左栏底改暖米白 `#F5F0E8`；标题用衬线字体类、勃艮第红；眉题金色宽字距
- 底部特性条：每列 = 线框 SVG 图标（沿用现有图标风格）+ 加粗小标题 + 灰说明
- 移动端：特性条可折行或缩减为横向滚动，保持可读性

### 3.2 衬线字体引入

- 方案 A（推荐）：Google Fonts 引入 **Playfair Display**（600/700 两档），在 `app.css` 加 `--font-serif` token，主标题使用
- 方案 B：系统衬线回退栈（Georgia, 'Times New Roman', serif）——零加载但观感降档

### 3.3 文案入 content JSON（`home_page.hero_carousel`）

slide 对象扩展字段（便于后期改文案不动代码）：

```jsonc
{
  "eyebrow": "FINE PRINT CRAFT",
  "headline": "MADE TO BE\nREMEMBERED",
  "subheadline": "Exquisite letterpress craftsmanship that turns every connection into a lasting impression.",
  "cta_text": "EXPLORE COLLECTION",
  "cta_href": "/shop",
  "features": [
    { "icon": "card",   "title": "BUSINESS CARDS",   "description": "Premium & Professional" },
    { "icon": "press",  "title": "FINE PRINT CRAFT", "description": "Quality in Every Detail" },
    { "icon": "pencil", "title": "CUSTOM DESIGN",    "description": "Tailored for You" }
  ],
  "image_url": "/images/home/hero-letterpress.png",
  "alt": "Letterpress business cards on a stone slab with eucalyptus"
}
```

> 三张 slide 是否都改成此版式：默认**只改第一张**试点，其余两张保留现状，验收后推广。

### 3.4 右栏配图生成

用 image_generation 插件生成（16:9 / 2K，写实产品摄影）：

> Blind-debossed letterpress business cards on a rough stone slab, deep burgundy notebook and eucalyptus branch as props, warm side light, photorealistic product photography

存至 `public/images/home/hero-letterpress.png` 并写入 slide 配置。

### 3.5 特性条图标

按现有线框 SVG 风格绘制 3 个新图标（名片、印刷机、铅笔），硬编码于组件内（同现有 ICONS 做法）。

## 4. 验证

1. 桌面端：版式与参照截图一致（眉题/衬线标题/CTA/特性条/右图）
2. 移动端：左栏在上右图在下，特性条不破版
3. 轮播自动播放与手动切换正常；其他 slide 不受影响
4. `tsc --noEmit` 零错误；`npm run build` 通过；JSON 合法
5. 提交 + 推送

## 5. 备注

- 若 §3.1 之前先完成全站换色（7-27 配色方案），本版式的红/金色直接使用新 token，避免二次改动
- 文案为参照截图内容，上线前可自由替换
