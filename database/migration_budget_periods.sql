-- PESO: Flexible budget periods (Day / Week / Month / Custom Range)
-- Migrates `budgets` from a month-only (CHAR(7) 'YYYY-MM') model to an
-- explicit period_type + period_start/period_end model.

ALTER TABLE budgets
  ADD COLUMN period_type ENUM('day','week','month','custom') NOT NULL DEFAULT 'month' AFTER category,
  ADD COLUMN period_start DATE NULL AFTER period_type,
  ADD COLUMN period_end DATE NULL AFTER period_start;

UPDATE budgets
SET period_type = 'month',
    period_start = STR_TO_DATE(CONCAT(month, '-01'), '%Y-%m-%d'),
    period_end = LAST_DAY(STR_TO_DATE(CONCAT(month, '-01'), '%Y-%m-%d'));

ALTER TABLE budgets
  MODIFY period_start DATE NOT NULL,
  MODIFY period_end DATE NOT NULL;

ALTER TABLE budgets
  DROP INDEX uniq_user_cat_month,
  ADD UNIQUE KEY uniq_user_cat_period (user_id, category, period_start, period_end);

ALTER TABLE budgets DROP COLUMN month;
