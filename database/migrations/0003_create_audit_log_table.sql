-- System audit trail.
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_name VARCHAR(150) NOT NULL DEFAULT '',
    target_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL DEFAULT '',
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(60) NOT NULL DEFAULT '',
    title VARCHAR(180) NOT NULL,
    text TEXT NULL,
    tone VARCHAR(20) NOT NULL DEFAULT 'brand',
    icon VARCHAR(40) NOT NULL DEFAULT 'info',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY audit_log_actor_index (actor_user_id),
    KEY audit_log_target_index (target_user_id),
    KEY audit_log_action_index (action),
    KEY audit_log_created_index (created_at),
    CONSTRAINT audit_log_actor_fk FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT audit_log_target_fk FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
