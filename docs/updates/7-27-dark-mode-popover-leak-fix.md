# 修复：深色模式浏览器下页头下拉菜单背景变黑

> 2026-07-27。状态：**待开工**。
> 症状：部分浏览器中，页头导航的 mega 下拉菜单背景呈黑色；浅色系统下正常。

---

## 1. 根因链条

| # | 环节 | 位置 |
|---|---|---|
| 1 | 站点外观默认 `'system'`，系统深色时给 `<html>` 加 `.dark` 类 | `resources/js/hooks/use-appearance.tsx:41,51` |
| 2 | 暗色系 token `--popover` 为近黑色 `oklch(0.145 0 0)` | `resources/css/app.css:104` |
| 3 | shadcn `NavigationMenuViewport` 默认带 `bg-popover text-popover-foreground`，mega 面板渲染于其中，Radix 将 Viewport 高度动画至与内容等高，形成"黑色幕布" | `resources/js/components/ui/navigation-menu.tsx:115` |
| 4 | 自定义代码只给 `NavigationMenuContent` 覆盖了 `!bg-white`，**未覆盖外层 Viewport** | `resources/js/components/storefront-header.tsx:333` |

**为何只有部分浏览器出现**：仅当用户操作系统/浏览器为深色模式时触发（Windows/macOS 深色主题、浏览器强制深色）。

## 2. 修复方案（推荐）

在 `<header>` 元素上加**局部 CSS 变量覆盖**，把 popover 色锁死在 header 子树内：

```tsx
// storefront-header.tsx
<header className="[--popover:#ffffff] relative z-40 w-full border-b border-neutral-200 bg-white">
```

- Tailwind v4 任意属性类，无需改全局 token
- 不影响暗色模式本身、不影响其他页面的 popover 组件
- Viewport 的 `bg-popover` 在 header 子树内解析为白色

### 备选方案（不推荐，仅记录）

| 方案 | 缺点 |
|---|---|
| 改 `navigation-menu.tsx` 的 Viewport 类 | 共享组件，影响其他使用处 |
| 改 `.dark` 全局 `--popover` | 影响全站暗色 popover 语义 |
| 店面页强制禁用 `.dark` | 行为面大，需动 appearance 机制 |

## 3. 同类泄漏排查（实施时一并做）

暗色模式下，以下组件若使用 `bg-popover` / `bg-card` / `bg-muted` 等 token 且预期为白色，需同样局部锁色：

- `storefront-footer.tsx`
- 产品页弹层：dialog（设计服务表单弹窗）、sheet（移动端菜单）、dropdown-menu
- 购物车抽屉（`cart-drawer.tsx`）

处理方式：同方案——在对应根元素加 `[--popover:#ffffff]`（必要时连 `[--card:#ffffff]` / `[--muted:#f5f5f4]` 一并锁）。

## 4. 实施步骤

1. `storefront-header.tsx` 的 `<header>` 加 `[--popover:#ffffff]`
2. 按 §3 清单逐个检查，泄漏处同样处理
3. 验证：macOS/Windows 深色模式下打开首页，展开 mega 菜单背景为白色；浅色模式回归无变化
4. `tsc --noEmit` 无错、`npm run build` 通过
5. 提交 + 推送

## 5. 验证方法（无深色系统时）

- Chrome DevTools → Rendering → `Emulate CSS media feature prefers-color-scheme: dark` 模拟深色环境验证
