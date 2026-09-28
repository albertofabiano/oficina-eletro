-- Tracks when each ad account was last collected, shown in the dashboard.
alter table public.ad_accounts add column last_synced_at timestamptz;

-- ad_accounts uses column-level grants (the Vault reference stays hidden), so
-- new readable columns must be granted explicitly.
grant select (last_synced_at) on public.ad_accounts to authenticated;
