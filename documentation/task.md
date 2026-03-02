# Task List - RBA Hospital Development

## Phase 1: Planning & Setup [x]
- [x] Analyze SRS document (`RBA_HOSPITAL_SPEC.md`)
- [x] Create Technical Implementation Plan (`implementation_plan.md`)
- [x] Initialize Laravel project
- [x] Configure Database & Environment

## Phase 2: Database & Models [x]
- [x] Create Level 1 Migrations (Users, Units, Account Codes, Periods)
- [x] Create Level 2 Migrations (RBA Headers)
- [x] Create Level 3 Migrations (Submissions, Account Pagu)
- [x] Create Level 4 Migrations (RBA Details)
- [x] Create Level 5 Migrations (Attachments)
- [x] Define Eloquent Models & Relationships

## Phase 3: Master Data & Frontend Setup [/]
- [x] Setup Frontend Environment with Bun
- [x] Implement Master Data Management (Units, Account Codes, Periods)
    - [x] Unit Management (CRUD)
    - [x] Account Code Management (CRUD)
    - [x] RBA Period Management (CRUD)
- [ ] Implement RBA Header & Unit Submission Logic

## Phase 4: Core Features - Sprint 2 [ ]
- [ ] Build Operator Workboard: CRUD RBA Details with PDF Upload (V1)

## Phase 5: Budget Logic & Locking - Sprint 3 [ ]
- [ ] Create Admin Dashboard for Pagu Global input
- [ ] Implement 'Read-Only' state logic (Observers & Policies)
- [ ] Implement File Versioning Strategy (V2+ uploads)
- [ ] Validation: Check total detail vs Pagu Global

## Phase 6: Reporting & Audit - Sprint 4 [ ]
- [ ] Build Supervisor Review Dashboard
- [ ] Implement PDF History/Version Viewer
- [ ] Final UI/UX Polishing
- [ ] Verification & Testing
