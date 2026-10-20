# Part I: Analysis and Design Document
## Student Scholarship Monitoring and Academic Compliance System
**SAD Laboratory Midterm Examination | Section A | Student ID: M-007823**

---

## A1. Investigation and Planning

### Problem Statement
The Scholarship Office currently relies on a fragmented combination of spreadsheets, email attachments, and paper-based grade records to administer Merit Scholar and Community Scholar grants. This manual approach results in duplicate semester entries, unverified grades, missed deadlines, and inconsistent scholarship renewal decisions. Scholars lack visibility into whether their submissions have been received, verified, or require corrections, leading to delayed renewals and administrative burden.

### Problem-Cause-Effect Analysis

| # | Problem | Cause | Effect |
|---|---------|-------|--------|
| 1 | Duplicate semester entries exist | No unique constraint validation in spreadsheets | Staff waste time reconciling conflicting records; scholars may receive incorrect evaluations |
| 2 | Unverified grades are used in decisions | No formal verification workflow before evaluation | Scholarship decisions are based on potentially inaccurate data, exposing the office to audit risk |
| 3 | Missed submission deadlines go undetected | Manual tracking of deadline compliance via email timestamps | Late submissions are processed as on-time, violating policy fairness |
| 4 | Inconsistent renewal decisions | Policy thresholds are manually applied per staff member without standardized checks | Different staff may approve or deny the same academic record, causing inequitable outcomes |

### Scope and Exclusions

**In Scope:**
- Scholar registration and scholarship program assignment
- Semester grade submission with validation and duplicate prevention
- Evidence verification workflow (Verified / Returned / Resubmit)
- Automated compliance evaluation against persisted program policies
- Staff-controlled renewal decisions with audit logging
- Dashboard with statistics, search, and filtering
- Role-based access control (Scholar and Staff)

**Exclusions:**
- Integration with live Registrar API for official grade data
- SMS/email notification gateway
- Payment processing or disbursement modules
- Machine learning-based grade prediction
- Full appeal workflow for contested decisions
- Multi-institution or cross-campus support

### Development Tasks

| # | Task | Duration |
|---|------|----------|
| 1 | Database schema design, Supabase project setup, and RLS policies | 15 min |
| 2 | Authentication scaffolding and role-based access enforcement | 10 min |
| 3 | Scholar management, grade submission, and verification workflow | 50 min |
| 4 | Compliance evaluation engine, renewal decisions, dashboard, and testing | 75 min |

### Risks and Responses

| # | Risk | Likelihood | Impact | Response |
|---|------|-----------|--------|----------|
| 1 | Network connectivity failure preventing Supabase access during exam | Medium | High | Prepare local database fallback; maintain a local runnable copy with error documentation |
| 2 | Supabase rate limiting during bulk seed operations | Low | Medium | Implement request throttling with 200-300ms delays between API calls; use batch insert where possible |

---

## A2. Stakeholders and Elicitation

### Stakeholders

| # | Stakeholder | Type | Role Description |
|---|------------|------|------------------|
| 1 | **Scholar** | Actual Actor | Submits semester academic summaries, provides grade document references, views submission status and compliance results |
| 2 | **Scholarship Staff** | Actual Actor | Verifies evidence, evaluates policy compliance, records renewal decisions, manages scholar assignments |
| 3 | **Registrar Office** | External Stakeholder | Maintains official grade records; source of truth for academic data (not a system actor) |
| 4 | **Scholarship Office Director** | Stakeholder | Oversees scholarship policy, reviews aggregate compliance reports, authorizes policy changes |

### Elicitation Questions

1. **Verification Process**: "What specific elements of a grade document reference do you check when verifying a scholar's submitted evidence, and how do you currently handle documents that are partially legible or incomplete?"

2. **Deadline Handling**: "How should the system handle submissions that arrive at the exact deadline boundary (e.g., 5:00:00 PM)? Should timezone differences between the scholar's device and server time be considered?"

3. **Policy Exceptions**: "Under what circumstances, if any, can a staff member override a 'With Deficiency' compliance result to approve renewal? Should exception workflows be documented for audit purposes?"

4. **Access Levels**: "Beyond the Scholar and Staff roles, are there supervisory roles that should have read-only access to all decisions without the ability to modify them? Should scholars see the specific policy thresholds they are evaluated against?"

