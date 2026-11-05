-- Orientation module editor fields: content type, verification and media.
ALTER TABLE orientation_modules
    ADD COLUMN content_type VARCHAR(50) NOT NULL DEFAULT 'Video Presentation' AFTER category,
    ADD COLUMN verification_method VARCHAR(80) NOT NULL DEFAULT 'Quiz Assessment' AFTER duration_minutes,
    ADD COLUMN passing_rate TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER verification_method,
    ADD COLUMN compliance_deadline DATE NULL AFTER passing_rate,
    ADD COLUMN media_file_id BIGINT UNSIGNED NULL AFTER attachments_count;
