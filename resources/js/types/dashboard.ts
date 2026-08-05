export type Household = {
  id: number
  name: string
  base_currency_code: string
  default_locale: string
}

export type Member = {
  user_id: number
  name: string
  email: string
  role: string
  can_view_balances: boolean
  can_create_transactions: boolean
  can_view_transactions: boolean
}

export type Invitation = {
  id: number
  email: string
  role: string
  token: string
  accepted_at: string | null
}

export type BudgetSummary = {
  budget: null | {
    id: number
    name: string
    period_type: string
    base_currency_code: string
  }
  periods: Array<{
    id: number
    starts_on: string
    ends_on: string
    status: string
    lines: Array<{
      category_id: number
      category_name: string | null
      planned_minor_amount: number
      actual_minor_amount: number
      remaining_minor_amount: number
    }>
  }>
}

export type Receipt = {
  id: number
  client_uuid: string | null
  account_id: number
  currency_id: number
  total_minor_amount: number
  categorization_status: string
  categorized_minor_amount: number
  remaining_uncategorized_minor_amount: number
  attachments: Array<{ id: number; original_name: string }>
}

export type RecurringRule = { id: number; name: string; amount_minor: number; next_run_on: string | null }
export type UpcomingBill = { id: number; name: string; amount_minor: number; due_on: string | null; status: string }
export type SavingsGoal = { id: number; name: string; target_minor_amount: number; current_minor_amount: number; status: string }
export type Debt = { id: number; name: string; counterparty_name: string; remaining_minor_amount: number; status: string }
export type BackupLog = { id: number; status: string; path: string | null; size_bytes: number }
export type AuditLog = { id: number; event: string; created_at: string }
