-- PESO: Tag each expense with which budget period it should be counted
-- toward (Day / Week / Month / Custom Range), matching the `budgets` table's
-- period_type. Existing rows default to 'month'.

ALTER TABLE expenses
  ADD COLUMN period_type ENUM('day','week','month','custom') NOT NULL DEFAULT 'month' AFTER expense_date;
