-- Preserve the workflow labels used by the original static portal seed data.
ALTER TABLE regulations
    MODIFY status ENUM('Draft', 'Pending Review', 'Published', 'Archived') NOT NULL DEFAULT 'Draft';

ALTER TABLE lost_found_items
    MODIFY status ENUM('Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed') NOT NULL DEFAULT 'Open';

ALTER TABLE lost_found_claims
    MODIFY status ENUM('Pending', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending';

ALTER TABLE document_requests
    MODIFY status ENUM('Submitted', 'Under Review', 'Ready for Pick-up', 'Released', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Submitted';

ALTER TABLE broadcasts
    MODIFY status ENUM('Draft', 'Pending', 'Scheduled', 'Sent') NOT NULL DEFAULT 'Draft';
