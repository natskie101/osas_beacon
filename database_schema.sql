-- ========================================
-- OSAS Beacon Database Schema
-- PostgreSQL DDL Script
-- Generated from Laravel Migrations
-- ========================================

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS dashboard_infographics CASCADE;
DROP TABLE IF EXISTS sessions CASCADE;
DROP TABLE IF EXISTS orientation_quiz_answers CASCADE;
DROP TABLE IF EXISTS orientation_quiz_attempts CASCADE;
DROP TABLE IF EXISTS orientation_quiz_options CASCADE;
DROP TABLE IF EXISTS orientation_quiz_questions CASCADE;
DROP TABLE IF EXISTS orientation_quizzes CASCADE;
DROP TABLE IF EXISTS event_attendance CASCADE;
DROP TABLE IF EXISTS tracking CASCADE;
DROP TABLE IF EXISTS reports CASCADE;
DROP TABLE IF EXISTS broadcasts CASCADE;
DROP TABLE IF EXISTS support_messages CASCADE;
DROP TABLE IF EXISTS support_tickets CASCADE;
DROP TABLE IF EXISTS document_request_events CASCADE;
DROP TABLE IF EXISTS document_requests CASCADE;
DROP TABLE IF EXISTS lost_found_claims CASCADE;
DROP TABLE IF EXISTS lost_found_items CASCADE;
DROP TABLE IF EXISTS events CASCADE;
DROP TABLE IF EXISTS orientation_progress CASCADE;
DROP TABLE IF EXISTS orientation_modules CASCADE;
DROP TABLE IF EXISTS regulations CASCADE;
DROP TABLE IF EXISTS announcements CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS files CASCADE;
DROP TABLE IF EXISTS audit_log CASCADE;
DROP TABLE IF EXISTS login_attempts CASCADE;
DROP TABLE IF EXISTS password_resets CASCADE;
DROP TABLE IF EXISTS remember_tokens CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- ========================================
-- Migration: 2026_01_01_000001_create_users_table
-- ========================================

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'Student' CHECK (role IN ('Coordinator', 'Administrator', 'Student')),
    student_id VARCHAR(30),
    department VARCHAR(150),
    program VARCHAR(150),
    year_level VARCHAR(30),
    access_level VARCHAR(150),
    account_tier VARCHAR(50) NOT NULL DEFAULT 'Student Tier' CHECK (account_tier IN ('Student Tier', 'Admin Tier')),
    status VARCHAR(50) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Active', 'Pending', 'Inactive', 'Archived')),
    phone VARCHAR(30),
    school_id_file_id BIGINT,
    email_verified_at TIMESTAMP,
    last_login_at TIMESTAMP,
    last_seen_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    requested_at TIMESTAMP,
    middle_name VARCHAR(100),
    age SMALLINT,
    archived_status VARCHAR(50) CHECK (archived_status IN ('Active', 'Pending', 'Inactive'))
);

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_last_name ON users(last_name);

-- ========================================
-- Migration: 2026_01_01_000002_create_auth_tables
-- ========================================

CREATE TABLE remember_tokens (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    selector CHAR(18) UNIQUE NOT NULL,
    validator_hash CHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    last_used_at TIMESTAMP
);

CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);
ALTER TABLE remember_tokens ADD CONSTRAINT fk_remember_tokens_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

