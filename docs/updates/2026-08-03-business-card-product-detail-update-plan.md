# 名片商品详情页更新实施方案

## 已确认范围

- 应用到所有同时包含 `sizes`、`paper_finish`、`corners` 配置的名片商品。
- 设计服务费用使用美元：`$29` 和 `$79`。
- 两个设计服务 radio 选项默认均不选中，提交前必须主动选择。
- 所有 Special finish 图片保持不变，唯一例外是 `no special finish`，改用 `/images/product-options/no-foil.png`。
- 默认轮播图存在至少 3 张图片时，交换第 1 张和第 3 张；少于 3 张的商品不修改。

## 1. 保护现有工作区改动

开始实施前检查 Git 状态和目标文件 diff。当前以下区域可能已有用户改动，实施时只做增量修改，不回滚或覆盖：

- `resources/js/pages/shop/show.tsx`
- `resources/js/components/storefront-header.tsx`
- `resources/js/components/design-service-form.tsx`
- `content/hardcoded-content.json`
- `content/product-options/**`
- `public/images/product-options/no-foil.png`

同时扫描以下目录，生成实际符合条件的商品清单：

- `content/product-options/business-cards/`
- `content/product-options/cotton-business-cards/`
- `content/product-options/pvc-business-cards/`

后续 swatch 更新和默认轮播图更新都使用这份清单，避免遗漏商品或误改非名片产品。

## 2. 生成新的 swatch 图片

使用现有 swatch 图片作为视觉参考，通过内置图片生成流程生成共享素材。生成内容不包含品牌、姓名、文字、水印或可识别标志。

预计生成以下素材：

- Size：Standard、Square
- Paper finish：Matte、Gloss、UV、Cotton、PVC
- Corners：Square、Rounded

素材规范：

- 使用同一张无品牌名片作为主体。
- 使用统一背景、角度、构图和光线。
- Size 只改变卡片比例。
- Paper finish 只改变纸面材质、纹理和反光效果。
- Corners 只改变直角或圆角形状。
- 保持主体与背景有足够对比，小尺寸下仍然清晰可辨。

最终素材沿用现有 UI 的比例并进行裁剪、压缩：

- Size：约 `526 × 325`
- Paper finish：约 `526 × 251`
- Corners：适合两列选项卡的统一横向比例

新文件使用 `-v2` 等新文件名，不覆盖旧素材，保存到：

- `public/images/product-options/business-cards/sizes/`
- `public/images/product-options/business-cards/laminates/`
- `public/images/product-options/business-cards/corners/`

生成后逐张检查构图、材质差异、边缘质量、可读性和文件大小。不符合要求的图片单独迭代。

## 3. 更新所有商品的 swatch 配置

更新符合范围的产品 JSON：

- `sizes[*].swatch_image`
- `paper_finish[*].swatch_image`
- `corners[*].swatch_image`

更新 `show.tsx` 中 Corners 的渲染逻辑，使其支持：

- 图片 URL：使用 `<img>` 渲染。
- 旧 inline SVG：继续兼容。
- 空值：继续使用现有的 CSS fallback 图形。

Special finish 严格按以下规则处理：

- 名称为 `no special finish` 的选项改为 `/images/product-options/no-foil.png`。
- Foil、spot gloss、raised gloss、`NO NFC` 等其他 special finish 路径完全不改。

## 4. 调整默认轮播图顺序

遍历每个适用商品的 `galleries`，找到 `is_default: true` 的 gallery。

当默认图片数量至少为 3 张时，将数组从：

```text
[1, 2, 3, 4]
```

调整为：

```text
[3, 2, 1, 4]
```

只修改默认 gallery，不修改动态匹配 gallery，也不重新生成轮播图片。

验证以下行为：

- 页面首次打开时，原第 3 张成为主图。
- 原第 1 张出现在第 3 个缩略图位置。
- 切换 Size、Paper finish、Corners 后，动态 gallery 逻辑仍然正常。
- 默认 gallery 少于 3 张的棉纸和 PVC 商品保持原顺序。

## 5. 添加 Turnaround Time tooltip

在第一个 `1 - 2 days Turn Around Time` feature chip 上增加 tooltip，并保留现有 Gang Run Printing tooltip。

tooltip 文案写入 `content/hardcoded-content.json`，整理为以下结构：

