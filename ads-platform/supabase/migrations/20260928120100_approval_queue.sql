-- Approval queue: every write to an ad platform starts here and needs a recorded human decision.

create table public.action_requests (
  id uuid primary key default gen_random_uuid(),
  organization_id uuid not null references public.organizations (id) on delete cascade,
  campaign_id uuid not null references public.campaigns (id) on delete cascade,
  action_type text not null check (action_type in ('pause_campaign', 'resume_campaign', 'update_daily_budget')),
  payload jsonb not null default '{}'::jsonb,
  reason text not null,
  source text not null check (source in ('rule', 'user')),
  rule_id text,
  status text not null default 'pending'
    check (status in ('pending', 'approved', 'rejected', 'executed', 'failed')),
  requested_by uuid references auth.users (id),
  requested_at timestamptz not null default now(),
  decided_by uuid references auth.users (id),
  decided_at timestamptz,
  executed_at timestamptz,
  dry_run boolean,
  error text,
  -- Anything past "pending" must carry who decided and when.
  constraint action_requests_decision_recorded check (
    status = 'pending' or (decided_by is not null and decided_at is not null)
  ),
  -- Only approved requests can ever be executed or fail during execution.
  constraint action_requests_execution_after_approval check (
    status not in ('executed', 'failed') or executed_at is not null
  ),
  constraint action_requests_budget_payload check (
    action_type <> 'update_daily_budget'
    or (jsonb_typeof(payload -> 'daily_budget_cents') = 'number'
        and (payload ->> 'daily_budget_cents')::bigint > 0)
  )
);

create index action_requests_org_status_idx on public.action_requests (organization_id, status);

-- At most one open suggestion of the same kind per campaign.
create unique index action_requests_one_pending_idx
  on public.action_requests (campaign_id, action_type)
  where status = 'pending';

create table public.audit_log (
  id bigint generated always as identity primary key,
  organization_id uuid not null references public.organizations (id) on delete cascade,
  actor_user_id uuid references auth.users (id),
  action text not null,
  entity_type text not null,
  entity_id uuid,
  details jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

create index audit_log_org_created_idx on public.audit_log (organization_id, created_at desc);

-- Status transitions allowed from the database side. Execution is done by the
-- service role, which bypasses RLS but not this trigger.
create function public.enforce_action_request_transition()
returns trigger
language plpgsql
as $$
begin
  if old.status = new.status then
    return new;
  end if;
  if not (
    (old.status = 'pending' and new.status in ('approved', 'rejected'))
    or (old.status = 'approved' and new.status in ('executed', 'failed'))
  ) then
    raise exception 'invalid action_request transition % -> %', old.status, new.status;
  end if;
  if old.status <> 'pending' and (new.decided_by is distinct from old.decided_by
                                  or new.decided_at is distinct from old.decided_at) then
    raise exception 'decision of an action_request cannot be changed';
  end if;
  return new;
end;
$$;

create trigger action_requests_transition
  before update on public.action_requests
  for each row execute function public.enforce_action_request_transition();
