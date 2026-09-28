-- Database tests: RLS isolation and approval queue rules.
-- Run with scripts/test-db.sh (plain Postgres + supabase-stub.sql).
\set ON_ERROR_STOP on
\o /dev/null

create function pg_temp.act_as(user_id uuid) returns void language plpgsql as $$
begin
  perform set_config('request.jwt.claim.sub', coalesce(user_id::text, ''), true);
  execute 'set local role ' || case when user_id is null then 'anon' else 'authenticated' end;
end;
$$;

create function pg_temp.expect_error(stmt text, label text) returns void language plpgsql as $$
begin
  begin
    execute stmt;
  exception when others then
    raise notice 'ok: % (%)', label, sqlerrm;
    return;
  end;
  raise exception 'FAIL: expected error: %', label;
end;
$$;

create function pg_temp.expect_eq(actual bigint, expected bigint, label text) returns void language plpgsql as $$
begin
  if actual is distinct from expected then
    raise exception 'FAIL: % (expected %, got %)', label, expected, actual;
  end if;
  raise notice 'ok: %', label;
end;
$$;

create function pg_temp.rows_affected(stmt text) returns bigint language plpgsql as $$
declare
  n bigint;
begin
  execute stmt;
  get diagnostics n = row_count;
  return n;
end;
$$;

grant execute on all functions in schema pg_temp to anon, authenticated;

insert into auth.users (id, email) values
  ('00000000-0000-0000-0000-00000000000a', 'ana@example.com'),
  ('00000000-0000-0000-0000-00000000000b', 'bruno@example.com');

-- Ana creates her organization through the RPC.
begin;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
select public.create_organization('Oficina da Ana') as ana_org \gset
select pg_temp.expect_eq((select count(*) from public.organizations), 1, 'Ana sees her organization');
select pg_temp.expect_eq(
  (select count(*) from public.organization_members where role = 'owner'), 1, 'Ana is owner');
select pg_temp.expect_error($$insert into public.organizations (name) values ('Hack')$$,
  'direct insert into organizations is blocked');
commit;

-- Bruno creates another organization.
begin;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000b');
select public.create_organization('Assistência do Bruno') as bruno_org \gset
commit;

-- Service role seeds synced data for both organizations (bypasses RLS like the collector).
begin;
set local role service_role;
insert into public.ad_accounts (id, organization_id, platform, external_id, name, token_secret_id) values
  ('10000000-0000-0000-0000-00000000000a', :'ana_org', 'fake', 'act_1', 'Conta Ana', gen_random_uuid()),
  ('10000000-0000-0000-0000-00000000000b', :'bruno_org', 'fake', 'act_2', 'Conta Bruno', null);
insert into public.campaigns (id, organization_id, ad_account_id, external_id, name, status, daily_budget_cents) values
  ('20000000-0000-0000-0000-00000000000a', :'ana_org', '10000000-0000-0000-0000-00000000000a', 'c1', 'TV', 'active', 4000),
  ('20000000-0000-0000-0000-00000000000b', :'bruno_org', '10000000-0000-0000-0000-00000000000b', 'c2', 'Celular', 'active', 3000);
insert into public.daily_insights (campaign_id, organization_id, date, spend_cents, impressions, clicks, leads) values
  ('20000000-0000-0000-0000-00000000000a', :'ana_org', '2026-09-27', 3800, 2000, 30, 2),
  ('20000000-0000-0000-0000-00000000000b', :'bruno_org', '2026-09-27', 2900, 1500, 20, 1);
insert into public.action_requests (id, organization_id, campaign_id, action_type, payload, reason, source, rule_id) values
  ('30000000-0000-0000-0000-00000000000a', :'ana_org', '20000000-0000-0000-0000-00000000000a',
   'update_daily_budget', '{"daily_budget_cents": 3000}', 'CPL alto', 'rule', 'high-cpl'),
  ('30000000-0000-0000-0000-00000000000b', :'bruno_org', '20000000-0000-0000-0000-00000000000b',
   'pause_campaign', '{}', 'Sem leads', 'rule', 'no-leads');
commit;

