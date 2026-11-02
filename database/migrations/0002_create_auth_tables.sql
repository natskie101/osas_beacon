-- Authentication support: remember-me tokens, password resets, login throttling.
CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(18) NOT NULL,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY remember_tokens_selector_unique (selector),
    KEY remember_tokens_user_index (user_id),
    CONSTRAINT remember_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY password_resets_token_unique (token_hash),
    KEY password_resets_email_index (email),
    CONSTRAINT password_resets_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY login_attempts_lookup_index (email, ip_address, attempted_at),
    KEY login_attempts_time_index (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
