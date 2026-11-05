-- Optional YouTube/Vimeo link for orientation modules.
ALTER TABLE orientation_modules
    ADD COLUMN video_url VARCHAR(500) NOT NULL DEFAULT '' AFTER media_file_id;
