-- Row level security: users only see data from organizations they belong to.
-- Writes to synced data and to the queue status go through the service role or
-- security definer functions, never through direct table updates.

create function public.is_org_member(org_id uuid)
returns boolean
language sql
stable
security definer
set search_path = ''
as $$
  select exists (
    select 1 from public.organization_members m
    where m.organization_id = org_id and m.user_id = (select auth.uid())
  );
$$;

alter table public.organizations enable row level security;
alter table public.organization_members enable row level security;
alter table public.ad_accounts enable row level security;
alter table public.campaigns enable row level security;
alter table public.daily_insights enable row level security;
alter table public.action_requests enable row level security;
alter table public.audit_log enable row level security;

create policy "members read organization" on public.organizations
  for select to authenticated using (public.is_org_member(id));

create policy "members read memberships" on public.organization_members
  for select to authenticated using (public.is_org_member(organization_id));

create policy "members read ad accounts" on public.ad_accounts
  for select to authenticated using (public.is_org_member(organization_id));

create policy "members read campaigns" on public.campaigns
  for select to authenticated using (public.is_org_member(organization_id));

create policy "members read insights" on public.daily_insights
  for select to authenticated using (public.is_org_member(organization_id));

create policy "members read action requests" on public.action_requests
  for select to authenticated using (public.is_org_member(organization_id));

create policy "members read audit log" on public.audit_log
  for select to authenticated using (public.is_org_member(organization_id));

-- Never expose the Vault reference to the browser: a column-level revoke has no
-- effect over a table-level grant, so grant only the safe columns instead.
revoke select on public.ad_accounts from anon, authenticated;
grant select (id, organization_id, platform, external_id, name, currency, status, created_at)
  on public.ad_accounts to authenticated;

-- Creates an organization with the caller as owner.
create function public.create_organization(org_name text)
returns uuid
language plpgsql
security definer
set search_path = ''
as $$
declare
  new_org_id uuid;
  caller uuid := (select auth.uid());
begin
  if caller is null then
    raise exception 'not authenticated';
  end if;

  insert into public.organizations (name) values (trim(org_name))
  returning id into new_org_id;

  insert into public.organization_members (organization_id, user_id, role)
  values (new_org_id, caller, 'owner');

  insert into public.audit_log (organization_id, actor_user_id, action, entity_type, entity_id)
  values (new_org_id, caller, 'organization.created', 'organization', new_org_id);

  return new_org_id;
end;
$$;

-- Records a human decision on a pending action request.
create function public.decide_action_request(request_id uuid, decision text)
returns void
language plpgsql
security definer
set search_path = ''
as $$
declare
  caller uuid := (select auth.uid());
  req public.action_requests;
begin
  if caller is null then
    raise exception 'not authenticated';
  end if;
  if decision not in ('approved', 'rejected') then
    raise exception 'invalid decision %', decision;
  end if;

  select * into req from public.action_requests where id = request_id for update;
  if not found or not public.is_org_member(req.organization_id) then
    raise exception 'action request not found';
  end if;
  if req.status <> 'pending' then
    raise exception 'action request already decided';
  end if;

  update public.action_requests
  set status = decision, decided_by = caller, decided_at = now()
  where id = request_id;

  insert into public.audit_log (organization_id, actor_user_id, action, entity_type, entity_id, details)
  values (req.organization_id, caller, 'action_request.' || decision, 'action_request', request_id,
          jsonb_build_object('action_type', req.action_type, 'payload', req.payload));
end;
$$;

revoke execute on function public.create_organization(text) from public, anon;
revoke execute on function public.decide_action_request(uuid, text) from public, anon;
grant execute on function public.create_organization(text) to authenticated;
grant execute on function public.decide_action_request(uuid, text) to authenticated;
