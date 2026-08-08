# 名片商品详情页区块重建实施方案

## 已确认范围

- 所有名片商品详情页都显示新的五段内容。
- 每个名片商品使用自己的产品 JSON 配置 FAQ、纸张推荐、下载链接、CTA 和图片。
- FAQ 使用三列布局，每项包含问题和答案。
- 下载链接先使用 `/templates/...` 假 URL，后续补充真实文件。
- `Compare plans` 链接到 `/business-card-design-service`。
- 图片允许使用 AI 按文字描述生成接近的概念图。
- 删除当前详情页中对应的全部旧营销区块，不保留旧实现。

新的页面顺序：

商品图片 + 商品选项
→ Design Specifications
→ Business Design Services Banner
→ Paper Stock Comparison
→ Even more good stuff
→ FAQ

## 1. 产品详情数据结构

现有页面通过 `content/product-options/{category}/{slug}.json` 加载商品配置。在每个符合条件的名片 JSON 中新增统一的 `detail_sections` 节点。

建议结构：

```json
{
  "detail_sections": {
    "design_specifications": {
      "heading": "Design Specifications",
      "diagram": {
        "bleed": {
          "label": "Bleed Area",
          "dimensions": "3.66\" x 2.16\"",
          "description": "Make sure that your background extends to fill the bleed to avoid your Business Cards having white edges when trimmed."
        },
        "trim": {
          "label": "Trim",
          "dimensions": "3.50\" x 2.0\"",
          "description": "This is where we aim to cut your cards."
        },
        "safe_area": {
          "label": "Safe Area",
          "dimensions": "3.34\" x 1.84\"",
          "description": "Make sure any important aspects of your design such as text and logos are inside of the safe area, otherwise they may be cut off."
        }
      },
      "downloads": [
        {
          "id": "photoshop",
          "label": "Photoshop",
          "extension": ".psd",
          "href": "/templates/template.psd",
          "color": "#2563eb"
        },
        {
          "id": "illustrator",
          "label": "Illustrator",
          "extension": ".ai",
          "href": "/templates/template.ai",
          "color": "#f97316"
        },
        {
          "id": "indesign",
          "label": "InDesign",
          "extension": ".indd",
          "href": "/templates/template.indd",
          "color": "#ec4899"
        },
        {
          "id": "jpeg",
          "label": "Jpeg",
          "extension": ".jpg",
          "href": "/templates/template.jpg",
          "color": "#0f766e"
        }
      ]
    },
    "design_service_banner": {
      "heading": "Need help designing your Business Cards?",
      "body": "Unlock our team of expert designers, enjoy special discounts and more with a MOO Business Plan.",
      "cta_label": "Compare plans",
      "cta_href": "/business-card-design-service",
      "image_url": "/images/product-detail/business-design-banner.png",
      "image_alt": "Two angled business card design concepts"
    },
    "paper_stocks": {
      "heading": "Check out our other paper stocks",
      "subtitle": "We start at premium and go all the way to extra fancy.",
      "items": []
    },
    "more_good_stuff": {
      "heading": "Even more good stuff",
      "items": []
    },
    "faq": {
      "heading": "Frequently asked questions",
      "items": []
    }
  }
}
```

每个商品独立配置。修改一个商品的 FAQ、价格、图片或 CTA 时，不影响其他商品。实施时扫描现有名片 JSON，避免在前端硬编码商品 slug。

## 2. 新增可复用组件

新增目录：

```text
resources/js/components/product-detail/
```

新增组件：

- `design-specifications-section.tsx`
- `design-service-banner.tsx`
- `paper-stock-comparison-section.tsx`
- `more-good-stuff-section.tsx`
- `product-faq-section.tsx`
- `file-format-icon.tsx`

`show.tsx` 只负责读取 JSON、按顺序传入数据和组合页面。所有区块使用统一居中容器：

```tsx
<div className="mx-auto max-w-7xl px-4">
```

## 3. Design Specifications

文件：`resources/js/components/product-detail/design-specifications-section.tsx`

桌面端为左右两栏，移动端变为单列。左侧使用 HTML、CSS 或 inline SVG 绘制规格图，不使用 AI 图片：

