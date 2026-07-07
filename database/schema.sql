-- ============================================================
-- SIWES Placement, Tracking, and Reporting System
-- Database Schema (PostgreSQL)
-- ============================================================
-- Note: Render creates and connects you to the database itself,
-- so there is no CREATE DATABASE / USE statement here — just run
-- this file against the database Render already gave you.

-- ------------------------------------------------------------
-- Users: every account (student, supervisor/company, admin)
-- ------------------------------------------------------------
CREATE TABLE users (
    id              SERIAL PRIMARY KEY,
    role            VARCHAR(20) NOT NULL CHECK (role IN ('student', 'supervisor', 'admin')),
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Students: extends a user with role-specific profile fields
-- ------------------------------------------------------------
CREATE TABLE students (
    id              SERIAL PRIMARY KEY,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    matric_no       VARCHAR(50) NOT NULL UNIQUE,
    department      VARCHAR(100) NOT NULL,
    level           VARCHAR(20) NOT NULL,
    phone           VARCHAR(30),
    skills          TEXT, -- Comma-separated skill keywords used for matching
    bio             TEXT,
    status          VARCHAR(20) DEFAULT 'unplaced' CHECK (status IN ('unplaced', 'pending', 'placed', 'completed')),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Companies: extends a user with organisation profile fields
-- ------------------------------------------------------------
CREATE TABLE companies (
    id              SERIAL PRIMARY KEY,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    company_name    VARCHAR(150) NOT NULL,
    industry        VARCHAR(100),
    location        VARCHAR(150),
    description     TEXT,
    skills_required TEXT, -- Comma-separated skill keywords used for matching
    slots_available INT DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Placements: a student's application to / placement with a company
-- ------------------------------------------------------------
CREATE TABLE placements (
    id              SERIAL PRIMARY KEY,
    student_id      INT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    company_id      INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    match_score     INT DEFAULT 0, -- Recommendation score at time of application (0-100)
    status          VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected', 'completed')),
    applied_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    decided_at      TIMESTAMP NULL,
    UNIQUE (student_id, company_id)
);

-- ------------------------------------------------------------
-- Logbook entries: daily/weekly activity records tied to a placement
-- ------------------------------------------------------------
CREATE TABLE logbook_entries (
    id                      SERIAL PRIMARY KEY,
    placement_id            INT NOT NULL REFERENCES placements(id) ON DELETE CASCADE,
    entry_date              DATE NOT NULL,
    activity_description    TEXT NOT NULL,
    supervisor_comment       TEXT,
    status                  VARCHAR(20) DEFAULT 'submitted' CHECK (status IN ('submitted', 'reviewed')),
    created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Reports: weekly / monthly / final reports tied to a placement
-- ------------------------------------------------------------
CREATE TABLE reports (
    id                  SERIAL PRIMARY KEY,
    placement_id        INT NOT NULL REFERENCES placements(id) ON DELETE CASCADE,
    report_type         VARCHAR(20) DEFAULT 'weekly' CHECK (report_type IN ('weekly', 'monthly', 'final')),
    period_label        VARCHAR(50) NOT NULL, -- e.g. "Week 3" or "March 2026"
    content             TEXT NOT NULL,
    submitted_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status              VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'reviewed')),
    supervisor_feedback TEXT,
    score               INT -- Score out of 100 given by supervisor
);

-- ============================================================
-- Seed data
-- ============================================================
-- Demo accounts (admin + sample companies) are created by running
-- database/seed.php ONCE in your browser after importing this schema.
-- That script uses PHP's password_hash() directly, so the passwords
-- are guaranteed to work with this codebase (a hash typed straight
-- into SQL cannot be verified without running PHP).
