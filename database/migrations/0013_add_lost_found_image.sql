-- Optional photo of a lost/found item.
ALTER TABLE lost_found_items
    ADD COLUMN image_file_id BIGINT UNSIGNED NULL AFTER attachment;