- 粉色外框表示 Bleed Area。
- 实线内框表示 Trim Line。
- 点状内框表示 Safe Area。
- 展示 Pink Box Icon、Crop Mark Icon、Dotted Box Icon。
- 尺寸和说明文字全部来自当前商品 JSON。
- 为图示增加 `aria-label` 和辅助说明。

右侧白色下载卡片标题为 `Download a Design Guideline`，显示 Photoshop、Illustrator、InDesign 和 Jpeg 四个下载链接。每个链接包含彩色格式图标、软件名称、后缀、`download` 属性和 `aria-label`。

初始假 URL：

- `/templates/template.psd`
- `/templates/template.ai`
- `/templates/template.indd`
- `/templates/template.jpg`

## 4. Business Design Services Banner

文件：`resources/js/components/product-detail/design-service-banner.tsx`

使用浅鼠尾草绿色背景和居中容器。左侧显示标题、说明和深绿色 `Compare plans` 按钮，按钮跳转到 `/business-card-design-service`。

右侧使用 imagegen 生成一张组合概念图：

- 右上为奶油色名片，带金色箔压、贝壳 logo、`PAMPEZ` 和 `ESTD 2023`。
- 下方为白色名片，带黑色 `SIGMA STUDIO` 和 infinity symbol。
- 两张卡片倾斜、重叠，背景与 Banner 的绿色协调。

保存为：

```text
public/images/product-detail/business-design-banner.png
```

生成后检查文字、箔压效果、卡片层次和移动端裁剪。若 AI 文字不够准确，可以保留图片材质，再用 HTML 或 SVG 叠加准确文字。

## 5. Paper Stock Comparison

文件：`resources/js/components/product-detail/paper-stock-comparison-section.tsx`

白色背景，桌面端四列，平板端两列，手机端单列。每列包含图片、名称、价格、五条特性和绿色 CTA。

默认数据：

- Original Business Cards：50 cards from $23.00，16pt、premium paper、matte/gloss、质量和性价比、FSC® Certified；CTA 为 `Start making ›`。
- Super Business Cards：50 cards from $33.00，18pt、silky smooth、Soft Touch/High Gloss、foils/special finishes、FSC® Certified；CTA 为 `Shop Super Business Cards ›`。
- Luxe Business Cards：50 cards from $43.00，32pt、4-Layer Mohawk Superfine®、eight color seams、uncoated、FSC® Certified；CTA 为 `Shop Luxe Business Cards ›`。
- Cotton Business Cards：50 cards from $33.00，17pt、cotton linters、uncoated bright white、lightweight durable、sustainably sourced；CTA 为 `Shop Cotton Business Cards ›`。

价格、特性、图片和 href 全部配置在产品 JSON。默认 href 可使用：

- Original：`/classic-business-cards`
- Super：`/super-business-cards`
- Luxe：`/luxe-business-cards`
- Cotton：`/classic-cotton-business-card`

使用 imagegen 生成四张统一比例的局部特写，保存到：

```text
public/images/product-detail/paper-stocks/
```

建议文件名：

- `original-business-cards.png`
- `super-business-cards.png`
- `luxe-business-cards.png`
- `cotton-business-cards.png`

图片使用 `loading="lazy"`，并统一比例。

## 6. Even More Good Stuff

文件：`resources/js/components/product-detail/more-good-stuff-section.tsx`

使用柔和米白背景、居中容器和四列产品网格。每项包含正方形灰色图片区和绿色链接，平板端两列，手机端单列。

四个配置项目：

- Original Postcards：Meddo 茶饮明信片、红橙色植物摄影卡片、黑色剪影圆形元素；链接为 `Original Postcards ›`。
- Super Postcards：SaturdaySocial 橄榄绿色卡片、Celebrate 碰杯插画和绿色圆形元素；链接为 `Super Postcards ›`。
- Premium Flyers：SIGMA GIFT CERTIFICATE £50、深红棕色 STUDIO 排版；链接为 `Premium Flyers ›`。
- Brochures：室内设计照片折页、SIGMA STUDIO、紫色 THIS IS WHY WE DO IT 折页；链接为 `Brochures ›`。

使用 imagegen 生成四张正方形组合 mockup 图片，保存到：

```text
public/images/product-detail/even-more/
```

建议文件名：

