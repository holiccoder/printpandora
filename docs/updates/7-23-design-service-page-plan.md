# Business Card Design Service 页面 — 实施文档

> 2026-07-23 锁定。状态：**待开工**。
> 关联：上一轮需求 2（正方形价格表 / 删 paper_finish）经用户确认**整条作废**——square 价格表已验证存在且正确，paper_finish 保留不动。

---

## 1. 目标

1. 新建独立页面 `/business-card-design-service`，内含设计服务说明文案 + 设计服务表单（与产品页弹窗同款）。
2. 在 **Business Cards 下拉菜单（mega menu）**中，"Business Card Sample Pack" 下方新增入口 "Business Card Design Service"。
3. 产品页的两个弹窗**保留不动**（弹窗与独立页面共存；产品页内完成付款的转化路径不受影响）。

## 2. 已锁定决策

| 项 | 决策 |
|---|---|
| URL | `/business-card-design-service` |
| 菜单位置 | mega menu 中 `Business Card Sample Pack`（hardcoded-content.json L106）**正下方** |
| 菜单文案 | "Business Card Design Service" |
| 产品页弹窗 | 保留，与新页面共存；表单抽为共享组件，两处复用 |
| 页面结构 | 说明文案（英文）在上，表单在下 |
| 表单提交 | 维持前端行为（成功 toast），后端端点后期再接 |
| 需求 2 | 作废（square 价格已验证正常；paper_finish 保留） |

## 3. 页面文案（英文，置于表单上方）

> 以下为所提供中文内容的英文版，**涉及费用与责任条款（$100、不可退款等），上线前请复核译文**。

### Intro

Our talented design team is here to help with professional design services. If you would like a business logo on your cards, you must provide the original logo file in an editable vector format (AI, EPS, PDF, SVG). If your logo is not in the correct format, our designers can redraw it for an additional fee based on its complexity. Before discussing your card design with our team, we strongly recommend browsing our range of print products to get familiar with the card stocks and print features you may want in your design. Please note: if you are printing elsewhere, our business card design service will incur an additional charge.

### Design Process

1. Once we receive your payment, our service team will contact you by email during regular business hours (Monday–Friday, 9:00 AM–5:00 PM PST).
2. Our service team will review your design questionnaire to confirm or collect any remaining details — including your print budget, card stock and print feature preferences, and creative or style references — before passing them to our design team.
3. Once your preferences are confirmed, our design team will create your initial business card design within 1–3 business days. Our service team will then email you the design as a PDF for review.
4. To keep revisions within the project scope, please be as detailed as possible about style. The most effective approach is to send us examples of styles you like — written instructions, photos of other designs, drawings, screenshots, or anything that conveys your ideas are all welcome by email. Revision requests beyond the project scope may require purchasing additional design services.
5. With every design concept and subsequent revision, please proofread all information carefully and check spelling.
6. After the design is finalized, confirm your stock, finish, size, order quantity, and shipping address. You will receive a shipping quote and be asked to choose your preferred shipping method. Once paid, we will start producing your cards.

### Business Card Design Notes

1. Please be sure to provide your logo in an editable vector format (AI or EPS).
2. If you don't have access to a vector version of your logo, we can help redraw it for an additional fee; the cost depends on the complexity of the logo and the quality of the original file.
3. The initial design will be emailed to you within 1–3 business days. Subsequent refinements and revisions may each take up to 1 additional business day. The total duration depends on timely communication and the number and complexity of revision requests.
4. Please note: our design service develops an initial design concept based on the preferences and examples you provide. If you are not satisfied with the result, we will do our best to work with you on revisions to find a direction that better suits your style; work beyond the project scope may incur additional design fees.
5. Design service fees are non-refundable once work on the initial concept has begun.
6. Our design services are reserved for customers who print with us. Any files requested by customers who have not purchased our printing services will incur an additional charge.
7. Customers who do not print with us will be charged an additional USD $100 per design.
8. Customers are responsible for confirming that spelling and information on the proof are correct. Even if corrections were previously made or a correct version was provided, errors can still occur. Once the final proof is approved, we accept no liability for any spelling mistakes or inaccuracies.

## 4. 技术实施步骤

| # | 步骤 | 涉及文件 |
|---|---|---|
| 1 | **抽共享表单组件**：把 `show.tsx` 中重设计后的表单（FormRow 布局、email / logo 上传 / business name / card info / type 下拉 / examples 上传 / terms 复选框）提取为独立组件，产品页弹窗改为引用它，行为不变 | 新增 `resources/js/components/design-service-form.tsx`；改 `resources/js/pages/shop/show.tsx` |
| 2 | **新建页面**：StorefrontLayout + SEO + §3 文案（intro / Design Process / Design Notes 三个区块）+ 共享表单组件（卡片容器内） | 新增 `resources/js/pages/business-card-design-service.tsx` |
| 3 | **路由**：按现有静态页模式注册 | `routes/web.php` 增加 `Route::inertia('/business-card-design-service', 'business-card-design-service')->name('business-card-design-service');` |
| 4 | **布局解析**：把新页面名加入 layout resolver 的 null 分支（与 about/shipping/sample-packs 一致，页面自带 StorefrontLayout） | `resources/js/app.tsx`、`resources/js/ssr.tsx` |
| 5 | **菜单**：在 mega menu "Business Card Sample Pack"（L106）下方插入 `{ "label": "Business Card Design Service", "href": "/business-card-design-service" }` | `content/hardcoded-content.json` |
| 6 | **页面文案存放**：§3 文案入 `hardcoded-content.json`（新 key，如 `design_service_page`），页面经 `useContent` 读取——便于后期改文案不动代码 | `content/hardcoded-content.json`、页面组件 |

## 5. 验收标准

1. `/business-card-design-service` 可访问，文案三段 + 表单完整渲染，无布局错乱
2. Business Cards 下拉菜单中，新条目出现在 "Business Card Sample Pack" 正下方并可点击跳转
3. 产品页两个弹窗行为与之前完全一致（回归检查）
4. `tsc --noEmit` 目标文件零错误；prettier 通过；`hardcoded-content.json` JSON 合法
5. SSR 构建（`npm run build:ssr`）无报错（如项目当前使用 SSR 预览）

## 6. 备注

- 表单后端端点仍待用户提供 API 后接入（提交动作 = toast，与产品页弹窗一致）
- §3 英文文案为 AI 译文，特别是费用/责任条款（Notes 第 5、7、8 条），**上线前请人工复核**
