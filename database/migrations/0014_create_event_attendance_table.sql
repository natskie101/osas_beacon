-- Student attendance records used by the participation tracking ledger.
CREATE TABLE IF NOT EXISTS event_attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    event_code VARCHAR(30) NOT NULL DEFAULT '',
    event_name VARCHAR(200) NOT NULL DEFAULT '',
    attended_at DATETIME NULL,
    credit_hours TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY event_attendance_user_index (user_id),
    KEY event_attendance_event_index (event_code),
    CONSTRAINT event_attendance_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