```text
Turnaround & delivery

Design
We take 1–2 business days to design your card.

Printing
Allow 1 additional business day for printing and dispatch.

Shipping
Choose standard or expedited shipping at checkout.

U.S. delivery
Delivery to U.S. addresses typically takes 3–7 business days.
```

Tooltip 需要支持鼠标悬停和键盘聚焦，并在移动端限制宽度，避免内容超出视口。

## 6. 调整 Header 搜索栏

将 `global_chrome.header.search.placeholder` 精确改为：

```text
Search for business cards, stickers, flyers and other custom print goods.
```

桌面端将 Header 调整为三栏布局：

```text
左侧 Logo | 居中 Search | 右侧账户和购物车
```

左右两侧使用等宽布局轨道，确保搜索框相对于整个 Header 几何居中，而不是只在剩余 flex 空间内居中。

移动端继续使用现有搜索图标和导航行为，并检查长 placeholder 在窄屏下的截断和输入体验。

## 7. 在 Design for you 弹窗中加入设计方案

只在 `Design for you` 弹窗中显示两个 radio card，不影响免费上传设计的弹窗。

标题按美元显示：

- `名片排版 $29`
- `名片设计 $79`

中文说明沿用用户提供的内容，并将星号说明独立为备注段落，改善长文本的阅读层级。

交互规则：

- 初始没有任何选项被选中。
- 两个选项互斥。
- 未选择方案时不能成功提交，并显示验证提示。
- 关闭弹窗但没有提交，不改变商品价格。
- 只有设计申请成功保存后，才将方案附加到当前商品。
- 重新提交另一方案时替换原方案，不重复叠加设计费。

## 8. 保存设计服务申请数据

扩展设计服务表单、控制器、模型和数据库：

- 增加 `design_service_code`。
- 增加设计费快照字段，例如 `design_service_fee`。
- 更新 `DesignServiceRequest` 的 `$fillable` 和 casts。
- 更新 Filament 的 Design Service Request 列表和编辑页。
- 后端只接受两个合法的设计服务代码。

商品页提交成功后应返回原商品详情页，关闭弹窗并更新当前选择，而不是跳转到独立的设计服务页面。

浏览器只提交方案代码，不提交可信金额。设计费由服务器根据方案代码决定，避免客户端篡改价格。

## 9. 打通前后端计价

在商品详情页增加 `selectedDesignService` 状态。显示总价计算为：

```text
商品印刷价格 + Custom Design Fee
```

同步更新：

- `Your selection` 最后一项显示 `Custom Design Fee — $29` 或 `$79`。
- 商品详情页 Total。
- 原价划线金额，如当前商品存在折扣价。
- `Add to cart · $...` 按钮金额。
- 提交到购物车的 options。

数量价格表保持印刷价格，不将一次性设计费摊入单张卡片价格。

修改 `app/Services/PricingService.php`，让服务端根据合法设计服务代码重新计算金额：

```text
动态或静态商品价格 + 29.00 或 79.00
```

购物车、Checkout、PayPal、Cryptomus 和订单保存都继续使用服务端计算后的金额，确保各页面价格一致。

必要时在购物车商品选项区域显示设计方案，帮助用户确认设计服务费来源。

## 10. 自动化和人工验收

自动化检查：

- 所有产品 JSON 可正常解析。
- 每个新的 swatch URL 都对应真实文件。
- 除 `no special finish` 外，Special finish 图片路径没有变化。
- 没有设计服务时价格保持原样。
- `名片排版 $29` 正确增加 `$29`。
- `名片设计 $79` 正确增加 `$79`。
- 非法设计方案代码不会产生费用。
- 购物车 subtotal、Checkout total 和订单金额一致。
- 设计申请正确保存所选方案和费用快照。

执行项目现有检查命令：

```text
php artisan test
npm run types:check
npm run lint:check
npm run format:check
npm run build
```

人工验收桌面和移动端：

- Size、Paper finish、Corners swatch 的图片、比例和选中态。
- No special finish 图片是否使用 `no-foil.png`。
- 其他 Special finish 图片是否保持不变。
- 默认轮播图第 1、3 张是否正确交换。
- Turnaround tooltip 的悬停、键盘聚焦和移动端显示。
- Header 搜索框是否真正居中。
- 设计方案 radio 的默认状态、键盘操作和长中文内容布局。
- 提交后 `Your selection`、Total、Add to cart、购物车和 Checkout 的金额是否一致。

## 当前状态

本文件只记录实施方案，尚未执行代码修改、图片生成或测试。