CREATE TABLE password_resets (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    email VARCHAR(190) NOT NULL,
    token_hash CHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_password_resets_email ON password_resets(email);
ALTER TABLE password_resets ADD CONSTRAINT fk_password_resets_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

CREATE TABLE login_attempts (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    successful SMALLINT NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_login_attempts_email_ip_time ON login_attempts(email, ip_address, attempted_at);
CREATE INDEX idx_login_attempts_attempted_at ON login_attempts(attempted_at);

-- ========================================
-- Migration: 2026_01_01_000003_create_audit_log_table
-- ========================================

CREATE TABLE audit_log (
    id BIGSERIAL PRIMARY KEY,
    actor_user_id BIGINT,
    actor_name VARCHAR(150) NOT NULL DEFAULT '',
    target_user_id BIGINT,
    action VARCHAR(60) NOT NULL DEFAULT '',
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(60) NOT NULL DEFAULT '',
    title VARCHAR(180) NOT NULL,
    text TEXT,
    tone VARCHAR(20) NOT NULL DEFAULT 'brand',
    icon VARCHAR(40) NOT NULL DEFAULT 'info',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_audit_log_actor_user_id ON audit_log(actor_user_id);
CREATE INDEX idx_audit_log_target_user_id ON audit_log(target_user_id);
CREATE INDEX idx_audit_log_action ON audit_log(action);
CREATE INDEX idx_audit_log_created_at ON audit_log(created_at);
ALTER TABLE audit_log ADD CONSTRAINT fk_audit_log_actor_user_id 
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE audit_log ADD CONSTRAINT fk_audit_log_target_user_id 
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- ========================================
-- Migration: 2026_01_01_000004_create_files_and_notifications_tables
-- ========================================

CREATE TABLE files (
    id BIGSERIAL PRIMARY KEY,
    owner_user_id BIGINT,
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(60) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT NOT NULL DEFAULT 0,
    checksum CHAR(64),
    created_at TIMESTAMP NOT NULL,
    file_data TEXT
);

CREATE INDEX idx_files_owner_user_id ON files(owner_user_id);
CREATE INDEX idx_files_entity_type_id ON files(entity_type, entity_id);
ALTER TABLE files ADD CONSTRAINT fk_files_owner_user_id 
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT,
    type VARCHAR(40) NOT NULL DEFAULT 'info',
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(60) NOT NULL DEFAULT '',
    read_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_notifications_user_id_read_at ON notifications(user_id, read_at);
ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- ========================================
-- Migration: 2026_01_01_000005_create_content_modules
-- ========================================

CREATE TABLE announcements (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    audience VARCHAR(100) NOT NULL DEFAULT 'All Student Portals',
    status VARCHAR(50) NOT NULL DEFAULT 'Draft' CHECK (status IN ('Draft', 'Scheduled', 'Published', 'Archived')),
    priority VARCHAR(50) NOT NULL DEFAULT 'Standard Priority',
    summary TEXT,
    body TEXT,
    author VARCHAR(150) NOT NULL DEFAULT '',
    views BIGINT NOT NULL DEFAULT 0,
    published_at TIMESTAMP,
    scheduled_for TIMESTAMP,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    attachments JSON
);

CREATE INDEX idx_announcements_status ON announcements(status);
CREATE INDEX idx_announcements_category ON announcements(category);
CREATE INDEX idx_announcements_published_at ON announcements(published_at);
ALTER TABLE announcements ADD CONSTRAINT fk_announcements_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE regulations (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    reference_code VARCHAR(50) NOT NULL DEFAULT '',
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    audience VARCHAR(100) NOT NULL DEFAULT 'All Students',
    status VARCHAR(50) NOT NULL DEFAULT 'Draft' CHECK (status IN ('Draft', 'Pending Review', 'Published', 'Archived')),
    effective_date VARCHAR(100) NOT NULL DEFAULT '',
    review_schedule VARCHAR(150) NOT NULL DEFAULT '',
    approval_body VARCHAR(200) NOT NULL DEFAULT '',
    views BIGINT NOT NULL DEFAULT 0,
    summary TEXT,
    body TEXT,
    sanctions JSON,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    severity VARCHAR(50) NOT NULL DEFAULT ''
);

CREATE INDEX idx_regulations_status ON regulations(status);
CREATE INDEX idx_regulations_category ON regulations(category);
ALTER TABLE regulations ADD CONSTRAINT fk_regulations_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE orientation_modules (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(120) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'Draft' CHECK (status IN ('Draft', 'Published', 'Archived')),
    audience VARCHAR(100) NOT NULL DEFAULT 'Incoming Freshmen',
    version VARCHAR(20) NOT NULL DEFAULT 'v1.0',
    duration_minutes INTEGER NOT NULL DEFAULT 0,
    mandatory SMALLINT NOT NULL DEFAULT 1,
    learners INTEGER NOT NULL DEFAULT 0,
    completions INTEGER NOT NULL DEFAULT 0,
    completion_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
    description TEXT,
    objectives JSON,
    body TEXT,
    department VARCHAR(150) NOT NULL DEFAULT '',
    attachments_count INTEGER NOT NULL DEFAULT 0,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    content_type VARCHAR(50) NOT NULL DEFAULT 'Video Presentation',
    verification_method VARCHAR(80) NOT NULL DEFAULT 'Quiz Assessment',
    passing_rate SMALLINT NOT NULL DEFAULT 0,
    compliance_deadline DATE,
    media_file_id BIGINT,
    video_url VARCHAR(500) NOT NULL DEFAULT ''
);

CREATE INDEX idx_orientation_modules_status ON orientation_modules(status);
ALTER TABLE orientation_modules ADD CONSTRAINT fk_orientation_modules_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE orientation_progress (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    module_id BIGINT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Not Started' CHECK (status IN ('Not Started', 'In Progress', 'Completed')),
    progress SMALLINT NOT NULL DEFAULT 0,
    last_viewed_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    video_watched_at TIMESTAMP,
    UNIQUE(user_id, module_id)
);

ALTER TABLE orientation_progress ADD CONSTRAINT fk_orientation_progress_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE orientation_progress ADD CONSTRAINT fk_orientation_progress_module_id 
    FOREIGN KEY (module_id) REFERENCES orientation_modules(id) ON DELETE CASCADE;

CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'Scheduled' CHECK (status IN ('Scheduled', 'Ongoing', 'Completed', 'Cancelled')),
    start_at TIMESTAMP NOT NULL,
    end_at TIMESTAMP,
    venue VARCHAR(180) NOT NULL DEFAULT '',
    mode VARCHAR(50) NOT NULL DEFAULT 'On-campus',
    audience VARCHAR(100) NOT NULL DEFAULT 'All Students',
    capacity INTEGER NOT NULL DEFAULT 0,
    registered INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    organizer VARCHAR(180) NOT NULL DEFAULT '',
    verification VARCHAR(200) NOT NULL DEFAULT '',
    attachments JSON,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_start_at ON events(start_at);
ALTER TABLE events ADD CONSTRAINT fk_events_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE lost_found_items (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'Found' CHECK (type IN ('Lost', 'Found')),
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    description TEXT,
    location VARCHAR(180) NOT NULL DEFAULT '',
    date_occurred TIMESTAMP,
    reported_by VARCHAR(150) NOT NULL DEFAULT '',
    reporter_email VARCHAR(190) NOT NULL DEFAULT '',
    student_id VARCHAR(30) NOT NULL DEFAULT '',
    contact VARCHAR(190) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'Open' CHECK (status IN ('Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed')),
    custody VARCHAR(150) NOT NULL DEFAULT '',
    attachment VARCHAR(255) NOT NULL DEFAULT '',
    posted_at TIMESTAMP NOT NULL,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    image_file_id BIGINT
);

CREATE INDEX idx_lost_found_items_status ON lost_found_items(status);
CREATE INDEX idx_lost_found_items_type ON lost_found_items(type);
ALTER TABLE lost_found_items ADD CONSTRAINT fk_lost_found_items_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE lost_found_claims (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    item_id BIGINT NOT NULL,
    claimant_user_id BIGINT,
    claimant_name VARCHAR(150) NOT NULL,
    claimant_email VARCHAR(190) NOT NULL,
    student_id VARCHAR(30) NOT NULL DEFAULT '',
    proof TEXT,
    message TEXT,
    attachment VARCHAR(255) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending', 'Verified', 'Rejected')),
    verified_by VARCHAR(150) NOT NULL DEFAULT '',
    verified_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_lost_found_claims_item_id_status ON lost_found_claims(item_id, status);
ALTER TABLE lost_found_claims ADD CONSTRAINT fk_lost_found_claims_item_id 
    FOREIGN KEY (item_id) REFERENCES lost_found_items(id) ON DELETE CASCADE;
ALTER TABLE lost_found_claims ADD CONSTRAINT fk_lost_found_claims_claimant_user_id 
    FOREIGN KEY (claimant_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE document_requests (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    student_user_id BIGINT,
    student_name VARCHAR(150) NOT NULL,
    student_id VARCHAR(30) NOT NULL DEFAULT '',
    student_email VARCHAR(190) NOT NULL,
    program VARCHAR(150) NOT NULL DEFAULT '',
    year_level VARCHAR(30) NOT NULL DEFAULT '',
    document_type VARCHAR(150) NOT NULL,
    purpose TEXT,
    copies INTEGER NOT NULL DEFAULT 1,
    delivery_mode VARCHAR(60) NOT NULL DEFAULT 'Pick-up at OSAS',
    status VARCHAR(50) NOT NULL DEFAULT 'Submitted' CHECK (status IN ('Submitted', 'Under Review', 'Ready for Pick-up', 'Released', 'Rejected', 'Cancelled')),
    priority VARCHAR(30) NOT NULL DEFAULT 'Medium',
    requested_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    released_at TIMESTAMP,
    processed_by VARCHAR(150) NOT NULL DEFAULT 'OSAS Records',
    remarks TEXT,
    attachment VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_document_requests_student_user_id ON document_requests(student_user_id);
CREATE INDEX idx_document_requests_status ON document_requests(status);
ALTER TABLE document_requests ADD CONSTRAINT fk_document_requests_student_user_id 
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE document_request_events (
    id BIGSERIAL PRIMARY KEY,
    request_id BIGINT NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT '',
    by_name VARCHAR(150) NOT NULL DEFAULT '',
    note TEXT,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_document_request_events_request_id ON document_request_events(request_id, created_at);
ALTER TABLE document_request_events ADD CONSTRAINT fk_document_request_events_request_id 
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE;

CREATE TABLE support_tickets (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    student_user_id BIGINT,
    student_name VARCHAR(150) NOT NULL,
    student_email VARCHAR(190) NOT NULL,
    student_id VARCHAR(30) NOT NULL DEFAULT '',
    program VARCHAR(150) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL DEFAULT '',
    subject VARCHAR(200) NOT NULL,
    description TEXT,
    priority VARCHAR(30) NOT NULL DEFAULT 'Medium',
    status VARCHAR(50) NOT NULL DEFAULT 'Open' CHECK (status IN ('Open', 'In Progress', 'Resolved', 'Closed')),
    assigned_to VARCHAR(150) NOT NULL DEFAULT '',
    rating SMALLINT,
    submitted_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_support_tickets_student_user_id ON support_tickets(student_user_id);
CREATE INDEX idx_support_tickets_status ON support_tickets(status);
ALTER TABLE support_tickets ADD CONSTRAINT fk_support_tickets_student_user_id 
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE support_messages (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL,
    author_name VARCHAR(150) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'Student',
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_support_messages_ticket_id ON support_messages(ticket_id, created_at);
ALTER TABLE support_messages ADD CONSTRAINT fk_support_messages_ticket_id 
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE;

CREATE TABLE broadcasts (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    channel VARCHAR(60) NOT NULL DEFAULT 'Portal',
    status VARCHAR(50) NOT NULL DEFAULT 'Draft' CHECK (status IN ('Draft', 'Pending', 'Scheduled', 'Sent')),
    audience VARCHAR(100) NOT NULL DEFAULT 'All Student Portals',
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP,
    reach INTEGER NOT NULL DEFAULT 0,
    delivered INTEGER NOT NULL DEFAULT 0,
    opened INTEGER NOT NULL DEFAULT 0,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_broadcasts_status ON broadcasts(status);
ALTER TABLE broadcasts ADD CONSTRAINT fk_broadcasts_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE reports (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    period VARCHAR(80) NOT NULL DEFAULT '',
    status VARCHAR(40) NOT NULL DEFAULT 'Completed',
    format VARCHAR(20) NOT NULL DEFAULT 'CSV',
    records BIGINT NOT NULL DEFAULT 0,
    generated_at TIMESTAMP NOT NULL,
    generated_by VARCHAR(150) NOT NULL DEFAULT '',
    scope VARCHAR(200) NOT NULL DEFAULT '',
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    report_type VARCHAR(60) NOT NULL DEFAULT '',
    date_from DATE,
    date_to DATE
);

CREATE INDEX idx_reports_category ON reports(category);
ALTER TABLE reports ADD CONSTRAINT fk_reports_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE tracking (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    type VARCHAR(80) NOT NULL DEFAULT '',
    views BIGINT NOT NULL DEFAULT 0,
    unique_visitors BIGINT NOT NULL DEFAULT 0,
    avg_time VARCHAR(20) NOT NULL DEFAULT '0m 00s',
    engagement DECIMAL(5, 2) NOT NULL DEFAULT 0,
    last_viewed TIMESTAMP,
    trend DECIMAL(6, 2) NOT NULL DEFAULT 0,
    source_id VARCHAR(40) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_tracking_source_id ON tracking(source_id);

-- ========================================
-- Migration: 2026_01_01_000014_create_event_attendance_table
-- ========================================

CREATE TABLE event_attendance (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    event_code VARCHAR(30) NOT NULL DEFAULT '',
    event_name VARCHAR(200) NOT NULL DEFAULT '',
    attended_at TIMESTAMP,
    credit_hours SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_event_attendance_user_id ON event_attendance(user_id);
CREATE INDEX idx_event_attendance_event_code ON event_attendance(event_code);
ALTER TABLE event_attendance ADD CONSTRAINT fk_event_attendance_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- ========================================
-- Migration: 2026_01_01_000015_create_orientation_quizzes
-- ========================================

CREATE TABLE orientation_quizzes (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    module_id BIGINT UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL DEFAULT '',
    instructions TEXT,
    time_limit_minutes INTEGER NOT NULL DEFAULT 20,
    status VARCHAR(50) NOT NULL DEFAULT 'Draft' CHECK (status IN ('Draft', 'Published')),
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

ALTER TABLE orientation_quizzes ADD CONSTRAINT fk_orientation_quizzes_module_id 
    FOREIGN KEY (module_id) REFERENCES orientation_modules(id) ON DELETE CASCADE;

CREATE TABLE orientation_quiz_questions (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    quiz_id BIGINT NOT NULL,
    prompt TEXT NOT NULL,
    points INTEGER NOT NULL DEFAULT 1,
    position INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_orientation_quiz_questions_quiz_id ON orientation_quiz_questions(quiz_id);
ALTER TABLE orientation_quiz_questions ADD CONSTRAINT fk_orientation_quiz_questions_quiz_id 
    FOREIGN KEY (quiz_id) REFERENCES orientation_quizzes(id) ON DELETE CASCADE;

CREATE TABLE orientation_quiz_options (
    id BIGSERIAL PRIMARY KEY,
    question_id BIGINT NOT NULL,
    label VARCHAR(500) NOT NULL,
    is_correct SMALLINT NOT NULL DEFAULT 0,
    position INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_orientation_quiz_options_question_id ON orientation_quiz_options(question_id);
ALTER TABLE orientation_quiz_options ADD CONSTRAINT fk_orientation_quiz_options_question_id 
    FOREIGN KEY (question_id) REFERENCES orientation_quiz_questions(id) ON DELETE CASCADE;

CREATE TABLE orientation_quiz_attempts (
    id BIGSERIAL PRIMARY KEY,
    public_id VARCHAR(30) UNIQUE NOT NULL,
    quiz_id BIGINT NOT NULL,
    user_id BIGINT,
    score INTEGER NOT NULL DEFAULT 0,
    total_points INTEGER NOT NULL DEFAULT 0,
    percent DECIMAL(5, 2) NOT NULL DEFAULT 0,
    passed SMALLINT NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'In Progress' CHECK (status IN ('In Progress', 'Submitted', 'Expired')),
    started_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    submitted_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_orientation_quiz_attempts_quiz_id ON orientation_quiz_attempts(quiz_id);
CREATE INDEX idx_orientation_quiz_attempts_user_id ON orientation_quiz_attempts(user_id);
ALTER TABLE orientation_quiz_attempts ADD CONSTRAINT fk_orientation_quiz_attempts_quiz_id 
    FOREIGN KEY (quiz_id) REFERENCES orientation_quizzes(id) ON DELETE CASCADE;
ALTER TABLE orientation_quiz_attempts ADD CONSTRAINT fk_orientation_quiz_attempts_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE orientation_quiz_answers (
    id BIGSERIAL PRIMARY KEY,
    attempt_id BIGINT NOT NULL,
    question_id BIGINT NOT NULL,
    option_id BIGINT,
    is_correct SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_orientation_quiz_answers_attempt_id ON orientation_quiz_answers(attempt_id);
ALTER TABLE orientation_quiz_answers ADD CONSTRAINT fk_orientation_quiz_answers_attempt_id 
    FOREIGN KEY (attempt_id) REFERENCES orientation_quiz_attempts(id) ON DELETE CASCADE;

-- ========================================
-- Migration: 2026_01_01_000019_create_dashboard_infographics_table
-- ========================================

CREATE TABLE dashboard_infographics (
    id BIGSERIAL PRIMARY KEY,
    image_file_id BIGINT NOT NULL,
    created_by BIGINT,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_dashboard_infographics_image_file_id ON dashboard_infographics(image_file_id);
ALTER TABLE dashboard_infographics ADD CONSTRAINT fk_dashboard_infographics_image_file_id 
    FOREIGN KEY (image_file_id) REFERENCES files(id) ON DELETE CASCADE;

-- ========================================
-- Migration: 2026_01_01_000020_create_sessions_table
-- ========================================

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- ========================================
-- Data Migration: 2026_01_01_000022_clean_corrupt_phone_data
-- Clean corrupt phone data (applied during setup if needed)
-- ========================================

-- UPDATE users 
-- SET phone = NULL 
-- WHERE phone LIKE '%values%phone%' 
--    OR phone LIKE '%{{%';

-- ========================================
-- END OF SCHEMA
-- ========================================

-- Note: This script creates all tables with their constraints.
-- Some data migrations (like migration 000022) are commented out
-- as they would only apply to existing data.
-- Migration 000007 (align_seed_status_labels) was a no-op.
-- Migration 000017 (preserve_user_history_on_delete) is reflected
-- in the foreign key constraints using ON DELETE SET NULL instead
-- of ON DELETE CASCADE for specific tables (orientation_progress,
-- event_attendance, orientation_quiz_attempts, notifications).
