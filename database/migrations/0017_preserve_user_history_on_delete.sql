-- Keep participation and orientation history after its user profile is deleted.
ALTER TABLE orientation_progress
    DROP FOREIGN KEY orientation_progress_user_fk,
    MODIFY user_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT orientation_progress_user_history_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE event_attendance
    DROP FOREIGN KEY event_attendance_user_fk,
    MODIFY user_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT event_attendance_user_history_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE orientation_quiz_attempts
    DROP FOREIGN KEY orientation_quiz_attempts_user_fk,
    MODIFY user_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT orientation_quiz_attempts_user_history_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE notifications
    DROP FOREIGN KEY notifications_user_fk,
    MODIFY user_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT notifications_user_history_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;
