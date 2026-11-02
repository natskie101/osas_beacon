-- Uploaded files are stored outside the document root and streamed through an authorised controller.
CREATE TABLE IF NOT EXISTS files (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_user_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(60) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    checksum CHAR(64) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY files_owner_index (owner_user_id),
    KEY files_entity_index (entity_type, entity_id),
    CONSTRAINT files_owner_fk FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-user in-app notifications (bell).
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'info',
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(60) NOT NULL DEFAULT '',
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY notifications_user_index (user_id, read_at),
    CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
