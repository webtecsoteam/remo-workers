ALTER TABLE messages
    ADD COLUMN attachment_path VARCHAR(512) NULL DEFAULT NULL AFTER message,
    ADD COLUMN attachment_name VARCHAR(255) NULL DEFAULT NULL AFTER attachment_path,
    ADD COLUMN attachment_mime VARCHAR(128) NULL DEFAULT NULL AFTER attachment_name;

ALTER TABLE messages MODIFY message TEXT NULL;