5. **Reporting Requirements**: "What periodic reports does the Scholarship Office Director require (e.g., end-of-semester summary of compliant vs. deficient scholars by program)? Should these be exportable?"

6. **Historical Records**: "When a scholar's submission is returned for correction and resubmitted, should the system preserve the original submission data alongside the corrected version, or replace it while logging the change in an audit trail?"

---

## A3. Requirements and Rules

### Functional Requirements

| ID | Requirement | Actor | Testable Criteria |
|----|-------------|-------|-------------------|
| FR01 | The system shall authenticate users with email and password and redirect them to role-appropriate dashboards | Scholar, Staff | Login with valid credentials redirects to correct dashboard; invalid credentials show error |
| FR02 | Staff shall register new scholars with full name, student ID, email, and password | Staff | New scholar account appears in user list and can log in |
| FR03 | Staff shall assign a scholar to one of two persisted scholarship programs for a given academic year | Staff | Assignment record created; program thresholds readable from program table |
| FR04 | Scholars shall submit semester academic summaries including GWA, enrolled units, failed/incomplete/dropped counts, and evidence reference | Scholar | Submission record created with all fields populated and timestamp recorded |
| FR05 | The system shall reject duplicate submissions for the same scholar, academic year, and semester combination | Scholar | Second submission for same period returns error; first submission unchanged |
| FR06 | The system shall validate that GWA is between 1.00 and 5.00 and all counts are non-negative integers | Scholar, System | Invalid values (GWA 0.50, negative count) rejected with clear message |
| FR07 | Staff shall mark submission evidence as Verified or Returned with a mandatory reason for returns | Staff | Verification status updated; returned submissions include reason text |
| FR08 | Staff shall evaluate compliance of verified submissions against program policy thresholds | Staff | Compliance status set to Compliant or With Deficiency with itemized reasons |
| FR09 | Staff shall record renewal decisions (Approved or Held) only for evaluated submissions; Approved requires Compliant status | Staff | Compliant records can be Approved; deficient records can only be Held |
| FR10 | The dashboard shall display total assigned scholars, pending verification count, compliant count, and deficiency count filtered by semester | Staff | Correct counts displayed matching database records for selected semester |

### Non-Functional Requirements

| ID | Requirement | Measurable Criteria |
|----|-------------|-------------------|
| NFR01 | The system shall respond to user actions within 3 seconds under normal network conditions | 95% of API calls complete in < 3000ms measured by browser developer tools |
| NFR02 | The system shall enforce role-based access at the API/backend level, not merely by hiding UI elements | Direct API call from scholar account to staff-only endpoint returns HTTP 403 |
| NFR03 | The system shall persist all data across browser refreshes without data loss | Refresh browser after any operation; all data intact and displayed correctly |
| NFR04 | The system shall log all verification, evaluation, and renewal actions with actor identity and timestamp | Audit log table contains entries for every state change with actor_id, actor_name, and created_at |

### Business Rules

| ID | Rule |
|----|------|
| BR01 | A Merit Scholar must maintain a GWA of 1.75 or below, enroll in at least 18 units, and have zero failed, incomplete, or dropped subjects |
| BR02 | A Community Scholar must maintain a GWA of 2.25 or below, enroll in at least 15 units, have zero failed and incomplete subjects, and at most 1 dropped subject |
| BR03 | A submission at the exact deadline (2026-10-30 17:00:00 PHT) is considered on time; any submission after is late and results in a deficiency |
| BR04 | Evidence must be verified before compliance evaluation can proceed; unverified evidence blocks evaluation |
| BR05 | Staff cannot approve renewal for a submission with compliance status "With Deficiency"; only "Held" is permitted for deficient records |
| BR06 | A returned submission may be corrected and resubmitted by the scholar; upon resubmission, the verification status resets to Pending and previous evaluation/renewal decisions are cleared |

---

## A4. UML Use Case Diagram

