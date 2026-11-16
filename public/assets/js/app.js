(() => {
  const csrf = document.querySelector('meta[name="csrf"]')?.content || '';

  window.toast = (msg, ok = true) => {
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${ok ? 'success' : 'danger'} border-0`;
    el.innerHTML = `<div class="d-flex"><div class="toast-body"></div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    el.querySelector('.toast-body').textContent = msg;
    document.getElementById('toasts').appendChild(el);
    new bootstrap.Toast(el, { delay: 4000 }).show();
  };

  window.api = async (rota, { method = 'GET', params = {}, body = null } = {}) => {
    const qs = new URLSearchParams({ r: rota, ...params });
    try {
      const r = await fetch('api.php?' + qs, {
        method, credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, Accept: 'application/json' },
        body: body ? JSON.stringify(body) : undefined,
      });
      const j = await r.json().catch(() => ({ success: false, message: 'Resposta inválida do servidor.' }));
      if (r.status === 401 && rota !== 'auth/login') location.href = 'login.php';
      return j;
    } catch { return { success: false, message: 'Falha de comunicação com o servidor.' }; }
  };

  // Formularios genericos: <form data-route="produtos" [data-redirect]>; campo id preenchido => PUT
  document.querySelectorAll('form[data-route]').forEach(form => {
    form.addEventListener('submit', async ev => {
      ev.preventDefault();
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      const data = Object.fromEntries(new FormData(form));
      const id = data.id; delete data.id;
      const btn = form.querySelector('[type=submit]'); btn.disabled = true;
      const res = await api(form.dataset.route, { method: id ? 'PUT' : 'POST', params: id ? { id } : {}, body: data });
      toast(res.message, res.success);
      if (res.success) {
        if (form.dataset.redirect) location.href = form.dataset.redirect;
        else setTimeout(() => location.reload(), 700);
      } else btn.disabled = false;
    });
  });

  // Modais: [data-new] limpa o formulario; [data-edit='{json}'] preenche
  const prepararModal = (btn, dados) => {
    const modal = document.querySelector(btn.dataset.bsTarget);
    const form = modal.querySelector('form');
    form.reset(); form.classList.remove('was-validated');
    form.elements.id && (form.elements.id.value = '');
    modal.querySelectorAll('[data-only-new]').forEach(x => { x.disabled = !!dados; });
    modal.querySelectorAll('[data-only-edit]').forEach(x => x.classList.toggle('d-none', !dados));
    modal.querySelector('.modal-title').textContent = dados ? (btn.dataset.tituloEdit || 'Editar') : (btn.dataset.titulo || 'Novo');
    if (dados) Object.entries(dados).forEach(([k, v]) => { if (form.elements[k]) form.elements[k].value = v ?? ''; });
    if (dados && form.elements.id) form.elements.id.value = dados.id_produto ?? '';
    if (btn.dataset.tipo && form.elements.tipo) form.elements.tipo.value = btn.dataset.tipo;
  };
  document.querySelectorAll('[data-new]').forEach(b => b.addEventListener('click', () => prepararModal(b, null)));
  document.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => prepararModal(b, JSON.parse(b.dataset.edit))));

  document.querySelectorAll('[data-inativar]').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('Inativar este produto? Ele deixará de aceitar movimentações (o histórico é mantido).')) return;
    const res = await api('produtos', { method: 'DELETE', params: { id: b.dataset.inativar } });
    toast(res.message, res.success);
    if (res.success) setTimeout(() => location.reload(), 700);
  }));

  document.getElementById('menuBtn')?.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('open'));
})();
