ALTER TABLE users
    MODIFY status ENUM('Active', 'Pending', 'Inactive', 'Archived') NOT NULL DEFAULT 'Pending',
    ADD COLUMN archived_status ENUM('Active', 'Pending', 'Inactive') NULL AFTER status;