```
┌──────────────────────────────────────────────────────────────────────┐
│                SCHOLARSHIP MONITORING SYSTEM                        │
│                                                                      │
│    ┌──────────────┐                                                  │
│    │  Register     │◄─────────────────── Staff                       │
│    │  Scholar      │                      │                          │
│    └──────────────┘                      │                          │
│                                          │                          │
│    ┌──────────────┐                      │                          │
│    │  Assign       │◄────────────────────┤                          │
│    │  Program      │                      │                          │
│    └──────────────┘                      │                          │
│                                          │                          │
│    ┌──────────────┐                      │                          │
│    │  Submit       │◄──── Scholar        │                          │
│    │  Grades       │        │            │                          │
│    └───────┬──────┘        │            │                          │
│            │ «include»     │            │                          │
│            ▼               │            │                          │
│    ┌──────────────┐        │            │                          │
│    │  Validate     │        │            │                          │
│    │  Input        │        │            │                          │
│    └──────────────┘        │            │                          │
│                            │            │                          │
│    ┌──────────────┐        │            │                          │
│    │  View Own     │◄───────┘            │                          │
│    │  Submissions  │                      │                          │
│    └──────────────┘                      │                          │
│                                          │                          │
│    ┌──────────────┐                      │                          │
│    │  Verify       │◄────────────────────┤                          │
│    │  Evidence     │                      │                          │
│    └───────┬──────┘                      │                          │
│            │ «extend»                    │                          │
│            ▼                             │                          │
│    ┌──────────────┐                      │                          │
│    │  Return for   │                      │                          │
│    │  Correction   │                      │                          │
│    └──────────────┘                      │                          │
│                                          │                          │
│    ┌──────────────┐                      │                          │
│    │  Evaluate     │◄────────────────────┤                          │
│    │  Compliance   │                      │                          │
│    └───────┬──────┘                      │                          │
│            │ «include»                   │                          │
│            ▼                             │                          │
│    ┌──────────────┐                      │                          │
│    │  Check Policy │                      │                          │
│    │  Thresholds   │                      │                          │
│    └──────────────┘                      │                          │
│                                          │                          │
│    ┌──────────────┐                      │                          │
│    │  Record       │◄────────────────────┤                          │
│    │  Renewal      │                      │                          │
│    │  Decision     │                      │                          │
│    └──────────────┘                      │                          │
│                                          │                          │
│    ┌──────────────┐                      │                          │
│    │  View         │◄────────────────────┘                          │
│    │  Dashboard    │                                                 │
│    └──────────────┘                                                  │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Relationship Justifications

**Include Relationships:**

1. **Submit Grades → «include» → Validate Input**: Every grade submission *must* include input validation (GWA range 1.00–5.00, non-negative integers, required evidence, duplicate check). This is mandatory reused behavior that cannot be skipped — without validation, the submission cannot proceed. The validation logic is also reused when a scholar resubmits a corrected record.

2. **Evaluate Compliance → «include» → Check Policy Thresholds**: Every compliance evaluation *must* include checking the scholar's academic data against the assigned program's policy thresholds (max GWA, min units, allowed counts, deadline). This is mandatory behavior that produces the Compliant/With Deficiency result. The policy thresholds are read from the persisted program record, not hardcoded.

**Extend Relationship:**

1. **Verify Evidence → «extend» → Return for Correction**: The base use case is verifying evidence (marking it Verified). At the extension point where the staff determines evidence is insufficient, the *optional* behavior of returning it for correction is triggered. This is conditional — it only occurs when evidence fails review, not on every verification. The extending use case adds the behavior of recording a return reason, clearing any prior evaluation, and allowing the scholar to resubmit.

---

## A5. Detailed Use Cases and Data Design

### UC01: Submit Grades

| Element | Description |
|---------|-------------|
| **Use Case ID** | UC01 |
| **Use Case Name** | Submit Semester Academic Summary |
| **Actor** | Scholar |
| **Trigger** | Scholar navigates to the grade submission page and initiates a new submission |
| **Preconditions** | Scholar is authenticated (FR01); Scholar has an active scholarship assignment for the selected academic year (FR03) |
| **Linked Requirements** | FR04, FR05, FR06, BR03, BR06 |

**Main Flow:**
1. Scholar selects academic year and semester from dropdown menus.
2. Scholar enters GWA (numeric, 1.00–5.00).
3. Scholar enters enrolled units (non-negative integer).
4. Scholar enters failed subjects count, incomplete grades count, and dropped subjects count (all non-negative integers).
5. Scholar enters grade document reference text.
6. Scholar clicks "Submit Academic Summary."
7. System validates all input fields (FR06).
8. System checks for duplicate submission for the same scholar, academic year, and semester (FR05).
9. System retrieves the scholar's assignment and program deadline.
10. System compares submission timestamp against the program deadline in Asia/Manila time (BR03).
11. System creates the submission record with verification_status = "Pending" and records the timestamp.
12. System displays success confirmation and redirects to dashboard.

**Alternative Flows:**
- **A1 (Resubmission after Return):** At step 8, if a submission exists with verification_status = "Returned", the system updates the existing record with new data, resets verification_status to "Pending", clears evaluation and renewal fields, and logs the correction (BR06).

**Exception Flows:**
- **E1 (Invalid GWA):** At step 7, if GWA < 1.00 or GWA > 5.00, display error "GWA must be between 1.00 and 5.00." Submission is blocked.
- **E2 (Negative Count):** At step 7, if any count is negative, display error. Submission is blocked.
- **E3 (Duplicate Exists - Not Returned):** At step 8, if a non-returned submission exists for the period, display "Duplicate submission rejected." Submission is blocked.
- **E4 (No Active Assignment):** At step 9, if no active assignment found, display "No active scholarship assignment found." Submission is blocked.

**Postcondition:** A new or corrected submission record exists in the database with Pending verification status, recorded timestamp, and late flag calculated.

---

### UC02: Evaluate Compliance

| Element | Description |
|---------|-------------|
| **Use Case ID** | UC02 |
| **Use Case Name** | Evaluate Academic Compliance |
| **Actor** | Scholarship Staff |
| **Trigger** | Staff clicks "Evaluate Compliance" on a verified submission detail view |
| **Preconditions** | Staff is authenticated with "staff" role (FR01, NFR02); Submission verification_status = "Verified" (BR04) |
| **Linked Requirements** | FR08, BR01, BR02, BR03, BR04 |

**Main Flow:**
1. Staff selects a verified submission to review.
2. System retrieves the submission data and the linked scholarship program's policy thresholds.
3. Staff clicks "Evaluate Compliance."
4. System checks GWA against program max_gwa (BR01/BR02).
5. System checks enrolled_units against program min_units.
6. System checks failed_count against program allowed_failed.
7. System checks incomplete_count against program allowed_incomplete.
8. System checks dropped_count against program allowed_dropped.
9. System checks is_late flag against deadline compliance (BR03).
10. If all checks pass, system sets compliance_status = "Compliant" with reason "All policy requirements met."
11. System records evaluator ID and timestamp.
12. System creates audit log entry with detailed check results.
13. System displays compliance results with pass/fail indicators for each check.

**Alternative Flows:**
- **A1 (Deficiency Found):** At steps 4–9, if any check fails, system sets compliance_status = "With Deficiency" and records all specific failure reasons as a concatenated string (e.g., "GWA 1.76 exceeds maximum allowed 1.75; Submission was late").

**Exception Flows:**
- **E1 (Not Verified):** At step 2, if verification_status ≠ "Verified", system displays "Cannot evaluate. Submission must be verified first." Evaluation is blocked.
- **E2 (Access Denied):** If the requesting user's role is not "staff", system returns HTTP 403 (NFR02).

**Postcondition:** Submission record has compliance_status (Compliant or With Deficiency), itemized compliance_reasons, evaluator identity, and evaluation timestamp. Audit log records the evaluation action.

---

### Entity-Relationship Diagram (ERD)

```
┌───────────────────┐       ┌─────────────────────────┐
│   user_profiles   │       │  scholarship_programs    │
├───────────────────┤       ├─────────────────────────┤
│ PK id (UUID)      │       │ PK id (UUID)            │
│    auth_id (UUID) │       │    program_name (TEXT)   │
│    email (TEXT)    │       │    max_gwa (NUMERIC)    │
│    full_name (TEXT)│       │    min_units (INT)      │
│    role (TEXT)     │       │    allowed_failed (INT) │
│    student_id(TEXT)│       │    allowed_incomplete   │
│    created_at     │       │    allowed_dropped (INT)│
└────────┬──────────┘       │    requires_evidence    │
         │                  │    submission_deadline   │
         │ 1                │    created_at           │
         │                  └────────────┬────────────┘
         │                               │ 1
         │            ┌──────────────────┘
         │            │
         ▼ M          ▼ M
