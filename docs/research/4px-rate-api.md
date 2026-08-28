# 4PX 动态费率接口核查

核查日期：2026-08-28

## 结论

用户提供的链接**不是费率接口**。`ids=53,99,240` 是 4PX 文档的三级目录路径：`53` 是“全球直发服务”，`99` 是其下的“证明下载”，真正的接口 ID 是 `240`。官方菜单将这三个层级及名称列得很清楚：[4PX API 菜单](https://open.4px.com/apiInfo/menu)。

接口 `240` 的名称是 `查询签收证明并下载`（`ds.xms.certificate.sign.query`）。它只接收已有订单/物流单相关的 `order_no`，成功后返回签收证明的文件 URL 和文件名；文档还注明该功能只对部分授权客户开放。因此它既不能在结算前试算运费，也不是查询订单金额的接口：[接口 240 官方详情](https://open.4px.com/apiInfo/detail?id=240)。

## 结算前的动态运费试算

4PX 官方目录中有一个专门的接口：

- 接口 ID：`181`
- 名称：`预估费用查询/运费试算`
- API code：`ds.xms.estimated_cost.get`
- 正式地址：`https://open.4px.com/router/api/service`

当不传已有 `request_no` 时，该接口要求传入目的国二字码 `country_code` 和实际重量 `weight`（克）；还可以传长、宽、高、货物类型、物流产品代码列表和收件人邮编。返回结果示例包含计费重量 `charge_weight`、预计时效 `estimated_time`、物流产品代码 `logistics_product_code` 和预估总费用 `lump_sum_fee`。这说明它可以根据结算时提交的国家、包裹参数和产品条件动态返回**预估运费**，适合接入 checkout 的实时试算：[接口 181 官方详情](https://open.4px.com/apiInfo/detail?id=181)。

“预估”需要保留在产品语义中：官方接口名称和返回字段明确是 estimated cost，不应把该值当作最终结算账单。实际可用产品应先通过物流产品查询接口获取并筛选；官方示例中包含 DHL 产品代码，但产品是否对当前账号、起运地和目的地可用仍应以实时接口返回为准：[物流产品查询接口 167](https://open.4px.com/apiInfo/detail?id=167)。

## 下单后的实际费用查询

接口 `214` 才是“订单费用信息”接口（`ds.xms.order.getFreight`）。它要求传已有的 `request_no`，返回 `currency`、`charge_weight`、费用明细 `subs` 以及 `total_fee`。因此它适合下单后做实际费用核对/对账，不能替代 checkout 阶段的报价接口：[接口 214 官方详情](https://open.4px.com/apiInfo/detail?id=214)。

可以按下面的边界理解：

| 场景 | 应使用的接口 | 是否适合作为 checkout 报价 |
| --- | --- | --- |
| 结算前，按国家/重量/尺寸试算 | `ds.xms.estimated_cost.get`（ID 181） | 可以，结果是预估费用 |
| 下单后，按订单号查询账单 | `ds.xms.order.getFreight`（ID 214） | 不可以，必须已有订单 |
| 查询签收证明文件 | `ds.xms.certificate.sign.query`（ID 240） | 不可以，与费率无关 |

## 接入限制

ID 181 的官方详情列出 `app_key`、`secret_key`、时间戳、`sign` 等公共参数，并提供正式与测试地址；`access_token` 对平台服务商/第三方软件商要求传入，4PX B 类客户可不传。也就是说这是需要 4PX 开放平台账号和接口权限的签名 API，不是可以从浏览器匿名调用的公开费率 URL：[接口 181 的公共参数与请求地址](https://open.4px.com/apiInfo/detail?id=181)。

如果使用该接口改造网站，服务端应接收目的国家/邮编、包裹总重量和尺寸，服务端签名调用 ID 181，再把返回的 `lump_sum_fee` 作为预估运费展示；订单创建后再调用 ID 214 做费用核对。AppKey、Secret 和签名逻辑不能放到前端。上述字段和两阶段接口分工均以 4PX 官方详情为准：[ID 181](https://open.4px.com/apiInfo/detail?id=181)、[ID 214](https://open.4px.com/apiInfo/detail?id=214)。

## 按国家动态查询的最小请求

官方页面 `https://open.4px.com/v2/doc?ids=53` 对应“全球直发服务”；该目录下的“产品与试算”包含 ID 181 的“预估费用查询/运费试算”接口：[全球直发服务目录](https://open.4px.com/v2/doc?ids=53)、[4PX API 菜单](https://open.4px.com/apiInfo/menu)。

对 checkout 的新包裹（不传已有 `request_no`），业务参数的最小集合是：

```json
{
  "country_code": "US",
  "weight": "<实际重量，单位克>"
}
```

其中 `country_code` 是目的国二字码，`weight` 是实际重量，二者在未填写 `request_no` 时均为必填。长、宽、高、货物类型、物流产品代码列表和收件人邮编都是可选参数；`cargocode` 不传时官方默认按包裹（`P`）处理。若填写尺寸，长、宽、高必须三个一起传，不能只传其中一两个：[ID 181 请求参数](https://open.4px.com/apiInfo/detail?id=181)。

这套最小参数可以按国家动态请求预估费率，但“最小包裹”不等于“可以确定的最低价格”：官方文档没有声明 `weight` 的下限、最低计费重量、最小尺寸、起步价或最低收费字段。文档目前只明确了上限和格式约束：实际重量需小于 `1,000,000g`；尺寸（若填写）需小于 `1000cm`，并保留两位小数。因此不能仅凭公开文档安全地把 `1g`、`1cm` 或某个固定最低金额当作 4PX 的通用规则；这些规则应以具体物流产品/账号的试算结果或 4PX 商务确认结果为准：[ID 181 完整参数及响应字段](https://open.4px.com/apiInfo/detail?id=181)。

ID 181 的响应字段虽包含 `charge_weight`（计费重/体积重）和 `lump_sum_fee`（官方字段说明为总费用，币种 CNY），但没有返回“最低计费重量/最低尺寸/最低价规则”这类元数据。也就是说，API 可以直接给定一个国家和包裹参数做动态试算，却不能通过该接口直接读取一张通用的最低计费规则表：[ID 181 响应参数](https://open.4px.com/apiInfo/detail?id=181)。

如果需要在网站上提供“最基础包裹”选项，建议让后台保存真实商品重量；未采集尺寸时只传上述两个必要参数，并将返回值标注为预估费。不要为了满足接口而擅自把重量或尺寸填成零/固定最小值；官方文档没有保证这类值被接受，也没有说明它们符合某个物流产品的最低收费规则：[ID 181 请求参数](https://open.4px.com/apiInfo/detail?id=181)。

## 测试环境

ID 181 的官方详情同时列出正式地址 `https://open.4px.com/router/api/service` 和测试地址 `https://open-test.4px.com/router/api/service`，因此可以用测试地址做联调；仍需测试环境的 AppKey/Secret、接口权限和正确的签名参数，不能直接匿名请求：[ID 181 地址与公共参数](https://open.4px.com/apiInfo/detail?id=181)。

4PX 的通用接入指南把测试阶段称为 Sandbox，并要求测试通过后再切换正式环境；该指南的示例域名与 ID 181 详情页的测试域名存在差异，所以应以具体 API 详情页或账号后台当前显示的测试地址为准。测试返回的产品、价格和账号配置不应未经核对直接视为生产费率：[4PX 接入指南](https://open.us.4px.com/apiInfo/partner)。

## AppKey / AppSecret 申请位置

中文帮助页给出的流程是：在 4PX 开放平台注册并登录，完成个人或企业实名认证；进入控制台的“应用服务”，点击“新增”，填写应用联系人并勾选需要的服务后提交。认证审核通过后即可创建应用并开始 API 联调；应用详情页可查看系统生成的 AppKey 与 AppSecret：[中文接入帮助](https://open.4px.com/v2/help/point?ids=help-point,business)。

按自营网站接入自己的 4PX 物流账号，通常应选择“4PX 商家”账号；只有为多个外部商户提供 ERP/软件服务时才选择“合作伙伴/软件服务商”，后者还涉及 OAuth 授权。申请 ID 181 时应在应用服务中勾选全球直发/对应 API 权限；测试联调通过后，再按平台流程申请发布正式环境：[软件服务商接入说明](https://open.us.4px.com/apiInfo/partner)。
