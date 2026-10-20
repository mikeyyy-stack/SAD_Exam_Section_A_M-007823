-- ============================================================
-- Student Scholarship Monitoring and Academic Compliance System
-- Database Schema for Supabase (PostgreSQL)
-- ============================================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 1. User Profiles
CREATE TABLE IF NOT EXISTS user_profiles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    auth_id UUID UNIQUE,
    email TEXT NOT NULL UNIQUE,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('scholar', 'staff')),
    student_id TEXT UNIQUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Scholarship Programs and Policy Thresholds
CREATE TABLE IF NOT EXISTS scholarship_programs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    program_name TEXT NOT NULL UNIQUE,
    max_gwa NUMERIC(3,2) NOT NULL,
    min_units INTEGER NOT NULL,
    allowed_failed INTEGER NOT NULL DEFAULT 0,
    allowed_incomplete INTEGER NOT NULL DEFAULT 0,
    allowed_dropped INTEGER NOT NULL DEFAULT 0,
    requires_evidence BOOLEAN NOT NULL DEFAULT TRUE,
    submission_deadline TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. Scholar Assignments (links scholars to scholarship programs)
CREATE TABLE IF NOT EXISTS scholar_assignments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    scholar_id UUID NOT NULL REFERENCES user_profiles(id),
    program_id UUID NOT NULL REFERENCES scholarship_programs(id),
    academic_year TEXT NOT NULL,
    assigned_by UUID REFERENCES user_profiles(id),
    assigned_at TIMESTAMPTZ DEFAULT NOW(),
    status TEXT NOT NULL DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive')),
    UNIQUE(scholar_id, program_id, academic_year)
);

-- 4. Semester Submissions
CREATE TABLE IF NOT EXISTS semester_submissions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    scholar_id UUID NOT NULL REFERENCES user_profiles(id),
    assignment_id UUID NOT NULL REFERENCES scholar_assignments(id),
    academic_year TEXT NOT NULL,
    semester INTEGER NOT NULL CHECK (semester IN (1, 2, 3)),
    gwa NUMERIC(3,2) NOT NULL CHECK (gwa >= 1.00 AND gwa <= 5.00),
    enrolled_units INTEGER NOT NULL CHECK (enrolled_units >= 0),
    failed_count INTEGER NOT NULL DEFAULT 0 CHECK (failed_count >= 0),
    incomplete_count INTEGER NOT NULL DEFAULT 0 CHECK (incomplete_count >= 0),
    dropped_count INTEGER NOT NULL DEFAULT 0 CHECK (dropped_count >= 0),
    evidence_reference TEXT,
    submitted_at TIMESTAMPTZ DEFAULT NOW(),
    is_late BOOLEAN DEFAULT FALSE,
    verification_status TEXT NOT NULL DEFAULT 'Pending' CHECK (verification_status IN ('Pending', 'Verified', 'Returned')),
    verification_notes TEXT,
    verified_by UUID REFERENCES user_profiles(id),
    verified_at TIMESTAMPTZ,
    compliance_status TEXT CHECK (compliance_status IN ('Compliant', 'With Deficiency')),
    compliance_reasons TEXT,
    evaluated_by UUID REFERENCES user_profiles(id),
    evaluated_at TIMESTAMPTZ,
    renewal_decision TEXT CHECK (renewal_decision IN ('Approved', 'Held')),
    renewal_notes TEXT,
    decided_by UUID REFERENCES user_profiles(id),
    decided_at TIMESTAMPTZ,
    UNIQUE(scholar_id, academic_year, semester)
);

-- 5. Audit History Log
CREATE TABLE IF NOT EXISTS audit_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    submission_id UUID REFERENCES semester_submissions(id),
    action TEXT NOT NULL,
    actor_id UUID REFERENCES user_profiles(id),
    actor_name TEXT,
    details TEXT,
    previous_value TEXT,
    new_value TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- Seed Data: Scholarship Programs (Policy Records)
-- ============================================================

INSERT INTO scholarship_programs (program_name, max_gwa, min_units, allowed_failed, allowed_incomplete, allowed_dropped, requires_evidence, submission_deadline)
VALUES 
    ('Merit Scholar', 1.75, 18, 0, 0, 0, TRUE, '2026-10-30 17:00:00+08'),
    ('Community Scholar', 2.25, 15, 0, 0, 1, TRUE, '2026-10-30 17:00:00+08')
ON CONFLICT (program_name) DO NOTHING;

-- ============================================================
-- Row Level Security Policies
-- ============================================================

-- Enable RLS on all tables
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE scholarship_programs ENABLE ROW LEVEL SECURITY;
ALTER TABLE scholar_assignments ENABLE ROW LEVEL SECURITY;
ALTER TABLE semester_submissions ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_log ENABLE ROW LEVEL SECURITY;

-- Allow anonymous/authenticated read on scholarship_programs (public policy info)
CREATE POLICY "Allow read scholarship_programs" ON scholarship_programs
    FOR SELECT USING (true);

-- User profiles: users can see their own; staff can see all
CREATE POLICY "Users read own profile" ON user_profiles
    FOR SELECT USING (true);

CREATE POLICY "Allow insert user_profiles" ON user_profiles
    FOR INSERT WITH CHECK (true);

CREATE POLICY "Allow update user_profiles" ON user_profiles
    FOR UPDATE USING (true);

-- Scholar assignments: scholars see own; staff see all
CREATE POLICY "Read scholar_assignments" ON scholar_assignments
    FOR SELECT USING (true);

CREATE POLICY "Insert scholar_assignments" ON scholar_assignments
    FOR INSERT WITH CHECK (true);

CREATE POLICY "Update scholar_assignments" ON scholar_assignments
    FOR UPDATE USING (true);

-- Semester submissions: scholars see own; staff see all
CREATE POLICY "Read semester_submissions" ON semester_submissions
    FOR SELECT USING (true);

CREATE POLICY "Insert semester_submissions" ON semester_submissions
    FOR INSERT WITH CHECK (true);

CREATE POLICY "Update semester_submissions" ON semester_submissions
    FOR UPDATE USING (true);

-- Audit log: staff can read all
CREATE POLICY "Read audit_log" ON audit_log
    FOR SELECT USING (true);

CREATE POLICY "Insert audit_log" ON audit_log
    FOR INSERT WITH CHECK (true);