┌────────────────────────────────┐
│      scholar_assignments       │
├────────────────────────────────┤
│ PK id (UUID)                   │
│ FK scholar_id → user_profiles  │
│ FK program_id → scholarship_   │
│    programs                    │
│    academic_year (TEXT)        │
│ FK assigned_by → user_profiles │
│    assigned_at (TIMESTAMPTZ)   │
│    status (TEXT)               │
│ UQ (scholar_id, program_id,   │
│     academic_year)             │
└──────────────┬─────────────────┘
               │ 1
               │
               ▼ M
┌──────────────────────────────────────┐
│       semester_submissions           │
├──────────────────────────────────────┤
│ PK id (UUID)                         │
│ FK scholar_id → user_profiles        │
│ FK assignment_id → scholar_          │
│    assignments                       │
│    academic_year (TEXT)              │
│    semester (INT: 1,2,3)            │
│    gwa (NUMERIC 3,2)               │
│    enrolled_units (INT)             │
│    failed_count (INT)               │
│    incomplete_count (INT)           │
│    dropped_count (INT)              │
│    evidence_reference (TEXT)        │
│    submitted_at (TIMESTAMPTZ)       │
│    is_late (BOOLEAN)                │
│    verification_status (TEXT)       │
│    verification_notes (TEXT)        │
│ FK verified_by → user_profiles      │
│    verified_at (TIMESTAMPTZ)        │
│    compliance_status (TEXT)         │
│    compliance_reasons (TEXT)        │
│ FK evaluated_by → user_profiles     │
│    evaluated_at (TIMESTAMPTZ)       │
│    renewal_decision (TEXT)          │
│    renewal_notes (TEXT)             │
│ FK decided_by → user_profiles       │
│    decided_at (TIMESTAMPTZ)         │
│ UQ (scholar_id, academic_year,      │
│     semester)                        │
└──────────────┬───────────────────────┘
               │ 1
               │
               ▼ M
