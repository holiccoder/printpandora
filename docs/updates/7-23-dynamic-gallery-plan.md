# 产品图库随选项联动 — 实施计划（试点：/standard-classic-business-card）

> 2026-07-23。状态：**待开工**。
> 素材源：`docs/image-materials/300g铜版纸+uv/`（每文件夹含 .png + .webp，**只用 .webp**）。

---

## 1. 目标

- 产品页图库默认展示 4 张图；当用户更改产品选项（覆膜 / 圆角 / 特殊工艺）时，4 张图随之切换。
- 先行在 `/standard-classic-business-card` 试点，验收后再推广到其他产品。

## 2. 素材现状（已盘点）

| 文件夹 | .webp 数量 | 备注 |
|---|---|---|
| 亮膜 | 4 | exec-* 命名 |
| 亮膜圆角 | 4 | 01–04 中文命名 |
| 哑膜 | 4 | exec-* 命名 |
| 哑膜圆角 | 4 | 01–04 中文命名 |
| 哑膜uv | 4 | 01–04 sharp-*-line-uv |
| 哑膜圆角uv | 4 | 05–08 rounded-*-line-uv |
| 哑膜烫金 | **3** ⚠️ | 烫哑银 / 烫绿金 / 烫蓝金，无第 4 张 |

⚠️ **注意**：哑膜烫金只有 3 张图——该组合显示 3 张（缩略图也少一张），还是需要补第 4 张时再补素材。

## 3. 选项映射表（最终版）

> 已按文件夹实际结构修正你列表中的 3 处笔误：`哑膜` 对应 **square**（非 rounded）、`哑膜圆角` 对应 **matte**（非 uv）、重复的"哑膜烫金"实为**同一文件夹同时覆盖 square/rounded 两种圆角**。
> size（Standard/Square 卡片尺寸）**不影响图库选择**——素材不区分尺寸。

| 文件夹 | paper_finish | corners | special_finish |
|---|---|---|---|
| 亮膜 | gloss | square | no special finish |
| 亮膜圆角 | gloss | rounded | no special finish |
| 哑膜 | matte | square | no special finish |
| 哑膜圆角 | matte | rounded | no special finish |
| 哑膜uv | uv | square | no special finish |
| 哑膜圆角uv | uv | rounded | no special finish |
| 哑膜烫金 | matte | square **或** rounded | gold foil |

**默认图库**（未做选择 / 未覆盖组合的回退）：`哑膜`（= 默认选项 matte + square + 无特殊工艺）。

**未覆盖组合**：silver foil / spot glass / raised spot glass 任何覆膜、以及 uv+烫金——回退到默认图库（暂不接单，后期有素材再加文件夹即可扩展）。

**gloss 特殊约束**：选 gloss 时 special_finish 只能是 no special finish——UI 上禁用其他特殊工艺选项；若已选其他工艺再切到 gloss，自动重置为 no special finish。

## 4. 实施步骤

### ① 素材整理（一次性脚本）

把 .webp 复制到 Web 目录，文件夹转 ASCII、文件按自然顺序重命名：

```
docs/image-materials/300g铜版纸+uv/<文件夹>/*.webp
  → public/images/product-options/business-cards/galleries/standard-classic/<key>/01.webp …
```

| 文件夹 | 目标 key |
|---|---|
| 亮膜 | gloss |
| 亮膜圆角 | gloss-rounded |
| 哑膜 | matte（同时作为默认图库） |
| 哑膜圆角 | matte-rounded |
| 哑膜uv | matte-uv |
| 哑膜圆角uv | matte-rounded-uv |
| 哑膜烫金 | matte-foil（仅 01–03 三张） |

（保留 docs 原素材不动；只复制 .webp。）

### ② 内容 JSON 配置

`content/product-options/business-cards/standard-classic-business-card.json` 的 `galleries` 数组整体替换为（机制复用现有 `findMatchingGallery`，无需改匹配代码）：

| id | match | images |
|---|---|---|
| default（is_default） | {} | matte/01–04 |
| gloss | `{paper_finish: Gloss, corners: Square, special_finish: no special finish}` | gloss/01–04 |
| gloss-rounded | `{paper_finish: Gloss, corners: Rounded, special_finish: no special finish}` | gloss-rounded/01–04 |
| matte | `{paper_finish: Matte, corners: Square, special_finish: no special finish}` | matte/01–04 |
| matte-rounded | `{paper_finish: Matte, corners: Rounded, special_finish: no special finish}` | matte-rounded/01–04 |
| matte-uv | `{paper_finish: UV, corners: Square, special_finish: no special finish}` | matte-uv/01–04 |
| matte-rounded-uv | `{paper_finish: UV, corners: Rounded, special_finish: no special finish}` | matte-rounded-uv/01–04 |
| matte-foil | `{paper_finish: Matte, corners: Square, special_finish: gold foil}` | matte-foil/01–03 |
| matte-foil-rounded | `{paper_finish: Matte, corners: Rounded, special_finish: gold foil}` | matte-foil/01–03（同图） |

> match 值写原始选项名，`normalizeOptionValue` 会做规范化（现有逻辑）。

### ③ gloss 约束（`show.tsx`）

- `paper_finish` 选中 gloss 时：special_finish 选项组中除 "no special finish" 外的卡片**禁用**（灰化 + 不可点）；
- 若切换到 gloss 前已选其他特殊工艺：自动把 special_finish 重置为 no special finish（保证价格表与图库状态一致）。

### ④ 验证

1. 页面初始：显示哑膜 4 张（默认选项）
2. 逐一切换 finish/corners/special finish，图库按映射表切换；哑膜烫金显示 3 张
3. 选 gloss：特殊工艺组仅剩 no special finish 可选；从 gold foil 切到 gloss 时自动重置且图库变为亮膜
4. 未覆盖组合（如 silver foil）：回退哑膜图库
5. size 切换（Standard/Square）：图库不变，价格表正常
6. 网络面板确认加载的是 .webp；tsc / JSON 校验通过

## 5. 推广路径（试点验收后）

其他产品（cotton/PVC/super/luxe）照同一模式：素材文件夹按 `<选项组合>` 组织 → 复制脚本 → 各自内容 JSON 的 galleries → 无需再改代码（gloss 约束已通用化）。

## 6. 默认处理（有异议请指出）

1. 哑膜烫金按 **3 张**展示，不补占位
2. 默认图库 = 哑膜（不再使用现有 classic-default-*.jpg 占位图）
3. 未覆盖的特殊工艺组合回退到默认图库
4. size 不参与图库匹配（素材无尺寸区分）
