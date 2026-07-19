# V2 Import Complete Baseline

Captured: 2026-07-19 00:37 America/Chicago

Git tag: `v2-import-complete`

Database: `job_dashboard_v2`

Backup:

`storage/app/private/backups/job_dashboard_v2-20260719-003713.sql`

The backup contains schema and table data. It skips routines, events, and triggers because the application database user intentionally does not have those administrative privileges, and the Laravel V2 schema does not depend on them.

## Row Counts

| Table | Rows |
| --- | ---: |
| `jobs` | 465 |
| `job_events` | 121 |
| `generated_documents` | 620 |
| `dashboard_run_status` | 3 |
| `data_import_runs` | 1 |
| `data_import_row_errors` | 0 |
| `users` | 0 |

## Top 20 Sort

1. `overall_recommendation`: `Apply`, `Maybe`, `Pass`, other
2. `career_fit_score` descending
3. `life_fit_score` descending
4. `last_seen` descending
5. `company` ascending

## Current Top 20

| Rank | V1 Job ID | Company | Role | Career Fit | Life Fit | Priority Score | Status |
| ---: | ---: | --- | --- | ---: | ---: | ---: | --- |
| 1 | 418631 | Huble | CRM Solutions Architect (Remote \| USA) | 100 | 100 | 100 | Apply Soon |
| 2 | 11622 | BeyondTrust | Sr Salesforce Administrator- Remote | 100 | 95 | 100 | Apply Soon |
| 3 | 418622 | Akkodis | Salesforce Administrator | 100 | 94 | 100 | Apply Soon |
| 4 | 243931 | Figma | Solutions Consultant | 100 | 94 | 100 | Submitted |
| 5 | 645636 | General Motors | Staff Software Engineer - Digital eCommerce | 100 | 94 | 100 | Applied |
| 6 | 645626 | Twin Health | Senior Salesforce Technical Consultant, GTM | 100 | 94 | 100 | Ready for Review |
| 7 | 645627 | LucidLink | Senior RevOps Administrator | 100 | 92 | 100 | Ready for Review |
| 8 | 645622 | Comply | Sr. Salesforce Administrator | 100 | 87 | 100 | Ready for Review |
| 9 | 645634 | Electronic Arts (EA) | Senior Cloud Engineer | 100 | 87 | 100 | Ready for Review |
| 10 | 557688 | SwiftConnect | Solutions Engineer | 100 | 84 | 100 | Apply Soon |
| 11 | 645630 | Tenth Revolution Group | Salesforce Administrator | 100 | 84 | 100 | Ready for Review |
| 12 | 221049 | Vixxo Facility Solutions | Revenue Operations Leader | 100 | 84 | 100 | Ready for Review |
| 13 | 418625 | CrossCountry Consulting | Salesforce Consultant | 100 | 82 | 100 | Apply Soon |
| 14 | 645628 | KnowBe4 | Salesforce Delivery Manager (Channel/Portal Squad) (Remote) | 100 | 82 | 100 | Ready for Review |
| 15 | 557690 | Western Governors University | Systems Engineer II- Salesforce | 100 | 82 | 100 | Apply Soon |
| 16 | 418629 | Guidehouse | Salesforce Senior Solution Consultant: Business Analyst | 100 | 81 | 100 | Apply Soon |
| 17 | 580738 | Visa | Senior Site Reliability Engineer | 100 | 81 | 100 | Apply Soon |
| 18 | 645624 | Outreach | Senior Technical Consultant | 100 | 77 | 100 | Ready for Review |
| 19 | 645629 | Tekgence Inc | Siebel CRM developer | 100 | 77 | 100 | Ready for Review |
| 20 | 418628 | Baker Tilly US | Salesforce Consulting Senior | 100 | 76 | 100 | Apply Soon |