┌──────────────────────────────────────┐
│           audit_log                  │
├──────────────────────────────────────┤
│ PK id (UUID)                         │
│ FK submission_id → semester_         │
│    submissions                       │
│    action (TEXT)                     │
│ FK actor_id → user_profiles          │
│    actor_name (TEXT)                 │
│    details (TEXT)                    │
│    previous_value (TEXT)            │
│    new_value (TEXT)                  │
│    created_at (TIMESTAMPTZ)         │
└──────────────────────────────────────┘
```

**Cardinalities:**
- One **user_profile** can have many **scholar_assignments** (1:M)
- One **scholarship_program** can have many **scholar_assignments** (1:M)
- One **scholar_assignment** can have many **semester_submissions** (1:M)
- One **semester_submission** can have many **audit_log** entries (1:M)
- Unique constraint on (scholar_id, academic_year, semester) prevents duplicates

---

## A6. Traceability Matrix

| FR ID | Actor | Use Case | Business Rule | Screen/Function | Test Case | Expected Result |
|-------|-------|----------|---------------|-----------------|-----------|-----------------|
| FR04 | Scholar | UC01: Submit Grades | BR03 (deadline) | `scholar/submit.php` → `api/handler.php?action=submit_grades` | TC01, TC07 | Submission created with timestamp; late flag set correctly |
| FR05 | Scholar | UC01: Submit Grades | BR06 (resubmit) | `api/handler.php` duplicate check | TC07, TC09 | Duplicate rejected; returned record can be resubmitted |
| FR08 | Staff | UC02: Evaluate Compliance | BR01, BR02, BR04 | `staff/submissions.php` → `api/handler.php?action=evaluate_compliance` | TC01–TC06 | Compliance result matches policy thresholds; blocked if unverified |
| FR09 | Staff | UC03: Record Renewal | BR05 | `staff/submissions.php` → `api/handler.php?action=renewal_decision` | TC06, TC10 | Approved only if Compliant; Held allowed for any evaluated record |

### Planned but Not Implemented
- **Registrar API Integration**: FR relating to pulling official grades directly from Registrar system — excluded from exam scope
- **Email/SMS Notifications**: Automated notification when verification status changes — excluded from scope
- **Appeal Workflow**: Formal process for scholars to contest decisions — excluded from scope
