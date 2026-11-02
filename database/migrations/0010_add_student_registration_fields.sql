-- Student self-registration profile fields.
ALTER TABLE users
    ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name,
    ADD COLUMN age TINYINT UNSIGNED NULL AFTER student_id;