-- Isolation: each user only sees their own organization.
begin;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
select pg_temp.expect_eq((select count(*) from public.organizations), 1, 'Ana sees one organization');
select pg_temp.expect_eq((select count(*) from public.campaigns), 1, 'Ana sees only her campaigns');
select pg_temp.expect_eq((select count(*) from public.daily_insights), 1, 'Ana sees only her insights');
select pg_temp.expect_eq((select count(*) from public.action_requests), 1, 'Ana sees only her requests');
select pg_temp.expect_eq((select count(*) from public.ad_accounts), 1, 'Ana sees only her ad accounts');
select pg_temp.expect_error($$select token_secret_id from public.ad_accounts$$,
  'Vault reference is not readable by users');
select pg_temp.expect_eq(
  pg_temp.rows_affected($$update public.campaigns set daily_budget_cents = 1 where true$$),
  0, 'users cannot edit campaigns');
select pg_temp.expect_eq(
  pg_temp.rows_affected($$update public.action_requests set status = 'approved' where true$$),
  0, 'direct update of action_requests changes nothing');
select pg_temp.expect_error(
  $$select public.decide_action_request('30000000-0000-0000-0000-00000000000b', 'approved')$$,
  'Ana cannot decide Bruno''s request');
commit;

-- Anonymous users see nothing and cannot call RPCs.
begin;
select pg_temp.act_as(null);
select pg_temp.expect_eq((select count(*) from public.campaigns), 0, 'anon sees no campaigns');
select pg_temp.expect_error($$select public.create_organization('X')$$, 'anon cannot create organizations');
commit;

-- Approval flow.
begin;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
select pg_temp.expect_error(
  $$select public.decide_action_request('30000000-0000-0000-0000-00000000000a', 'executed')$$,
  'decision must be approved or rejected');
select public.decide_action_request('30000000-0000-0000-0000-00000000000a', 'approved');
select pg_temp.expect_eq(
  (select count(*) from public.action_requests
   where status = 'approved' and decided_by = '00000000-0000-0000-0000-00000000000a' and decided_at is not null),
  1, 'approval records who and when');
select pg_temp.expect_eq(
  (select count(*) from public.audit_log where action = 'action_request.approved'), 1, 'approval is audited');
select pg_temp.expect_error(
  $$select public.decide_action_request('30000000-0000-0000-0000-00000000000a', 'rejected')$$,
  'a decision cannot be changed');
commit;

-- Rules enforced even for the service role (executor).
begin;
set local role service_role;
select pg_temp.expect_error(
  $$update public.action_requests set status = 'executed', executed_at = now()
    where id = '30000000-0000-0000-0000-00000000000b'$$,
  'pending request cannot be executed');
select pg_temp.expect_error(
  $$update public.action_requests set status = 'approved' where id = '30000000-0000-0000-0000-00000000000b'$$,
  'approval without decided_by is rejected');
select pg_temp.expect_error(
  $$update public.action_requests set status = 'executed' where id = '30000000-0000-0000-0000-00000000000a'$$,
  'execution requires executed_at');
update public.action_requests set status = 'executed', executed_at = now(), dry_run = true
  where id = '30000000-0000-0000-0000-00000000000a';
select pg_temp.expect_eq(
  (select count(*) from public.action_requests where status = 'executed' and dry_run), 1, 'approved request executes');
select pg_temp.expect_error(
  $$update public.action_requests set status = 'pending' where id = '30000000-0000-0000-0000-00000000000a'$$,
  'executed request cannot go back');
select pg_temp.expect_error(
  $$insert into public.action_requests (organization_id, campaign_id, action_type, payload, reason, source)
    values ((select id from public.organizations where name = 'Oficina da Ana'), '20000000-0000-0000-0000-00000000000a', 'update_daily_budget', '{"daily_budget_cents": 12.5}', 'x', 'rule')$$,
  'budget must be integer cents');
select pg_temp.expect_error(
  $$insert into public.action_requests (organization_id, campaign_id, action_type, reason, source)
    values ((select id from public.organizations where name = 'Assistência do Bruno'), '20000000-0000-0000-0000-00000000000b', 'pause_campaign', 'dup', 'rule')$$,
  'only one pending request per campaign and action');
commit;

\o
\echo 'ALL DATABASE TESTS PASSED'
