/* ═══════════ BẾP NHÀ — AJAX & giao diện ═══════════
 * Form có thuộc tính data-ajax="..." sẽ được gửi bằng fetch (không tải lại trang).
 * Nếu trình duyệt tắt JavaScript, form vẫn submit bình thường → server redirect.
 */
document.documentElement.classList.add('js');

(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const loginUrl = document.body.dataset.loginUrl;
  const toastBox = document.getElementById('toasts');

  /* ── Toast ── */
  function toast(message, type = 'success') {
    if (!toastBox) return;
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', 'status');
    el.textContent = message;
    toastBox.appendChild(el);
    autoHide(el);
  }
  function autoHide(el) {
    setTimeout(() => el.classList.add('hide'), 3200);
    setTimeout(() => el.remove(), 3600);
  }
  toastBox?.querySelectorAll('.toast').forEach(autoHide); // toast từ session (sau redirect)

  /* ── Cập nhật số trên icon giỏ hàng ── */
  function setCartCount(count) {
    const el = document.getElementById('cart-count');
    if (!el) return;
    el.textContent = count;
    el.classList.remove('bump');
    void el.offsetWidth; // khởi động lại animation
    el.classList.add('bump');
  }

  /* ── Gửi form bằng fetch, nhận JSON ── */
  async function send(form) {
    const res = await fetch(form.action, {
      method: 'POST', // PATCH/DELETE được giả lập bằng trường _method có sẵn trong form (@method)
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(form),
    });
    if (res.status === 401) {
      location.href = loginUrl;
      throw new Error('Chưa đăng nhập');
    }
    if (res.status === 419) {
      throw new Error('Phiên làm việc đã hết hạn, vui lòng tải lại trang.');
    }
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
    if (data.count !== undefined) setCartCount(data.count);
    return data;
  }

  function setCartTotals(total) {
    document.querySelectorAll('[data-cart-total]').forEach((el) => (el.textContent = total));
  }

  /* Xử lý kết quả theo loại form */
  const onSuccess = {
    'cart-add': (form, data) => {
      toast(data.message);
      if (form.closest('.card') && form.quantity) form.quantity.value = 1; // thẻ món ngoài thực đơn: về lại 1
    },
    'cart-update': (form, data) => {
      form.closest('[data-cart-item]').querySelector('[data-subtotal]').textContent = data.subtotal;
      form.dataset.last = form.quantity.value;
      setCartTotals(data.total);
    },
    'cart-remove': (form, data) => {
      const row = form.closest('[data-cart-item]');
      row.classList.add('removing');
      setTimeout(() => {
        row.remove();
        if (data.count === 0) location.reload(); // giỏ trống → hiện trạng thái trống
      }, 300);
      setCartTotals(data.total);
      toast(data.message);
    },
  };

  document.addEventListener('submit', async (e) => {
    const form = e.target;
    const kind = form.dataset.ajax;
    if (!kind) return;
    e.preventDefault();

    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
      onSuccess[kind]?.(form, await send(form));
    } catch (err) {
      toast(err.message, 'error');
      if (kind === 'cart-update' && form.dataset.last) form.quantity.value = form.dataset.last; // trả lại số cũ
    } finally {
      if (btn) btn.disabled = false;
    }
  });

  /* ── Nút +/− số lượng ── */
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-qty]');
    if (!btn) return;
    const input = btn.closest('.qty').querySelector('input');
    btn.dataset.qty === '+' ? input.stepUp() : input.stepDown();
    const form = input.form;
    if (form?.dataset.ajax === 'cart-update') form.requestSubmit();
  });
  document.addEventListener('change', (e) => {
    const form = e.target.form;
    if (form?.dataset.ajax === 'cart-update' && e.target.name === 'quantity') form.requestSubmit();
  });
  document.querySelectorAll('form[data-ajax="cart-update"]').forEach((f) => (f.dataset.last = f.quantity.value));

  /* ── Tìm kiếm tức thì trên trang Thực đơn (debounce 400ms) ── */
  const searchForm = document.querySelector('[data-live-search]');
  if (searchForm) {
    const results = document.getElementById('product-results');
    let timer;
    let controller;
    const run = () => {
      const params = new URLSearchParams(new FormData(searchForm));
      [...params.keys()].forEach((k) => params.get(k) === '' && params.delete(k));
      const url = `${searchForm.action}${params.toString() ? '?' + params : ''}`;
      controller?.abort(); // huỷ request cũ nếu người dùng gõ tiếp
      controller = new AbortController();
      results.classList.add('loading');
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
        .then((r) => r.text())
        .then((html) => {
          results.innerHTML = html;
          history.replaceState(null, '', url);
        })
        .catch(() => {})
        .finally(() => results.classList.remove('loading'));
    };
    searchForm.addEventListener('input', (e) => {
      if (e.target.name !== 'q') return;
      clearTimeout(timer);
      timer = setTimeout(run, 400);
    });
    searchForm.addEventListener('change', (e) => e.target.name === 'sort' && run());
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      clearTimeout(timer);
      run();
    });
  }

  /* ── Khung chat CSKH (góc phải dưới) ── */
  const chatCount = document.getElementById('chat-count');
  const widget = document.getElementById('chat-widget');
  let chatOpen = false;
  const setBadge = (el, n) => {
    if (!el) return;
    el.textContent = n;
    el.hidden = !n;
  };
  if (widget) {
    const panel = widget.querySelector('.chat-panel');
    const box = widget.querySelector('[data-chat]');
    const chat = box && window.BepNhaChat ? window.BepNhaChat(box) : null;
    const openChat = (open) => {
      chatOpen = open;
      panel.hidden = !open;
      widget.classList.toggle('open', open);
      if (open) {
        chat?.start();
        setBadge(chatCount, 0);
        panel.querySelector('input')?.focus();
      } else {
        chat?.stop();
      }
    };
    document.getElementById('chat-toggle').addEventListener('click', () => openChat(!chatOpen));
    widget.querySelector('.chat-close').addEventListener('click', () => openChat(false));
    document.querySelectorAll('[data-open-chat]').forEach((b) => b.addEventListener('click', () => openChat(true)));
  }

  /* ── Thông báo đơn hàng: hỏi server mỗi 10 giây ── */
  const pollUrl = document.body.dataset.pollUrl;
  const notifList = document.getElementById('notif-list');
  if (pollUrl && notifList) {
    const notifCount = document.getElementById('notif-count');
    const seen = new Set([...notifList.querySelectorAll('[data-id]')].map((el) => el.dataset.id));

    const renderItem = (n) => {
      const a = document.createElement('a');
      a.href = n.url;
      a.className = 'notif-item' + (n.read ? '' : ' unread');
      a.dataset.id = n.id;
      const ic = document.createElement('span');
      ic.className = 'ic';
      ic.textContent = n.icon;
      const body = document.createElement('div');
      const title = document.createElement('strong');
      title.textContent = n.title;
      const msg = document.createElement('p');
      msg.textContent = n.message;
      const time = document.createElement('time');
      time.textContent = n.time;
      body.append(title, msg, time);
      a.append(ic, body);
      return a;
    };

    const poll = async () => {
      try {
        const res = await fetch(pollUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return;
        const data = await res.json();
        setBadge(notifCount, data.unread);
        setBadge(chatCount, chatOpen ? 0 : data.chatUnread);

        const fresh = data.items.filter((n) => !seen.has(n.id));
        if (!fresh.length) return;
        notifList.replaceChildren(...data.items.map(renderItem));
        fresh.reverse().forEach((n) => {
          seen.add(n.id);
          if (n.read) return;
          toast(`${n.icon} ${n.title} — ${n.message}`);
          // Đang xem đúng đơn đó → tải lại để thấy trạng thái mới
          if (String(n.order_id) === document.body.dataset.orderId) setTimeout(() => location.reload(), 2500);
        });
      } catch (e) { /* mất mạng tạm thời → lần sau thử lại */ }
    };
    setInterval(poll, 10000);
  }

  // Bấm ra ngoài thì đóng khung thông báo
  document.addEventListener('click', (e) => {
    document.querySelectorAll('details.notif[open]').forEach((d) => !d.contains(e.target) && d.removeAttribute('open'));
  });

  /* ── Menu mobile ── */
  document.querySelector('.menu-toggle')?.addEventListener('click', () => document.body.classList.toggle('nav-open'));

  /* ── Điền nhanh tài khoản demo ở trang đăng nhập ── */
  document.querySelectorAll('[data-demo]').forEach((btn) =>
    btn.addEventListener('click', () => {
      const [email, pass] = btn.dataset.demo.split('|');
      document.getElementById('email').value = email;
      document.getElementById('password').value = pass;
    })
  );
})();
