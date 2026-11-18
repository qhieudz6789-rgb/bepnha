/* ═══════════ BẾP NHÀ — Khung chat (dùng chung cho khách và nhân viên CSKH) ═══════════
 * <div data-chat data-fetch="url lấy tin" data-send="url gửi tin">
 *   <div data-chat-list></div>
 *   <form data-chat-form><input name="body"></form>
 * </div>
 * Lấy tin mới bằng cách gọi data-fetch?after=<id tin cuối> mỗi 3 giây.
 */
window.BepNhaChat = function (root) {
  const list = root.querySelector('[data-chat-list]');
  const form = root.querySelector('[data-chat-form]');
  const input = form.querySelector('[name="body"]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  let lastId = 0;
  let timer = null;
  let busy = false;
  let again = false;

  function add(m) {
    if (m.id <= lastId) return; // đã hiển thị rồi
    lastId = m.id;
    const row = document.createElement('div');
    row.className = 'msg ' + (m.mine ? 'mine' : 'theirs');
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = m.body; // textContent: không bao giờ chạy HTML trong tin nhắn (chống XSS)
    const meta = document.createElement('time');
    meta.textContent = (m.mine ? '' : m.sender + ' · ') + m.time;
    row.append(bubble, meta);
    list.appendChild(row);
    list.scrollTop = list.scrollHeight;
  }

  function showError(text) {
    const row = document.createElement('div');
    row.className = 'msg-error';
    row.textContent = text;
    list.appendChild(row);
    list.scrollTop = list.scrollHeight;
  }

  async function load() {
    if (busy) { again = true; return; }
    busy = true;
    try {
      const res = await fetch(`${root.dataset.fetch}?after=${lastId}`, { headers });
      if (res.ok) (await res.json()).messages.forEach(add);
    } catch (e) { /* mất mạng tạm thời → lần sau thử lại */ }
    busy = false;
    if (again) { again = false; load(); }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = input.value.trim();
    if (!body) return;
    input.value = '';
    input.focus();
    try {
      const res = await fetch(root.dataset.send, {
        method: 'POST',
        headers: { ...headers, 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ body }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(res.status === 429 ? 'Bạn gửi nhanh quá, đợi chút nhé.' : data.message || 'Không gửi được tin nhắn.');
      // Tải lại theo id thay vì chèn thẳng: tránh bỏ sót tin bên kia gửi cùng lúc
      load();
    } catch (err) {
      input.value = body;
      showError(err.message);
    }
  });

  return {
    start() { load(); timer = timer || setInterval(load, 3000); },
    stop() { clearInterval(timer); timer = null; },
  };
};
