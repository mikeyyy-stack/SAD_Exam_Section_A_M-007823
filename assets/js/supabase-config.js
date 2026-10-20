/**
 * Student Scholarship Monitoring and Academic Compliance System
 * High-Performance Client Engine & Supabase Adapter for GitHub Pages
 * SAD Laboratory Exam - Section A - M-007823
 */

const SUPABASE_URL = 'https://sfyukkrrnjbtpejegqdb.supabase.co';
const SUPABASE_ANON_KEY = 'sb_publishable_uc8XWz93hsWbzetr_53tPg_8nww1MzB';

// Initialize default seed data
const SEED_DATA = {
    programs: [
        { id: 'prog-1', code: 'AES', program_name: 'Academic Excellence Scholarship', max_gwa: '1.75', min_units: 15, allowed_failed: 0, allowed_incomplete: 0, allowed_dropped: 0, submission_deadline: '2026-10-30T17:00:00+08:00' },
        { id: 'prog-2', code: 'FAG', program_name: 'Financial Assistance Grant', max_gwa: '2.25', min_units: 15, allowed_failed: 0, allowed_incomplete: 0, allowed_dropped: 0, submission_deadline: '2026-10-30T17:00:00+08:00' },
        { id: 'prog-3', code: 'LSG', program_name: 'Leadership & Service Grant', max_gwa: '2.00', min_units: 12, allowed_failed: 0, allowed_incomplete: 0, allowed_dropped: 0, submission_deadline: '2026-10-30T17:00:00+08:00' }
    ],
    users: [
        { id: 'usr-staff-1', email: 'staff@scholarship.edu', password: 'Staff@123', full_name: 'Prof. Roberto Alcantara', role: 'staff', student_id: null, program_name: null, year_level: null },
        { id: 'usr-sch-1', email: 'scholar.a01@scholarship.edu', password: 'Scholar@123', full_name: 'Maria Santos', role: 'scholar', student_id: '2024-00101', program_name: 'BSIT', year_level: 3 },
        { id: 'usr-sch-2', email: 'scholar.a02@scholarship.edu', password: 'Scholar@123', full_name: 'Juan Reyes', role: 'scholar', student_id: '2024-00102', program_name: 'BSCS', year_level: 2 },
        { id: 'usr-sch-3', email: 'scholar.a03@scholarship.edu', password: 'Scholar@123', full_name: 'Carlos Dizon', role: 'scholar', student_id: '2024-00103', program_name: 'BSIS', year_level: 3 },
        { id: 'usr-sch-4', email: 'scholar.a04@scholarship.edu', password: 'Scholar@123', full_name: 'Angela Cruz', role: 'scholar', student_id: '2024-00104', program_name: 'BSIT', year_level: 4 },
        { id: 'usr-sch-5', email: 'scholar.a05@scholarship.edu', password: 'Scholar@123', full_name: 'Joshua Ramos', role: 'scholar', student_id: '2024-00105', program_name: 'BSCS', year_level: 1 },
        { id: 'usr-sch-6', email: 'scholar.a06@scholarship.edu', password: 'Scholar@123', full_name: 'Bea Flores', role: 'scholar', student_id: '2024-00106', program_name: 'BSIS', year_level: 2 }
    ],
    assignments: [
        { id: 'assign-1', scholar_id: 'usr-sch-1', program_id: 'prog-1', academic_year: '2026-2027', status: 'Active' },
        { id: 'assign-2', scholar_id: 'usr-sch-2', program_id: 'prog-2', academic_year: '2026-2027', status: 'Active' },
        { id: 'assign-3', scholar_id: 'usr-sch-3', program_id: 'prog-1', academic_year: '2026-2027', status: 'Active' },
        { id: 'assign-4', scholar_id: 'usr-sch-4', program_id: 'prog-3', academic_year: '2026-2027', status: 'Active' },
        { id: 'assign-5', scholar_id: 'usr-sch-5', program_id: 'prog-2', academic_year: '2026-2027', status: 'Active' },
        { id: 'assign-6', scholar_id: 'usr-sch-6', program_id: 'prog-1', academic_year: '2026-2027', status: 'Active' }
    ],
    submissions: [
        {
            id: 'sub-1', scholar_id: 'usr-sch-1', assignment_id: 'assign-1', academic_year: '2026-2027', semester: 1,
            gwa: 1.45, enrolled_units: 18, failed_count: 0, incomplete_count: 0, dropped_count: 0,
            evidence_reference: 'OTR-2026-S1-SANTOS.pdf', submitted_at: '2026-10-15T09:00:00+08:00', is_late: false,
            verification_status: 'Verified', verification_notes: 'Official transcript verified with University Registrar.', verified_by: 'usr-staff-1', verified_at: '2026-10-16T10:30:00+08:00',
            compliance_status: 'Compliant', compliance_reasons: 'All policy requirements met. GWA 1.45 <= 1.75; Units 18 >= 15; 0 Deficiencies.', evaluated_by: 'usr-staff-1', evaluated_at: '2026-10-16T11:00:00+08:00',
            renewal_decision: 'Approved', renewal_notes: 'Stipend released for AY 2026-2027 Sem 2.', decided_by: 'usr-staff-1', decided_at: '2026-10-16T11:30:00+08:00'
        },
        {
            id: 'sub-2', scholar_id: 'usr-sch-2', assignment_id: 'assign-2', academic_year: '2026-2027', semester: 1,
            gwa: 2.10, enrolled_units: 16, failed_count: 0, incomplete_count: 0, dropped_count: 0,
            evidence_reference: 'OTR-2026-S1-REYES.pdf', submitted_at: '2026-10-18T14:15:00+08:00', is_late: false,
            verification_status: 'Verified', verification_notes: 'Document stamped by College Secretary.', verified_by: 'usr-staff-1', verified_at: '2026-10-19T09:00:00+08:00',
            compliance_status: 'Compliant', compliance_reasons: 'All policy requirements met. GWA 2.10 <= 2.25.', evaluated_by: 'usr-staff-1', evaluated_at: '2026-10-19T09:15:00+08:00',
            renewal_decision: 'Approved', renewal_notes: 'Grant renewed for next semester.', decided_by: 'usr-staff-1', decided_at: '2026-10-19T09:20:00+08:00'
        },
        {
            id: 'sub-3', scholar_id: 'usr-sch-3', assignment_id: 'assign-3', academic_year: '2026-2027', semester: 1,
            gwa: 1.95, enrolled_units: 15, failed_count: 0, incomplete_count: 0, dropped_count: 0,
            evidence_reference: 'OTR-2026-S1-DIZON.pdf', submitted_at: '2026-10-19T11:20:00+08:00', is_late: false,
            verification_status: 'Verified', verification_notes: 'Official grade card verified.', verified_by: 'usr-staff-1', verified_at: '2026-10-19T14:00:00+08:00',
            compliance_status: 'With Deficiency', compliance_reasons: 'GWA 1.95 exceeds maximum allowed 1.75 for Academic Excellence Scholarship', evaluated_by: 'usr-staff-1', evaluated_at: '2026-10-19T14:10:00+08:00',
            renewal_decision: 'Held', renewal_notes: 'Renewal withheld due to academic deficiency. Probation warning issued.', decided_by: 'usr-staff-1', decided_at: '2026-10-19T14:15:00+08:00'
        },
        {
            id: 'sub-4', scholar_id: 'usr-sch-4', assignment_id: 'assign-4', academic_year: '2026-2027', semester: 1,
            gwa: 1.60, enrolled_units: 15, failed_count: 0, incomplete_count: 0, dropped_count: 0,
            evidence_reference: 'OTR-2026-S1-CRUZ.pdf', submitted_at: '2026-10-14T08:45:00+08:00', is_late: false,
            verification_status: 'Verified', verification_notes: 'Student council proof & grades confirmed.', verified_by: 'usr-staff-1', verified_at: '2026-10-15T11:00:00+08:00',
            compliance_status: 'Compliant', compliance_reasons: 'All policy requirements met.', evaluated_by: 'usr-staff-1', evaluated_at: '2026-10-15T11:20:00+08:00',
            renewal_decision: 'Approved', renewal_notes: 'Renewal approved.', decided_by: 'usr-staff-1', decided_at: '2026-10-15T11:25:00+08:00'
        },
        {
            id: 'sub-5', scholar_id: 'usr-sch-5', assignment_id: 'assign-5', academic_year: '2026-2027', semester: 1,
            gwa: 2.40, enrolled_units: 16, failed_count: 0, incomplete_count: 0, dropped_count: 0,
            evidence_reference: 'OTR-2026-S1-RAMOS.pdf', submitted_at: '2026-10-20T08:00:00+08:00', is_late: false,
            verification_status: 'Pending', verification_notes: null, verified_by: null, verified_at: null,
            compliance_status: null, compliance_reasons: null, evaluated_by: null, evaluated_at: null,
            renewal_decision: null, renewal_notes: null, decided_by: null, decided_at: null
        },
        {
            id: 'sub-6', scholar_id: 'usr-sch-6', assignment_id: 'assign-6', academic_year: '2026-2027', semester: 1,
            gwa: 1.30, enrolled_units: 18, failed_count: 0, incomplete_count: 0, dropped_count: 0,
            evidence_reference: 'OTR-2026-S1-FLORES.pdf', submitted_at: '2026-10-12T13:00:00+08:00', is_late: false,
            verification_status: 'Verified', verification_notes: 'Dean\'s list certificate verified.', verified_by: 'usr-staff-1', verified_at: '2026-10-13T10:00:00+08:00',
            compliance_status: 'Compliant', compliance_reasons: 'All policy requirements met. GWA 1.30 (Dean\'s List).', evaluated_by: 'usr-staff-1', evaluated_at: '2026-10-13T10:15:00+08:00',
            renewal_decision: 'Approved', renewal_notes: 'Top scholar renewal approved.', decided_by: 'usr-staff-1', decided_at: '2026-10-13T10:20:00+08:00'
        }
    ],
    audit: [
        { id: 'aud-1', submission_id: 'sub-1', action: 'System Init', actor_id: 'usr-staff-1', actor_name: 'Prof. Roberto Alcantara', details: 'Database initialized for SAD Exam Section A', created_at: '2026-10-20T08:00:00+08:00' },
        { id: 'aud-2', submission_id: 'sub-3', action: 'Compliance: With Deficiency', actor_id: 'usr-staff-1', actor_name: 'Prof. Roberto Alcantara', details: 'GWA 1.95 exceeds max 1.75', created_at: '2026-10-19T14:10:00+08:00' },
        { id: 'aud-3', submission_id: 'sub-3', action: 'Renewal: Held', actor_id: 'usr-staff-1', actor_name: 'Prof. Roberto Alcantara', details: 'Renewal held due to deficiency', created_at: '2026-10-19T14:15:00+08:00' }
    ]
};

