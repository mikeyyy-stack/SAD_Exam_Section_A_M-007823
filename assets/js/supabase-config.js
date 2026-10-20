// Supabase Configuration
const SUPABASE_URL = 'https://sfyukkrrnjbtpejegqdb.supabase.co';
const SUPABASE_ANON_KEY = 'sb_publishable_uc8XWz93hsWbzetr_53tPg_8nww1MzB';
const _supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

function getProfile() {
    return JSON.parse(localStorage.getItem('user_profile') || '{}');
}

async function requireAuth(role) {
    const { data: { session } } = await _supabase.auth.getSession();
    if (!session) { window.location.href = 'index.html'; return null; }
    const p = getProfile();
    if (role && p.role !== role) { window.location.href = 'index.html'; return null; }
    return p;
}

async function logout() {
    await _supabase.auth.signOut();
    localStorage.removeItem('user_profile');
    window.location.href = 'index.html';
}

function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function statusBadge(status) {
    const c = { 'Pending':'badge-pending','Verified':'badge-verified','Returned':'badge-returned','Compliant':'badge-compliant','With Deficiency':'badge-deficiency','Approved':'badge-approved','Held':'badge-held' };
    return `<span class="badge ${c[status]||'badge-info'}">${esc(status)}</span>`;
}

function fmtDate(d) { return d ? new Date(d).toLocaleString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—'; }
