<?php
/**
 * 产品选项 JSON 数据生成器
 *
 * 用于构建产品选项结构的灵活界面：
 * - 顶层分区（例如 sizes、paper_finish、galleries）
 * - 每个分区是一个项目数组
 * - 每个项目包含核心字段：name、code、swatch_image
 * - 每个项目包含结构化的 added_price 价格表
 * - 用户可以为每个项目添加/删除自定义键值字段
 * - 不符合选项模式的分区可以作为原始 JSON 编辑
 */

// Handle swatch image uploads requested via fetch from the tool UI.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['swatch_image'])) {
    header('Content-Type: application/json');

    $file = $_FILES['swatch_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '上传失败。错误代码：' . $file['error']]);
        exit;
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '文件过大。最大允许 5 MB。']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (!in_array($mime, $allowedMimes, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '无效的文件类型。允许：jpg、png、gif、webp、svg。']);
        exit;
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];
    $extension = $extensionMap[$mime] ?? pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';

    $filename = 'swatch_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $uploadDir = __DIR__ . '/../images/product-options/uploads/';
    $webPath = '/images/product-options/uploads/' . $filename;

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => '无法创建上传目录。']);
            exit;
        }
    }

    if (!is_writable($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '上传目录不可写。']);
        exit;
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '无法保存上传的文件。']);
        exit;
    }

    echo json_encode(['success' => true, 'path' => $webPath, 'filename' => $filename]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>产品选项 JSON 生成器</title>
    <style>
        :root {
            --bg: #f6f8fa;
            --surface: #ffffff;
            --border: #d1d9e0;
            --text: #1f2328;
            --muted: #656d76;
            --primary: #0969da;
            --primary-hover: #0550ae;
            --danger: #cf222e;
            --danger-bg: #ffebe9;
            --success: #1a7f37;
            --warning: #9a6700;
            --warning-bg: #fff8c5;
            --radius: 8px;
            --shadow: 0 1px 3px rgba(31, 35, 40, 0.12), 0 8px 24px rgba(66, 74, 83, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 20px 24px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        header h1 { margin: 0; font-size: 20px; font-weight: 600; }

        .page {
            max-width: 1500px;
            margin: 0 auto;
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr 460px;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1200px) {
            .page { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .card-title { font-weight: 600; font-size: 16px; }

        .card-body { padding: 20px; }

        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

        button {
            border: 1px solid var(--border);
            background: var(--surface);
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 14px;
            cursor: pointer;
            color: var(--text);
            transition: background .15s, border-color .15s, box-shadow .15s;
        }

        button:hover { background: var(--bg); }

        button.primary { background: var(--primary); border-color: var(--primary); color: #fff; }
        button.primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }

        button.danger { color: var(--danger); border-color: var(--danger); background: var(--danger-bg); }
        button.danger:hover { background: #ffdce0; }

        button.ghost { border-color: transparent; }
        button.small { padding: 5px 10px; font-size: 12px; }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            background: var(--surface);
            color: var(--text);
        }

        textarea { resize: vertical; min-height: 60px; }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.12);
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .option-group {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 16px;
            background: var(--surface);
        }

        .group-header {
            padding: 12px 16px;
            background: #f3f4f6;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .group-header input {
            font-weight: 600;
            font-size: 15px;
            border: 1px solid transparent;
            background: transparent;
            padding: 4px 6px;
            flex: 1;
            min-width: 120px;
        }

        .group-header input:focus { background: var(--surface); border-color: var(--primary); }

        .group-body { padding: 16px; }

        .group-actions { display: flex; gap: 6px; align-items: center; }

        .option-item {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 12px;
            background: #fafbfc;
            position: relative;
        }

        .option-item:last-child { margin-bottom: 0; }

        .item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .item-title { font-weight: 600; font-size: 14px; color: var(--text); }

        .item-body { }
        .item-body.folded { display: none; }

        .fold-toggle {
            background: transparent;
            border: none;
            padding: 4px 6px;
            cursor: pointer;
            font-size: 14px;
            color: var(--muted);
            transition: transform .15s;
        }

        .fold-toggle:hover { color: var(--text); }
        .fold-toggle.folded { transform: rotate(-90deg); }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .field { display: flex; flex-direction: column; }
        .field-full { grid-column: 1 / -1; }

        .custom-fields {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed var(--border);
        }

        .custom-field-row {
            display: grid;
            grid-template-columns: 160px 1fr auto;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 13px;
        }

        .price-table th,
        .price-table td {
            border: 1px solid var(--border);
            padding: 6px 8px;
            text-align: left;
        }

        .price-table th { background: #f3f4f6; font-weight: 600; }

        .price-table input {
            border: none;
            padding: 4px;
            background: transparent;
            width: 100%;
        }

        .price-table input:focus {
            outline: none;
            box-shadow: none;
        }

        .empty-state {
            text-align: center;
            padding: 32px 20px;
            color: var(--muted);
        }

        .empty-state p { margin: 0 0 12px; }

        .json-output {
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
            font-size: 12px;
            line-height: 1.5;
            width: 100%;
            min-height: 520px;
            border: none;
            outline: none;
            resize: vertical;
            background: #0d1117;
            color: #e6edf3;
            padding: 16px;
            border-radius: 0 0 var(--radius) var(--radius);
        }

        .raw-section textarea {
            width: 100%;
            min-height: 200px;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
            font-size: 12px;
            background: #f6f8fa;
        }

        .validation-message {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 12px;
            display: none;
        }

        .validation-message.error {
            display: block;
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(207, 34, 46, 0.3);
        }

        .validation-message.success {
            display: block;
            background: #dafbe1;
            color: var(--success);
            border: 1px solid rgba(26, 127, 55, 0.3);
        }

        .validation-message.warning {
            display: block;
            background: var(--warning-bg);
            color: var(--warning);
            border: 1px solid rgba(154, 103, 0, 0.3);
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 24px;
        }

        .checkbox-field input { width: auto; }
        .checkbox-field label { margin: 0; color: var(--text); font-size: 14px; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            background: #ddf4ff;
            color: var(--primary);
        }

        .pill.raw { background: var(--warning-bg); color: var(--warning); }

        .drag-handle {
            cursor: grab;
            color: var(--muted);
            padding: 4px;
            line-height: 1;
        }

        .drag-handle:active { cursor: grabbing; }

        .option-group.dragging,
        .option-item.dragging { opacity: 0.5; }

        .mode-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .mode-toggle input { width: auto; }

        .section-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--muted);
            margin: 16px 0 8px;
            font-weight: 600;
        }

        .swatch-upload-row {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .swatch-upload-row textarea {
            flex: 1;
        }

        .file-upload-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--surface);
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
            color: var(--text);
            transition: background .15s, border-color .15s;
        }

        .file-upload-button:hover { background: var(--bg); }

        .file-upload-button input[type="file"] { display: none; }

        .swatch-preview {
            margin-top: 10px;
            min-height: 20px;
        }

        .swatch-preview-img {
            max-height: 90px;
            max-width: 100%;
            border: 1px solid var(--border);
            border-radius: 6px;
            display: block;
        }

        .swatch-preview-svg {
            width: 48px;
            height: 48px;
            display: block;
        }

        .swatch-preview-svg svg {
            width: 100%;
            height: 100%;
        }

        .swatch-preview-placeholder {
            font-size: 12px;
            color: var(--muted);
            font-style: italic;
        }
    </style>
</head>
<body>
    <header>
        <h1>产品选项 JSON 生成器</h1>
    </header>

    <div class="page">
        <main>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">选项分区</span>
                    <div class="toolbar">
                        <button type="button" class="primary" onclick="addGroup()">+ 添加选项分区</button>
                        <button type="button" class="danger" onclick="clearAll()">清空全部</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="validation" class="validation-message"></div>
                    <div id="groups-container">
                        <div class="empty-state">
                            <p>暂无分区。</p>
                            <button type="button" class="primary" onclick="addGroup()">创建第一个分区</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <aside>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">生成的 JSON</span>
                    <div class="toolbar">
                        <button type="button" class="primary" onclick="copyJson()">复制</button>
                        <button type="button" onclick="downloadJson()">下载</button>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <textarea id="json-output" class="json-output" spellcheck="false"></textarea>
                </div>
            </div>

        </aside>
    </div>

    <script>
        let groups = [];
        let nextGroupId = 1;
        let nextItemId = 1;

        const container = document.getElementById('groups-container');
        const output = document.getElementById('json-output');
        const validation = document.getElementById('validation');

        // Delegate swatch image file uploads.
        container.addEventListener('change', e => {
            const input = e.target;
            if (!input.matches('input[type="file"][data-upload="swatch"]')) return;
            const groupId = parseInt(input.dataset.groupId, 10);
            const itemId = parseInt(input.dataset.itemId, 10);
            uploadSwatch(input, groupId, itemId);
        });

        const CORE_FIELDS = ['name', 'code', 'swatch_image'];
        const DEFAULT_PACK_SIZES = [50, 100, 200, 400, 600, 800, 1000, 1200, 1400, 1600, 2000, 3000, 4000, 5000];

        function renderSwatchPreview(path) {
            if (!path || !path.trim()) {
                return '<span class="swatch-preview-placeholder">未选择图片</span>';
            }
            const trimmed = path.trim();
            if (trimmed.toLowerCase().startsWith('<svg')) {
                return `<div class="swatch-preview-svg">${trimmed}</div>`;
            }
            if (/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i.test(trimmed)) {
                return `<img src="${escapeHtml(trimmed)}" alt="色板预览" class="swatch-preview-img" onerror="this.outerHTML='<span class=\\'swatch-preview-placeholder\\'>无法加载预览</span>'" >`;
            }
            return `<span class="swatch-preview-placeholder">${escapeHtml(trimmed)}</span>`;
        }

        function updateSwatchPreview(groupId, itemId, path) {
            const preview = document.querySelector(`div[data-preview-for="${groupId}-${itemId}"]`);
            if (preview) preview.innerHTML = renderSwatchPreview(path);
        }

        async function uploadSwatch(input, groupId, itemId) {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('swatch_image', file);

            showValidation('正在上传色板图片...', 'warning');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Upload failed');
                }

                updateField(groupId, itemId, 'swatch_image', data.path);
                render();
                showValidation('色板图片已上传：' + data.path, 'success');
            } catch (err) {
                showValidation('上传错误：' + err.message, 'error');
            } finally {
                input.value = '';
            }
        }

        function showValidation(message, type = '') {
            validation.textContent = message;
            validation.className = 'validation-message' + (type ? ' ' + type : '');
            if (!message) validation.className = 'validation-message';
        }

        function generateCode(name) {
            return name
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        function createGroup(key = '', isRaw = false) {
            return {
                id: nextGroupId++,
                key: key || 'section_' + nextGroupId,
                isRaw: isRaw,
                rawValue: isRaw ? '[]' : '',
                items: []
            };
        }

        function createItem(data = {}) {
            const item = { id: nextItemId++, folded: true };

            // Core fields
            for (const key of CORE_FIELDS) {
                item[key] = data[key] !== undefined ? data[key] : '';
            }

            // added_price is a structured table; default to the standard pack size ladder
            if (Array.isArray(data.added_price)) {
                item.added_price = data.added_price.map(row => ({
                    pack_size: row.pack_size !== undefined ? row.pack_size : '',
                    price_per_card: row.price_per_card !== undefined ? row.price_per_card : ''
                }));
            } else {
                item.added_price = DEFAULT_PACK_SIZES.map(pack_size => ({
                    pack_size: pack_size,
                    price_per_card: 0
                }));
            }

            // Custom fields: anything else
            item.custom = [];
            for (const [key, value] of Object.entries(data)) {
                if (CORE_FIELDS.includes(key) || key === 'added_price') continue;
                item.custom.push({ key, value: stringifyCustomValue(value) });
            }

            return item;
        }

        function stringifyCustomValue(value) {
            if (value === null) return 'null';
            if (typeof value === 'boolean') return value ? 'true' : 'false';
            if (typeof value === 'object') return JSON.stringify(value);
            return String(value);
        }

        function parseCustomValue(text) {
            text = text.trim();
            if (text === '') return '';
            if (text === 'true') return true;
            if (text === 'false') return false;
            if (text === 'null') return null;
            if (/^-?\d+$/.test(text)) return parseInt(text, 10);
            if (/^-?\d*\.\d+$/.test(text)) return parseFloat(text);
            try { return JSON.parse(text); } catch (e) { return text; }
        }

        function addGroup() {
            const group = createGroup();
            groups.push(group);
            render();
            setTimeout(() => {
                const input = document.querySelector(`[data-group-key-input="${group.id}"]`);
                if (input) input.focus();
            }, 0);
        }

        function removeGroup(id) {
            groups = groups.filter(g => g.id !== id);
            render();
        }

        function moveGroup(id, direction) {
            const idx = groups.findIndex(g => g.id === id);
            if (idx === -1) return;
            const newIdx = idx + direction;
            if (newIdx < 0 || newIdx >= groups.length) return;
            [groups[idx], groups[newIdx]] = [groups[newIdx], groups[idx]];
            render();
        }

        function toggleGroupMode(id) {
            const group = groups.find(g => g.id === id);
            if (!group) return;

            if (group.isRaw) {
                // Switch to structured mode
                let parsed = [];
                try { parsed = JSON.parse(group.rawValue || '[]'); } catch (e) {}
                if (!Array.isArray(parsed)) parsed = [];
                group.isRaw = false;
                group.items = parsed.map(item => typeof item === 'object' && item !== null ? createItem(item) : createItem({}));
                group.rawValue = '';
            } else {
                // Switch to raw mode
                group.isRaw = true;
                group.rawValue = JSON.stringify(buildGroupItems(group), null, 4);
                group.items = [];
            }
            render();
        }

        function updateGroupKey(id, value) {
            const group = groups.find(g => g.id === id);
            if (group) group.key = value;
            generateJson();
        }

        function updateRawValue(id, value) {
            const group = groups.find(g => g.id === id);
            if (group) group.rawValue = value;
            generateJson();
        }

        function addItem(groupId, data = {}) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            group.items.push(createItem(data));
            render();
        }

        function removeItem(groupId, itemId) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            group.items = group.items.filter(i => i.id !== itemId);
            render();
        }

        function moveItem(groupId, itemId, direction) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const idx = group.items.findIndex(i => i.id === itemId);
            if (idx === -1) return;
            const newIdx = idx + direction;
            if (newIdx < 0 || newIdx >= group.items.length) return;
            [group.items[idx], group.items[newIdx]] = [group.items[newIdx], group.items[idx]];
            render();
        }

        function toggleFold(groupId, itemId) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.folded = !item.folded;
            render();
        }

        function expandAllItems(groupId) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            group.items.forEach(i => i.folded = false);
            render();
        }

        function collapseAllItems(groupId) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            group.items.forEach(i => i.folded = true);
            render();
        }

        function updateField(groupId, itemId, field, value) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;

            if (field === 'name' && (!item.code || item.code === generateCode(item.name || ''))) {
                item.code = generateCode(value);
            }

            item[field] = value;
            generateJson();
        }

        function addPriceRow(groupId, itemId) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.added_price.push({ pack_size: '', price_per_card: '' });
            render();
        }

        function removePriceRow(groupId, itemId, idx) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.added_price.splice(idx, 1);
            render();
        }

        function updatePriceRow(groupId, itemId, idx, key, value) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.added_price[idx][key] = value;
            generateJson();
        }

        function addCustomField(groupId, itemId) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.custom.push({ key: '', value: '' });
            render();
        }

        function removeCustomField(groupId, itemId, idx) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.custom.splice(idx, 1);
            render();
        }

        function updateCustomField(groupId, itemId, idx, prop, value) {
            const group = groups.find(g => g.id === groupId);
            if (!group) return;
            const item = group.items.find(i => i.id === itemId);
            if (!item) return;
            item.custom[idx][prop] = value;
            generateJson();
        }

        function buildGroupItems(group) {
            return group.items.map(item => {
                const obj = {};
                for (const key of CORE_FIELDS) {
                    if (item[key] !== undefined && item[key] !== '') obj[key] = item[key];
                }

                if (item.added_price && item.added_price.length > 0) {
                    obj.added_price = item.added_price.map(row => ({
                        pack_size: parseNumberOrString(row.pack_size),
                        price_per_card: parseNumberOrString(row.price_per_card)
                    }));
                }

                for (const cf of item.custom) {
                    if (!cf.key.trim()) continue;
                    obj[cf.key.trim()] = parseCustomValue(cf.value);
                }

                return obj;
            });
        }

        function parseNumberOrString(value) {
            if (value === '' || value === undefined || value === null) return '';
            const trimmed = String(value).trim();
            if (/^-?\d+$/.test(trimmed)) return parseInt(trimmed, 10);
            if (/^-?\d*\.\d+$/.test(trimmed)) return parseFloat(trimmed);
            return value;
        }

        function generateJson() {
            const out = {};

            for (const group of groups) {
                if (!group.key.trim()) continue;
                if (group.isRaw) {
                    try {
                        out[group.key.trim()] = JSON.parse(group.rawValue || '[]');
                    } catch (e) {
                        out[group.key.trim()] = null;
                    }
                } else {
                    out[group.key.trim()] = buildGroupItems(group);
                }
            }

            output.value = JSON.stringify(out, null, 4);
            showValidation('', '');
        }

        function render() {
            if (groups.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <p>暂无分区。</p>
                        <button type="button" class="primary" onclick="addGroup()">创建第一个分区</button>
                    </div>
                `;
                generateJson();
                return;
            }

            container.innerHTML = '';

            for (const group of groups) {
                const groupEl = document.createElement('div');
                groupEl.className = 'option-group';
                groupEl.dataset.groupId = group.id;
                groupEl.draggable = true;

                const header = document.createElement('div');
                header.className = 'group-header';
                header.innerHTML = `
                    <span class="drag-handle" title="拖动以重新排序">⋮⋮</span>
                    <input type="text" data-group-key-input="${group.id}" value="${escapeHtml(group.key)}"
                        oninput="updateGroupKey(${group.id}, this.value)" aria-label="分区名称">
                    ${group.isRaw
                        ? `<span class="pill raw">原始 JSON</span>`
                        : `<span class="pill">${group.items.length} 个项目</span>`}
                    <div class="group-actions">
                        <button type="button" onclick="moveGroup(${group.id}, -1)" title="上移">↑</button>
                        <button type="button" onclick="moveGroup(${group.id}, 1)" title="下移">↓</button>
                        <label class="mode-toggle">
                            <input type="checkbox" ${group.isRaw ? 'checked' : ''} onchange="toggleGroupMode(${group.id})">
                            原始
                        </label>
                        ${!group.isRaw ? `
                            <button type="button" class="ghost" onclick="expandAllItems(${group.id})" title="展开全部">展开</button>
                            <button type="button" class="ghost" onclick="collapseAllItems(${group.id})" title="折叠全部">折叠</button>
                            <button type="button" class="primary" onclick="addItem(${group.id})" title="添加项目">+ 添加项目</button>
                        ` : ''}
                        <button type="button" class="danger" onclick="removeGroup(${group.id})" title="删除分区">删除</button>
                    </div>
                `;

                const body = document.createElement('div');
                body.className = 'group-body';

                if (group.isRaw) {
                    body.innerHTML = `
                        <div class="raw-section">
                            <label>以原始 JSON 数组编辑此分区</label>
                            <textarea oninput="updateRawValue(${group.id}, this.value)" rows="10">${escapeHtml(group.rawValue)}</textarea>
                        </div>
                    `;
                } else if (group.items.length === 0) {
                    body.innerHTML = `
                        <div class="empty-state" style="padding: 18px;">
                            <p style="margin: 0 0 10px;">此分区暂无项目。</p>
                            <button type="button" class="primary" onclick="addItem(${group.id})">添加第一个项目</button>
                        </div>
                    `;
                } else {
                    for (const item of group.items) {
                        const itemEl = document.createElement('div');
                        itemEl.className = 'option-item';
                        itemEl.dataset.itemId = item.id;
                        itemEl.draggable = true;

                        const title = item.name || `Item ${item.id}`;
                        itemEl.innerHTML = `
                            <div class="item-header">
                                <span class="drag-handle" title="拖动以重新排序">⋮⋮</span>
                                <button type="button" class="fold-toggle ${item.folded ? 'folded' : ''}" onclick="toggleFold(${group.id}, ${item.id})" title="${item.folded ? '展开' : '折叠'}">▼</button>
                                <span class="item-title" style="cursor: pointer;" onclick="toggleFold(${group.id}, ${item.id})" title="点击以${item.folded ? '展开' : '折叠'}">${escapeHtml(title)}</span>
                                <div class="group-actions">
                                    <button type="button" onclick="moveItem(${group.id}, ${item.id}, -1)" title="上移">↑</button>
                                    <button type="button" onclick="moveItem(${group.id}, ${item.id}, 1)" title="下移">↓</button>
                                    <button type="button" class="danger" onclick="removeItem(${group.id}, ${item.id})">删除</button>
                                </div>
                            </div>
                            <div class="item-body ${item.folded ? 'folded' : ''}">
                            <div class="field-grid">
                                <div class="field">
                                    <label>名称</label>
                                    <input type="text" value="${escapeHtml(item.name)}" placeholder="例如：标准"
                                        oninput="updateField(${group.id}, ${item.id}, 'name', this.value)">
                                </div>
                                <div class="field">
                                    <label>代码</label>
                                    <input type="text" value="${escapeHtml(item.code)}" placeholder="例如：standard"
                                        oninput="updateField(${group.id}, ${item.id}, 'code', this.value)">
                                </div>
                                <div class="field field-full">
                                    <label>色板图片 / SVG</label>
                                    <div class="swatch-upload-row">
                                        <textarea rows="2" placeholder="/path/to/image.jpg 或 <svg...>"
                                            oninput="updateField(${group.id}, ${item.id}, 'swatch_image', this.value); updateSwatchPreview(${group.id}, ${item.id}, this.value)">${escapeHtml(item.swatch_image)}</textarea>
                                        <label class="file-upload-button">
                                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml"
                                                data-upload="swatch" data-group-id="${group.id}" data-item-id="${item.id}">
                                            <span>上传图片</span>
                                        </label>
                                    </div>
                                    <div class="swatch-preview" data-preview-for="${group.id}-${item.id}">
                                        ${renderSwatchPreview(item.swatch_image)}
                                    </div>
                                </div>
                                <div class="field field-full">
                                    <label>加价表</label>
                                    <table class="price-table">
                                        <thead>
                                            <tr>
                                                <th>包装数量</th>
                                                <th>每张价格</th>
                                                <th style="width: 40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${item.added_price.map((row, idx) => `
                                                <tr>
                                                    <td><input type="text" value="${escapeHtml(row.pack_size)}" placeholder="50"
                                                        oninput="updatePriceRow(${group.id}, ${item.id}, ${idx}, 'pack_size', this.value)"></td>
                                                    <td><input type="text" value="${escapeHtml(row.price_per_card)}" placeholder="0"
                                                        oninput="updatePriceRow(${group.id}, ${item.id}, ${idx}, 'price_per_card', this.value)"></td>
                                                    <td><button type="button" class="danger small" onclick="removePriceRow(${group.id}, ${item.id}, ${idx})" title="删除行">×</button></td>
                                                </tr>
                                            `).join('')}
                                            ${item.added_price.length === 0 ? `<tr><td colspan="3" style="color: var(--muted); font-style: italic;">暂无价格行。</td></tr>` : ''}
                                        </tbody>
                                    </table>
                                    <button type="button" class="small" style="margin-top: 8px;" onclick="addPriceRow(${group.id}, ${item.id})">+ 添加价格行</button>
                                </div>
                            </div>
                            <div class="custom-fields">
                                <div class="section-label">自定义字段</div>
                                ${item.custom.map((cf, idx) => `
                                    <div class="custom-field-row">
                                        <input type="text" value="${escapeHtml(cf.key)}" placeholder="键"
                                            oninput="updateCustomField(${group.id}, ${item.id}, ${idx}, 'key', this.value)">
                                        <input type="text" value="${escapeHtml(cf.value)}" placeholder="值（字符串、数字、true、false 或 JSON）"
                                            oninput="updateCustomField(${group.id}, ${item.id}, ${idx}, 'value', this.value)">
                                        <button type="button" class="danger small" onclick="removeCustomField(${group.id}, ${item.id}, ${idx})" title="删除字段">×</button>
                                    </div>
                                `).join('')}
                                <button type="button" class="small" onclick="addCustomField(${group.id}, ${item.id})">+ 添加自定义字段</button>
                            </div>
                            </div>
                        `;
                        body.appendChild(itemEl);
                    }
                }

                groupEl.appendChild(header);
                groupEl.appendChild(body);
                container.appendChild(groupEl);

                attachGroupDragEvents(groupEl, group.id);
                const itemEls = body.querySelectorAll('.option-item');
                itemEls.forEach((el, idx) => attachItemDragEvents(el, group.id, group.items[idx]?.id));
            }

            // Bottom save bar
            if (groups.length > 0) {
                const saveBar = document.createElement('div');
                saveBar.className = 'card';
                saveBar.style.marginTop = '16px';
                saveBar.innerHTML = `
                    <div class="card-body">
                        <div class="toolbar" style="justify-content: flex-end;">
                            <button type="button" class="primary" onclick="downloadJson()">💾 保存 JSON 到文件</button>
                        </div>
                    </div>
                `;
                container.appendChild(saveBar);
            }

            generateJson();
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Drag-and-drop for groups
        let dragGroupId = null;
        function attachGroupDragEvents(el, groupId) {
            el.addEventListener('dragstart', e => {
                dragGroupId = groupId;
                el.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            el.addEventListener('dragend', () => {
                dragGroupId = null;
                el.classList.remove('dragging');
            });
            el.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            el.addEventListener('drop', e => {
                e.preventDefault();
                if (dragGroupId === null || dragGroupId === groupId) return;
                const fromIdx = groups.findIndex(g => g.id === dragGroupId);
                const toIdx = groups.findIndex(g => g.id === groupId);
                if (fromIdx === -1 || toIdx === -1) return;
                const [moved] = groups.splice(fromIdx, 1);
                groups.splice(toIdx, 0, moved);
                render();
            });
        }

        // Drag-and-drop for items
        let dragItem = null;
        function attachItemDragEvents(el, groupId, itemId) {
            if (!itemId) return;
            el.addEventListener('dragstart', e => {
                dragItem = { groupId, itemId };
                el.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            el.addEventListener('dragend', () => {
                dragItem = null;
                el.classList.remove('dragging');
            });
            el.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            el.addEventListener('drop', e => {
                e.preventDefault();
                if (!dragItem || dragItem.groupId !== groupId || dragItem.itemId === itemId) return;
                const group = groups.find(g => g.id === groupId);
                if (!group) return;
                const fromIdx = group.items.findIndex(i => i.id === dragItem.itemId);
                const toIdx = group.items.findIndex(i => i.id === itemId);
                if (fromIdx === -1 || toIdx === -1) return;
                const [moved] = group.items.splice(fromIdx, 1);
                group.items.splice(toIdx, 0, moved);
                render();
            });
        }

        function clearAll() {
            if (!confirm('确定要清空所有分区和保留的数据吗？')) return;
            groups = [];
            render();
        }

        function copyJson() {
            output.select();
            document.execCommand('copy');
            showValidation('JSON 已复制到剪贴板。', 'success');
        }

        function downloadJson() {
            const blob = new Blob([output.value], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'product-options.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showValidation('下载已开始。', 'success');
        }

        function loadDefaultData() {
            const defaults = {
                paper_type: [
                    {
                        name: 'Standard Paper',
                        code: 'standard_paper',
                        description: 'Classic cardstock'
                    }
                ],
                sizes: [
                    { name: 'Standard', code: 'standard', width: '2.0', height: '3.5', swatch_image: '/images/product-options/business-cards/sizes/business_card-standard-526x325.jpg' },
                    { name: 'Square', code: 'square', width: '2.16', height: '3.3', swatch_image: '/images/product-options/business-cards/sizes/business_card-square-526x325.jpg' }
                ],
                paper_finish: [
                    { name: 'Matte', code: 'matte', description: 'With a smooth feel. Shine-free so no glare.', swatch_image: '/images/product-options/business-cards/laminates/matte-526x251.jpg' },
                    { name: 'Gloss', code: 'gloss', description: 'Eye-catchingly shiny. Makes color photos pop.', swatch_image: '/images/product-options/business-cards/laminates/gloss-526x251.jpg' }
                ],
                corners: [
                    { name: 'Square', code: 'square', description: 'Sharp and Stylish', swatch_image: '<svg class="-large tile-radio-button__icon svg-icon" viewBox="0 0 48 48"><path d="M6.5 6.5V15h3V9.5h29v29H33v3h8.5v-35h-35zm3 27.5h-3v7.5H15v-3H9.5V34zm-3-14h3v9h-3zM20 38.5h8v3h-8z"></path></svg>' },
                    { name: 'Rounded', code: 'rounded', description: '', swatch_image: '<svg class="-large tile-radio-button__icon svg-icon" viewBox="0 0 48 48"><path d="M9.5 34h-3v7.5H15v-3H9.5V34zm-3-14h3v9h-3zM20 38.5h8v3h-8zM8 6.5H6.5V15h3V9.537A30.542 30.542 0 0 1 38.463 38.5H33v3h8.5V40A33.538 33.538 0 0 0 8 6.5z"></path></svg>' }
                ],
                special_finish: [
                    { name: 'no special finish', code: 'no_special_finish', description: 'no special finish, thanks', swatch_image: '/images/product-options/business-cards/special-finishes/no-special-finish-526x251.jpg' },
                    { name: 'spot glass', code: 'spot_glass', description: 'A little shiny, light-reflecting glossiness goes a long way.', swatch_image: '/images/product-options/business-cards/special-finishes/spot_gloss-526x251.jpg' },
                    { name: 'raised spot glass', code: 'raised_spot_glass', description: 'Shininess so thick, you can feel it. This tactile finish brings even the unprinted parts of your design to life.', swatch_image: '/images/product-options/business-cards/special-finishes/raised_spot_gloss-526x251.jpg' },
                    { name: 'gold foil', code: 'gold_foil', description: 'Even the simplest designs dazzle with this extra-special gilded finish.', swatch_image: '/images/product-options/business-cards/special-finishes/gold_foil-526x251.jpg' },
                    { name: 'silver foil', code: 'silver_foil', description: 'Add dazzling, shimmering silver foil accents to any part of your design.', swatch_image: '/images/product-options/business-cards/special-finishes/silver_foil-526x251.jpg' }
                ]
            };

            groups = [];
            nextGroupId = 1;
            nextItemId = 1;

            for (const [key, items] of Object.entries(defaults)) {
                const group = createGroup(key, false);
                for (const itemData of items) {
                    group.items.push(createItem(itemData));
                }
                groups.push(group);
            }

            render();
            showValidation('默认分区已加载。你可以编辑、添加或删除项目。', 'success');
        }

        // Initialize default sections on load.
        loadDefaultData();
    </script>
</body>
</html>
