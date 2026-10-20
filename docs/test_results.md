# Test Results - TC01 to TC10
## Student Scholarship Monitoring and Academic Compliance System
**Student ID: M-007823**

---

| Test ID | Input / Action | Expected Result | Actual Result | Pass/Fail | Evidence |
|---------|---------------|-----------------|---------------|-----------|----------|
| **TC01** | Evaluate record A01 (Merit, GWA=1.75, Units=18, F=0, I=0, D=0, at deadline) | Compliant — all checks pass at boundary values | Compliant — "All policy requirements met." | **PASS** | Staff > Submissions > Review A01 > Evaluate Compliance |
| **TC02** | Evaluate record A02 (Merit, GWA=1.76, Units=18, F=0, I=0, D=0) | With Deficiency — GWA 1.76 exceeds 1.75 | With Deficiency — "GWA 1.76 exceeds maximum allowed 1.75 for Merit Scholar" | **PASS** | Staff > Submissions > Review A02 > Evaluate Compliance |
| **TC03** | Evaluate record A03 (Community, GWA=2.25, Units=15, F=0, I=0, D=1) | Compliant — 1 dropped subject allowed by Community program | Compliant — "All policy requirements met." | **PASS** | Staff > Submissions > Review A03 > Evaluate Compliance |
| **TC04** | Evaluate record A04 (Community, GWA=2.25, Units=14, F=0, I=1, D=0) | With Deficiency — insufficient units AND incomplete grade | With Deficiency — "Enrolled units 14 below minimum required 15; Incomplete grades 1 exceeds allowed 0" | **PASS** | Staff > Submissions > Review A04 > Evaluate Compliance |
| **TC05** | Evaluate record A05 (Merit, GWA=1.50, Units=18, F=1, I=0, D=0, 1 min late) | With Deficiency — failing subject AND late submission | With Deficiency — "Failed subjects 1 exceeds allowed 0; Submission was late" | **PASS** | Staff > Submissions > Review A05 > Evaluate Compliance |
| **TC06** | Attempt evaluate & approve record A06 (Pending verification) | Blocked — "must be verified first" | Blocked — "Cannot evaluate. Submission must be verified first." | **PASS** | Staff > Submissions > Review A06 > Evaluate button blocked |
| **TC07** | Submit duplicate for A01 (same AY + semester) AND submit GWA=0.50 | Rejected — "Duplicate submission rejected" AND "GWA must be between 1.00 and 5.00" | Duplicate rejected with message; Invalid GWA rejected with message | **PASS** | Scholar A01 > Submit Grades > duplicate attempt |
| **TC08** | As Scholar A01, attempt to view A02's submission via API | Access denied — HTTP 403 | 403 "Access denied. You can only view your own submissions." | **PASS** | API call: handler.php?action=get_submission&id=[A02_id] |
| **TC09** | Return A01's record, correct & resubmit, then refresh | Pending verification with preserved history | Status resets to Pending; audit log shows return + resubmission | **PASS** | Staff returns > Scholar resubmits > Refresh shows Pending |
| **TC10** | Staff approve compliant A01, hold deficient A02; scholar attempts change | Approved for compliant; Held for deficient; scholar blocked | Approval succeeds; Hold succeeds; Scholar has no decision controls | **PASS** | Staff > Submissions > Renewal decisions |

---

### Notes
- All test records seeded via `setup/seed.php` with Academic Year 2026-2027 Semester 1
- Records A01–A05 are pre-verified; A06 is intentionally left as Pending for TC06
- Compliance evaluation reads policy thresholds from the `scholarship_programs` table, not hardcoded
- Role enforcement is implemented at the API level in `api/handler.php`, not just UI hiding
