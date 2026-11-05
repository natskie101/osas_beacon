-- Track which dataset a generated report belongs to and the export date range.
ALTER TABLE reports ADD COLUMN report_type VARCHAR(60) NOT NULL DEFAULT '' AFTER category;
ALTER TABLE reports ADD COLUMN date_from DATE NULL AFTER period;
ALTER TABLE reports ADD COLUMN date_to DATE NULL AFTER date_from;
