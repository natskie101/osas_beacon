-- Rotating infographics shown on the student dashboard (image bytes live in files).
CREATE TABLE IF NOT EXISTS dashboard_infographics (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    image_file_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY dashboard_infographics_image_index (image_file_id),
    CONSTRAINT dashboard_infographics_file_fk FOREIGN KEY (image_file_id) REFERENCES files (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
