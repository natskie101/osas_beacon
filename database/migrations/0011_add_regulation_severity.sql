-- Regulation severity rating used by the policy editor.
ALTER TABLE regulations
    ADD COLUMN severity VARCHAR(50) NOT NULL DEFAULT '' AFTER category;
