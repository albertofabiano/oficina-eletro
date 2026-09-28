-- Core multi-tenant schema: organizations, ad accounts, campaigns and daily insights.
-- Money is stored as integer cents (bigint). Dates are São Paulo calendar days.

create table public.organizations (
  id uuid primary key default gen_random_uuid(),
  name text not null check (length(trim(name)) between 2 and 120),
  created_at timestamptz not null default now()
);

create table public.organization_members (
  organization_id uuid not null references public.organizations (id) on delete cascade,
  user_id uuid not null references auth.users (id) on delete cascade,
  role text not null default 'member' check (role in ('owner', 'admin', 'member')),
  created_at timestamptz not null default now(),
  primary key (organization_id, user_id)
);

create index organization_members_user_id_idx on public.organization_members (user_id);

create table public.ad_accounts (
  id uuid primary key default gen_random_uuid(),
  organization_id uuid not null references public.organizations (id) on delete cascade,
  platform text not null check (platform in ('meta', 'fake')),
  external_id text not null,
  name text not null,
  currency text not null default 'BRL' check (currency = 'BRL'),
  -- Reference to the access token in Supabase Vault. The token itself never lives in this table.
  token_secret_id uuid,
  status text not null default 'active' check (status in ('active', 'disconnected')),
  created_at timestamptz not null default now(),
  unique (organization_id, platform, external_id)
);

create table public.campaigns (
  id uuid primary key default gen_random_uuid(),
  organization_id uuid not null references public.organizations (id) on delete cascade,
  ad_account_id uuid not null references public.ad_accounts (id) on delete cascade,
  external_id text not null,
  name text not null,
  status text not null check (status in ('active', 'paused', 'archived')),
  daily_budget_cents bigint check (daily_budget_cents >= 0),
  synced_at timestamptz not null default now(),
  unique (ad_account_id, external_id)
);

create index campaigns_organization_id_idx on public.campaigns (organization_id);

create table public.daily_insights (
  campaign_id uuid not null references public.campaigns (id) on delete cascade,
  date date not null,
  organization_id uuid not null references public.organizations (id) on delete cascade,
  spend_cents bigint not null default 0 check (spend_cents >= 0),
  impressions bigint not null default 0 check (impressions >= 0),
  clicks bigint not null default 0 check (clicks >= 0),
  leads integer not null default 0 check (leads >= 0),
  collected_at timestamptz not null default now(),
  primary key (campaign_id, date)
);

create index daily_insights_org_date_idx on public.daily_insights (organization_id, date);
