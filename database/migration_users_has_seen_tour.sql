-- PESO: Track whether a user has already seen the welcome dialog + guided
-- tour. New accounts start at 0 and get the tour on their first login.
-- Existing accounts are marked as already seen so they don't get it again.

ALTER TABLE users
  ADD COLUMN has_seen_tour TINYINT(1) NOT NULL DEFAULT 0 AFTER notify_budget_alerts;

UPDATE users SET has_seen_tour = 1;