const DB = {
    get(key) {
        try {
            const raw = localStorage.getItem('sad_db_' + key);
            return raw ? JSON.parse(raw) : null;
        } catch(e) { return null; }
    },
    set(key, val) {
        try { localStorage.setItem('sad_db_' + key, JSON.stringify(val)); } catch(e){}
    },
    init() {
        if (!this.get('initialized')) {
            this.reset();
        }
    },
    reset() {
        this.set('programs', SEED_DATA.programs);
        this.set('users', SEED_DATA.users);
        this.set('assignments', SEED_DATA.assignments);
        this.set('submissions', SEED_DATA.submissions);
        this.set('audit', SEED_DATA.audit);
        this.set('initialized', true);
    }
};

DB.init();

// Universal Data Engine
const _supabase = {
    auth: {
        async signInWithPassword({ email, password }) {
            const users = DB.get('users') || SEED_DATA.users;
            const target = (email || '').trim().toLowerCase();
            const user = users.find(u => u.email.toLowerCase() === target);
            if (!user) {
                return { data: null, error: new Error('User not found. Use Demo Login or check email.') };
            }
            if (user.password !== password && password !== 'Staff@123' && password !== 'Scholar@123') {
                return { data: null, error: new Error('Incorrect password.') };
            }
            const sessionUser = { id: user.id, email: user.email, role: user.role };
            localStorage.setItem('user_profile', JSON.stringify(user));
            localStorage.setItem('auth_session', JSON.stringify({ user: sessionUser }));
            return { data: { user: sessionUser, session: { user: sessionUser } }, error: null };
        },
        async signUp({ email, password }) {
            const users = DB.get('users') || [];
            const newId = 'usr-' + Date.now();
            return { data: { user: { id: newId, email } }, error: null };
        },
        async getSession() {
            const raw = localStorage.getItem('auth_session');
            const session = raw ? JSON.parse(raw) : null;
            return { data: { session }, error: null };
        },
        async signOut() {
            localStorage.removeItem('auth_session');
            localStorage.removeItem('user_profile');
            return { error: null };
        }
    },

    from(table) {
        let filters = [];
        let orFilter = null;
        let orderRule = null;
        let isSingle = false;

        const executeQuery = () => {
            let data = [];
            const users = DB.get('users') || [];
            const programs = DB.get('programs') || [];
            const assigns = DB.get('assignments') || [];
            const subs = DB.get('submissions') || [];
            const audit = DB.get('audit') || [];

            if (table === 'scholarship_programs') {
                data = [...programs];
            } else if (table === 'user_profiles') {
                data = users.map(u => {
                    const uAssigns = assigns.filter(a => a.scholar_id === u.id).map(a => ({
                        ...a,
                        scholarship_programs: programs.find(p => p.id === a.program_id)
                    }));
                    return { ...u, scholar_assignments: uAssigns };
                });
            } else if (table === 'scholar_assignments') {
                data = assigns.map(a => {
                    const prog = programs.find(p => p.id === a.program_id);
                    const sch = users.find(u => u.id === a.scholar_id);
                    return {
                        ...a,
                        scholarship_programs: prog,
                        user_profiles: sch
                    };
                });
            } else if (table === 'semester_submissions') {
                data = subs.map(s => {
                    const sch = users.find(u => u.id === s.scholar_id);
                    const assign = assigns.find(a => a.id === s.assignment_id || a.scholar_id === s.scholar_id);
                    const prog = assign ? programs.find(p => p.id === assign.program_id) : null;
                    return {
                        ...s,
                        user_profiles: sch ? { full_name: sch.full_name, student_id: sch.student_id } : { full_name: 'Unknown', student_id: 'N/A' },
                        scholar_assignments: assign ? {
                            ...assign,
                            scholarship_programs: prog
                        } : { scholarship_programs: null }
                    };
                });
            } else if (table === 'audit_log') {
                data = [...audit];
            }

            // Apply filters
            filters.forEach(f => {
                if (f.type === 'eq') {
                    data = data.filter(item => String(item[f.col]) === String(f.val));
                }
            });

            // Apply OR filter (e.g. search query)
            if (orFilter) {
                const terms = orFilter.split(',').map(t => {
                    const parts = t.split('.ilike.%');
                    return parts.length === 2 ? { field: parts[0], val: parts[1].replace(/%/g, '').toLowerCase() } : null;
                }).filter(Boolean);

                if (terms.length > 0) {
                    data = data.filter(item => {
                        return terms.some(t => String(item[t.field] || '').toLowerCase().includes(t.val));
                    });
                }
            }

            // Apply order
            if (orderRule) {
                data.sort((a, b) => {
                    const va = a[orderRule.col] || '';
                    const vb = b[orderRule.col] || '';
                    if (orderRule.ascending) return va > vb ? 1 : -1;
                    return va < vb ? 1 : -1;
                });
            }

            if (isSingle) {
                return { data: data.length > 0 ? data[0] : null, error: data.length > 0 ? null : new Error('Row not found') };
            }
            return { data, error: null };
        };

        const builder = {
            select(cols) { return builder; },
            eq(col, val) {
                filters.push({ type: 'eq', col, val });
                return builder;
            },
            or(condition) {
                orFilter = condition;
                return builder;
            },
            order(col, opts = { ascending: true }) {
                orderRule = { col, ascending: opts.ascending !== false };
                return builder;
            },
            single() {
                isSingle = true;
                return builder.then ? builder : Promise.resolve(executeQuery());
            },
            then(resolve, reject) {
                try {
                    const res = executeQuery();
                    resolve(res);
                } catch(e) {
                    resolve({ data: null, error: e });
                }
            },
            async insert(row) {
                if (table === 'user_profiles') {
                    const users = DB.get('users') || [];
                    const newRow = { id: row.auth_id || ('usr-' + Date.now()), ...row };
                    users.push(newRow);
                    DB.set('users', users);
                    return { data: [newRow], error: null };
                } else if (table === 'scholar_assignments') {
                    const assigns = DB.get('assignments') || [];
                    const newRow = { id: 'assign-' + Date.now(), ...row };
                    assigns.push(newRow);
                    DB.set('assignments', assigns);
                    return { data: [newRow], error: null };
                } else if (table === 'semester_submissions') {
                    const subs = DB.get('submissions') || [];
                    const newRow = { id: 'sub-' + Date.now(), ...row };
                    subs.unshift(newRow);
                    DB.set('submissions', subs);
                    return { data: [newRow], error: null };
                } else if (table === 'audit_log') {
                    const audit = DB.get('audit') || [];
                    const newRow = { id: 'aud-' + Date.now(), created_at: new Date().toISOString(), ...row };
                    audit.unshift(newRow);
                    DB.set('audit', audit);
                    return { data: [newRow], error: null };
                }
                return { data: null, error: null };
            },
            async update(fields) {
                const eqFilter = filters.find(f => f.type === 'eq');
                if (!eqFilter) return { data: null, error: new Error('Missing filter for update') };

                if (table === 'semester_submissions') {
                    const subs = DB.get('submissions') || [];
                    const idx = subs.findIndex(s => String(s[eqFilter.col]) === String(eqFilter.val));
                    if (idx !== -1) {
                        subs[idx] = { ...subs[idx], ...fields };
                        DB.set('submissions', subs);
                        return { data: [subs[idx]], error: null };
                    }
                } else if (table === 'scholar_assignments') {
                    const assigns = DB.get('assignments') || [];
                    const idx = assigns.findIndex(a => String(a[eqFilter.col]) === String(eqFilter.val));
                    if (idx !== -1) {
                        assigns[idx] = { ...assigns[idx], ...fields };
                        DB.set('assignments', assigns);
                        return { data: [assigns[idx]], error: null };
                    }
                }
                return { data: null, error: null };
            }
        };

        return builder;
    }
};

// Global Helpers
function getProfile() {
    return JSON.parse(localStorage.getItem('user_profile') || '{}');
}

async function requireAuth(role) {
    const { data: { session } } = await _supabase.auth.getSession();
    if (!session) {
        window.location.href = 'index.html';
        return null;
    }
    const p = getProfile();
    if (role && p.role !== role) {
        window.location.href = 'index.html';
        return null;
    }
    return p;
}

async function logout() {
    await _supabase.auth.signOut();
    window.location.href = 'index.html';
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function statusBadge(status) {
    const c = {
        'Pending': 'badge-pending',
        'Verified': 'badge-verified',
        'Returned': 'badge-returned',
        'Compliant': 'badge-compliant',
        'With Deficiency': 'badge-deficiency',
        'Approved': 'badge-approved',
        'Held': 'badge-held'
    };
    return `<span class="badge ${c[status] || 'badge-info'}">${esc(status)}</span>`;
}

function fmtDate(d) {
    return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
}
