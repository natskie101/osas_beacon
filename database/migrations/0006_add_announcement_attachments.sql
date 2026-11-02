-- Attachment metadata for announcements (uploaded files are tracked in files).
ALTER TABLE announcements
    ADD COLUMN attachments JSON NULL AFTER body;
