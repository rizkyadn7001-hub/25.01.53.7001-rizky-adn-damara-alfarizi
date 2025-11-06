// app.js — simple frontend for canteen backend
const API_BASE = '/canteen/backend/api-crud.php'; // path from web root

const $ = sel => document.querySelector(sel);
const tbody = document.querySelector('#table tbody');
const status = document.getElementById('status');

async function fetchList(q = '') {
  setStatus('Loading...');
  try {
    let url = `${API_BASE}/menu_items`;
    // simple search by name (client-side filtering if backend has no filter)
    const res = await fetch(url);
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    let items = Array.isArray(data) ? data : [];
    if (q) items = items.filter(i => (i.name||'').toLowerCase().includes(q.toLowerCase()));
    render(items);
    setStatus(`Loaded ${items.length} items.`);
  } catch (err) {
    setStatus('Error: ' + err.message);
  }
}

function render(items) {
  tbody.innerHTML = '';
  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="color:#888">No items</td></tr>';
    return;
  }
  for (const it of items) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${escapeHtml(it.id ?? '')}</td>
      <td>${escapeHtml(it.code ?? '')}</td>
      <td>${escapeHtml(it.name ?? '')}</td>
      <td>${escapeHtml(it.price ?? '')}</td>
      <td class="actions">
        <button data-id="${it.id}" class="del">Del</button>
        <button data-id="${it.id}" class="edit">Edit</button>
      </td>
    `;
    tbody.appendChild(tr);
  }
  tbody.querySelectorAll('.del').forEach(b => b.addEventListener('click', onDelete));
  tbody.querySelectorAll('.edit').forEach(b => b.addEventListener('click', onEdit));
}

function escapeHtml(s){ return (s+'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c])); }

async function onDelete(e){
  const id = e.target.dataset.id;
  if (!confirm('Delete item '+id+'?')) return;
  try {
    const res = await fetch(`${API_BASE}/menu_items/${id}`, { method: 'DELETE' });
    const json = await res.json();
    setStatus('Deleted rows: '+json.deleted);
    await fetchList($('#q').value);
  } catch (err) { setStatus('Delete error: '+err.message); }
}

async function onEdit(e){
  const id = e.target.dataset.id;
  const newName = prompt('New name (leave blank = cancel)');
  if (!newName) return;
  try {
    const res = await fetch(`${API_BASE}/menu_items/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type':'application/json' },
      body: JSON.stringify({ name: newName })
    });
    const json = await res.json();
    setStatus('Updated rows: '+json.updated_rows);
    await fetchList($('#q').value);
  } catch (err) { setStatus('Update error: '+err.message); }
}

async function onCreateDemo() {
  const demo = { code:'M' + Math.floor(Math.random()*9999), name:'Demo '+Date.now(), description:'added by demo', price: 10000 };
  try {
    const res = await fetch(`${API_BASE}/menu_items`, {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(demo)
    });
    const json = await res.json();
    setStatus('Created id: ' + json.id);
    await fetchList($('#q').value);
  } catch (err) { setStatus('Create error: '+err.message); }
}

function setStatus(msg){ status.textContent = msg; }

document.getElementById('refresh').addEventListener('click', ()=>fetchList($('#q').value));
document.getElementById('createBtn').addEventListener('click', onCreateDemo);
document.getElementById('q').addEventListener('input', (e)=>fetchList(e.target.value));

// initial load
fetchList();
