-- Orientation quiz assessment tables.
CREATE TABLE IF NOT EXISTS orientation_quizzes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(30) NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL DEFAULT '',
    instructions TEXT NULL,
    time_limit_minutes INT UNSIGNED NOT NULL DEFAULT 20,
    status ENUM('Draft', 'Published') NOT NULL DEFAULT 'Draft',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY orientation_quizzes_public_id_unique (public_id),
    UNIQUE KEY orientation_quizzes_module_unique (module_id),
    CONSTRAINT orientation_quizzes_module_fk FOREIGN KEY (module_id) REFERENCES orientation_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orientation_quiz_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(30) NOT NULL,
    quiz_id BIGINT UNSIGNED NOT NULL,
    prompt TEXT NOT NULL,
    points INT UNSIGNED NOT NULL DEFAULT 1,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY orientation_quiz_questions_public_id_unique (public_id),
    KEY orientation_quiz_questions_quiz_index (quiz_id),
    CONSTRAINT orientation_quiz_questions_quiz_fk FOREIGN KEY (quiz_id) REFERENCES orientation_quizzes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orientation_quiz_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY orientation_quiz_options_question_index (question_id),
    CONSTRAINT orientation_quiz_options_question_fk FOREIGN KEY (question_id) REFERENCES orientation_quiz_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orientation_quiz_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(30) NOT NULL,
    quiz_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    total_points INT UNSIGNED NOT NULL DEFAULT 0,
    percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('In Progress', 'Submitted', 'Expired') NOT NULL DEFAULT 'In Progress',
    started_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY orientation_quiz_attempts_public_id_unique (public_id),
    KEY orientation_quiz_attempts_quiz_index (quiz_id),
    KEY orientation_quiz_attempts_user_index (user_id),
    CONSTRAINT orientation_quiz_attempts_quiz_fk FOREIGN KEY (quiz_id) REFERENCES orientation_quizzes (id) ON DELETE CASCADE,
    CONSTRAINT orientation_quiz_attempts_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orientation_quiz_answers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attempt_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY orientation_quiz_answers_attempt_index (attempt_id),
    CONSTRAINT orientation_quiz_answers_attempt_fk FOREIGN KEY (attempt_id) REFERENCES orientation_quiz_attempts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