- `original-postcards.png`
- `super-postcards.png`
- `premium-flyers.png`
- `brochures.png`

CTA href 放入产品 JSON。默认可使用 `/postcards` 和 `/flyers-and-brochures`，后续可以单独修改。

## 7. FAQ

文件：`resources/js/components/product-detail/product-faq-section.tsx`

使用浅灰色背景和居中容器。桌面端三列，平板端两列，移动端单列。每个 block 包含问题和答案，不使用 accordion。

配置格式：

```json
{
  "faq": {
    "heading": "Frequently asked questions",
    "items": [
      {
        "question": "What are the exact dimensions?",
        "answer": "Standard and square sizes are available..."
      }
    ]
  }
}
```

每个名片商品可以独立配置 FAQ 标题、数量、问题、答案和顺序。前端使用语义化的 `section`、`h2`、`ul`、`li`、`h3` 和 `p`。

## 8. 替换现有页面区块

在 `resources/js/pages/shop/show.tsx` 中保留顶部商品图片、选项、数量、设计服务和购物车逻辑。

删除商品选项之后的全部旧营销 JSX：

- 旧 `design_guidelines`
- 旧 `lifestyle_banner`
- 旧 `paper_stocks_section`
- `printfinity_banner`
- `business_solutions_section`
- 旧 `cross_sell_section`
- 旧 `faq`

替换为：

```tsx
<DesignSpecificationsSection content={productOptions.detail_sections.design_specifications} />
<DesignServiceBanner content={productOptions.detail_sections.design_service_banner} />
<PaperStockComparisonSection content={productOptions.detail_sections.paper_stocks} />
<MoreGoodStuffSection content={productOptions.detail_sections.more_good_stuff} />
<ProductFaqSection content={productOptions.detail_sections.faq} />
```

新的 Even More Good Stuff 使用 JSON 固定内容，不再依赖数据库 related products。完成所有 JSON 迁移后，可以删除 ProductController 中不再需要的 `related` 查询和 props。

## 9. 类型和内容清理

新增或扩展：

```text
resources/js/types/product-detail.ts
```

定义 `DesignSpecificationContent`、`DesignServiceBannerContent`、`PaperStockContent`、`MoreGoodStuffContent`、`FaqContent` 和 `ProductDetailSections`，并扩展 `show.tsx` 的 `ProductOptions` 类型。

从 `content/hardcoded-content.json` 的 `product_detail_page` 中移除只供旧区块使用的字段：

- `design_guidelines`
- `lifestyle_banner`
- `paper_stocks_section`
- `printfinity_banner`
- `business_solutions_section`
- `cross_sell_section`
- `faq`

只删除商品详情页专用字段，不删除其他页面仍使用的公共内容。

## 10. 图片生成验收

使用内置 imagegen，分别生成 1 张 Banner 图片、4 张 Paper Stock 图片和 4 张 Even More Good Stuff 图片。

每个素材单独生成和检查，确保最终文件保存到项目的 `public/images/product-detail/` 下，而不是只留在默认生成目录。检查主体、构图、文字、比例、清晰度和统一的视觉风格。不合格的图片单独迭代。

设计规范图使用代码绘制，以保证边框、线条和尺寸标签准确。

## 11. 验证方案

执行：

```text
php artisan test
npm run types:check
npm run lint:check
npm run format:check
npm run build
```

增加配置验证：

- 所有名片 JSON 都包含 `detail_sections`。
- 所有 section 必需字段存在。
- 四个下载 href 格式正确。
- 每个产品 FAQ 可以独立渲染。
- Paper Stock 和 Even More Good Stuff 各有四个项目。
- 修改一个 JSON 后不会影响其他商品页面。

人工验收：

- 每个名片商品显示自己的 FAQ 和纸张推荐。
- Design Specifications 左图右卡布局正确。
- 下载链接指向 `/templates/...`。
- Compare plans 跳转到 `/business-card-design-service`。
- Banner 在桌面端和移动端不溢出。
- 纸张推荐响应式布局正确。
- Even More Good Stuff 图片保持正方形。
- FAQ 桌面端三列、移动端单列。
- 原有 Printfinity、Business Solutions、Related Products 和旧 FAQ 不再显示。

## 当前状态

本文件只记录实施方案，尚未执行代码修改、图片生成或测试。
