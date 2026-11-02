-- BaseModel-managed claims need the same timestamp pair as other records.
ALTER TABLE lost_found_claims ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;
