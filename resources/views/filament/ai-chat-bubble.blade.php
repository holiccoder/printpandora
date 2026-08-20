{{-- AI 客服人工接管气泡：轮询 /ai/chat/admin/* 接口，红点提示等待回复的会话 --}}
<div id="ai-admin-bubble" translate="no" class="notranslate">
    <style>
        #ai-admin-bubble .aib-launcher {
            position: fixed; right: 20px; bottom: 20px; z-index: 60;
            width: 52px; height: 52px; border-radius: 9999px;
            background: #800020; color: #fff; display: flex; align-items: center;
            justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,.25);
            border: none; cursor: pointer;
        }
        #ai-admin-bubble .aib-badge {
            position: absolute; top: -4px; right: -4px; min-width: 20px; height: 20px;
            border-radius: 9999px; background: #dc2626; color: #fff; font-size: 11px;
            font-weight: 700; display: flex; align-items: center; justify-content: center;
            padding: 0 5px;
        }
        #ai-admin-bubble .aib-panel {
            position: fixed; right: 20px; bottom: 84px; z-index: 60;
            width: 380px; max-width: calc(100vw - 40px); height: 540px; max-height: 70vh;
            background: #fff; border: 1px solid #e5e5e5; border-radius: 12px;
            box-shadow: 0 16px 48px rgba(0,0,0,.25); display: flex; flex-direction: column;
            overflow: hidden; font-size: 14px;
        }
        #ai-admin-bubble .aib-head {
            padding: 10px 14px; background: #800020; color: #fff; font-weight: 700;
            display: flex; align-items: center; justify-content: space-between;
        }
        #ai-admin-bubble .aib-list { flex: 1; overflow-y: auto; }
        #ai-admin-bubble .aib-item {
            width: 100%; text-align: left; padding: 10px 14px; border: none;
            border-bottom: 1px solid #f0f0f0; background: #fff; cursor: pointer;
        }
        #ai-admin-bubble .aib-item:hover { background: #faf7f2; }
        #ai-admin-bubble .aib-item .aib-waiting { color: #dc2626; font-weight: 700; font-size: 11px; }
        #ai-admin-bubble .aib-msgs { flex: 1; overflow-y: auto; padding: 12px; }
        #ai-admin-bubble .aib-msg {
            max-width: 85%; padding: 8px 10px; border-radius: 8px; margin-bottom: 8px;
            white-space: pre-wrap; word-break: break-word; font-size: 13px;
        }
        #ai-admin-bubble .aib-msg.user { background: #f0f0f0; }
        #ai-admin-bubble .aib-msg.admin { background: #800020; color: #fff; margin-left: auto; }
        #ai-admin-bubble .aib-msg.assistant { background: #eef2f7; color: #555; }
        #ai-admin-bubble .aib-msg .aib-who { font-size: 10px; font-weight: 700; opacity: .7; margin-bottom: 2px; text-transform: uppercase; }
        #ai-admin-bubble .aib-msg img { max-height: 120px; border-radius: 6px; margin-top: 4px; }
        #ai-admin-bubble .aib-input {
            display: flex; gap: 6px; padding: 8px; border-top: 1px solid #f0f0f0;
        }
        #ai-admin-bubble .aib-input input {
            flex: 1; border: 1px solid #e5e5e5; border-radius: 8px; padding: 8px 10px; font-size: 13px;
        }
        #ai-admin-bubble .aib-input button {
            background: #800020; color: #fff; border: none; border-radius: 8px;
            padding: 0 14px; cursor: pointer; font-size: 13px;
        }
        #ai-admin-bubble .aib-back { background: none; border: none; color: #fff; cursor: pointer; font-size: 12px; padding: 0; }
        #ai-admin-bubble .aib-resolve {
            background: none; border: none; color: #ffd9e2; cursor: pointer; font-size: 12px; padding: 0;
            text-decoration: underline;
        }
        #ai-admin-bubble .aib-empty { padding: 24px; text-align: center; color: #999; font-size: 13px; }
        #ai-admin-bubble .dark .aib-panel, .dark #ai-admin-bubble .aib-panel { background: #1f1f1f; border-color: #333; color: #eee; }
        .dark #ai-admin-bubble .aib-item { background: #1f1f1f; border-color: #2a2a2a; color: #eee; }
        .dark #ai-admin-bubble .aib-msg.user { background: #2a2a2a; }
        .dark #ai-admin-bubble .aib-msg.assistant { background: #252a32; color: #aaa; }
        .dark #ai-admin-bubble .aib-input input { background: #2a2a2a; border-color: #333; color: #eee; }
    </style>

    <button type="button" class="aib-launcher" id="aib-launcher" aria-label="AI 客服会话">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:24px;height:24px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
        </svg>
        <span class="aib-badge" id="aib-badge" style="display:none">0</span>
    </button>

    <div class="aib-panel" id="aib-panel" style="display:none">
        <div class="aib-head">
            <span id="aib-title">客服会话</span>
            <span>
                <button type="button" class="aib-resolve" id="aib-takeover" style="display:none">接管此会话</button>
                <button type="button" class="aib-resolve" id="aib-resolve" style="display:none">转回 AI</button>
                <button type="button" class="aib-back" id="aib-back" style="display:none">← 返回列表</button>
            </span>
        </div>
        <div class="aib-list" id="aib-list"></div>
        <div class="aib-msgs" id="aib-msgs" style="display:none"></div>
        <form class="aib-input" id="aib-form" style="display:none">
            <input type="text" id="aib-text" placeholder="输入回复…" maxlength="2000" autocomplete="off">
            <button type="submit">发送</button>
        </form>
    </div>

    <script>
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]');
            csrf = csrf ? csrf.getAttribute('content') : '';
            var state = { open: false, currentId: null, lastId: 0, timer: null };

            function $(id) { return document.getElementById(id); }

            function api(path, options) {
                options = options || {};
                options.headers = Object.assign({ 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, options.headers || {});
                return fetch('/ai/chat/admin/' + path, options).then(function (r) {
                    if (!r.ok) throw new Error('request failed: ' + r.status);
                    return r.json();
                });
            }

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }

            function isImage(url) { return /\.(jpe?g|png|gif|webp)$/i.test(url || ''); }

            function renderList(conversations) {
                var list = $('aib-list');

                if (!conversations.length) {
                    list.innerHTML = '<div class="aib-empty">暂无客户会话</div>';
                    return;
                }

                list.innerHTML = conversations.map(function (c) {
                    return '<button type="button" class="aib-item" data-id="' + c.id + '" data-mode="' + c.mode + '">' +
                        '<div style="display:flex;justify-content:space-between;gap:8px">' +
                        '<strong>' + esc(c.customer) + '</strong>' +
                        '<span style="font-size:11px;font-weight:700;' + (c.mode === 'human' ? 'color:#800020' : 'color:#999') + '">' +
                        (c.mode === 'human' ? '人工' : 'AI') + '</span>' +
                        (c.waiting ? '<span class="aib-waiting">待回复</span>' : '') +
                        '</div>' +
                        '<div style="color:#888;font-size:12px;margin-top:2px">' + esc(c.last_message || '(附件)') + '</div>' +
                        '</button>';
                }).join('');

                list.querySelectorAll('.aib-item').forEach(function (el) {
                    el.addEventListener('click', function () {
                        openConversation(parseInt(el.dataset.id, 10), el.dataset.mode);
                    });
                });
            }

            function renderMessages(messages) {
                var box = $('aib-msgs');
                var html = messages.map(function (m) {
                    var who = m.role === 'user' ? '客户' : (m.role === 'admin' ? '我' : 'AI');
                    var att = '';
                    if (m.attachment_url) {
                        att = isImage(m.attachment_url)
                            ? '<a href="' + esc(m.attachment_url) + '" target="_blank"><img src="' + esc(m.attachment_url) + '"></a>'
                            : '<a href="' + esc(m.attachment_url) + '" target="_blank" style="font-size:12px;text-decoration:underline">📎 ' + esc(m.attachment_name || '附件') + '</a>';
                    }
                    return '<div class="aib-msg ' + m.role + '"><div class="aib-who">' + who + '</div>' + esc(m.content) + att + '</div>';
                }).join('');

                box.insertAdjacentHTML('beforeend', html);
                box.scrollTop = box.scrollHeight;
            }

            function refreshBadge() {
                api('conversations').then(function (data) {
                    var badge = $('aib-badge');
                    if (data.waiting_count > 0) {
                        badge.textContent = data.waiting_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }

                    if (state.open && state.currentId === null) {
                        renderList(data.conversations);
                    }
                }).catch(function () {});
            }

            function refreshMessages() {
                if (state.currentId === null) return;
                api('conversations/' + state.currentId + '/messages?after_id=' + state.lastId)
                    .then(function (data) {
                        if (data.messages.length) {
                            state.lastId = data.messages[data.messages.length - 1].id;
                            renderMessages(data.messages);
                        }
                    }).catch(function () {});
            }

            function openConversation(id, mode) {
                state.currentId = id;
                state.currentMode = mode || 'ai';
                state.lastId = 0;
                $('aib-list').style.display = 'none';
                $('aib-msgs').style.display = 'block';
                $('aib-msgs').innerHTML = '';
                $('aib-form').style.display = state.currentMode === 'human' ? 'flex' : 'none';
                $('aib-back').style.display = 'inline';
                $('aib-resolve').style.display = state.currentMode === 'human' ? 'inline' : 'none';
                $('aib-takeover').style.display = state.currentMode === 'human' ? 'none' : 'inline';
                $('aib-title').textContent = '会话 #' + id;
                refreshMessages();
            }

            function showList() {
                state.currentId = null;
                $('aib-msgs').style.display = 'none';
                $('aib-form').style.display = 'none';
                $('aib-back').style.display = 'none';
                $('aib-resolve').style.display = 'none';
                $('aib-takeover').style.display = 'none';
                $('aib-list').style.display = 'block';
                $('aib-title').textContent = '客服会话';
                refreshBadge();
            }

            $('aib-launcher').addEventListener('click', function () {
                state.open = !state.open;
                $('aib-panel').style.display = state.open ? 'flex' : 'none';
                if (state.open) showList();
            });

            $('aib-back').addEventListener('click', showList);

            $('aib-resolve').addEventListener('click', function () {
                if (state.currentId === null) return;
                api('conversations/' + state.currentId + '/resolve', { method: 'POST' })
                    .then(function () { showList(); })
                    .catch(function () {});
            });

            $('aib-takeover').addEventListener('click', function () {
                if (state.currentId === null) return;
                api('conversations/' + state.currentId + '/takeover', { method: 'POST' })
                    .then(function () { openConversation(state.currentId, 'human'); })
                    .catch(function () {});
            });

            $('aib-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var input = $('aib-text');
                var text = input.value.trim();
                if (!text || state.currentId === null) return;

                api('conversations/' + state.currentId + '/reply', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text }),
                }).then(function (data) {
                    input.value = '';
                    state.lastId = Math.max(state.lastId, data.message.id);
                    renderMessages([data.message]);
                }).catch(function () {});
            });

            refreshBadge();
            state.timer = window.setInterval(function () {
                refreshBadge();
                if (state.open && state.currentId !== null) refreshMessages();
            }, 5000);
        })();
    </script>
</div>
