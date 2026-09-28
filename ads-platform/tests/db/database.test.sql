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
select pg_temp.expect_eq((select count(*) from (select last_synced_at from public.ad_accounts) s), 1,
  'sync time is readable by users');
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

-- Real ad accounts: token only in Vault, functions only for the service role.
insert into auth.users (id, email) values ('00000000-0000-0000-0000-00000000000c', 'carla@example.com');
insert into public.organization_members (organization_id, user_id, role)
  values (:'ana_org', '00000000-0000-0000-0000-00000000000c', 'member');

begin;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
select pg_temp.expect_error(
  format($$select public.connect_ad_account(%L, '00000000-0000-0000-0000-00000000000a', 'meta', 'act_123456789', 'Ana Meta', 'EAAtoken-aaaaaaaaaaaaaaaaaaaa')$$, :'ana_org'),
  'users cannot call connect_ad_account directly');
select pg_temp.expect_error(
  $$select public.get_ad_account_token('10000000-0000-0000-0000-00000000000a')$$,
  'users cannot read tokens');
select pg_temp.expect_error(
  $$select public.disconnect_ad_account('10000000-0000-0000-0000-00000000000a', '00000000-0000-0000-0000-00000000000a')$$,
  'users cannot call disconnect_ad_account directly');
commit;

begin;
set local role service_role;
select pg_temp.expect_error(
  format($$select public.connect_ad_account(%L, '00000000-0000-0000-0000-00000000000b', 'meta', 'act_123456789', 'Ana Meta', 'EAAtoken-aaaaaaaaaaaaaaaaaaaa')$$, :'ana_org'),
  'non-members cannot connect accounts');
select pg_temp.expect_error(
  format($$select public.connect_ad_account(%L, '00000000-0000-0000-0000-00000000000c', 'meta', 'act_123456789', 'Ana Meta', 'EAAtoken-aaaaaaaaaaaaaaaaaaaa')$$, :'ana_org'),
  'plain members cannot connect accounts');
select pg_temp.expect_error(
  format($$select public.connect_ad_account(%L, '00000000-0000-0000-0000-00000000000a', 'meta', '123; drop', 'Ana Meta', 'EAAtoken-aaaaaaaaaaaaaaaaaaaa')$$, :'ana_org'),
  'invalid ad account id is rejected');
select pg_temp.expect_error(
  format($$select public.connect_ad_account(%L, '00000000-0000-0000-0000-00000000000a', 'fake', 'act_123456789', 'Ana Meta', 'EAAtoken-aaaaaaaaaaaaaaaaaaaa')$$, :'ana_org'),
  'only real platforms take tokens');
select public.connect_ad_account(:'ana_org', '00000000-0000-0000-0000-00000000000a', 'meta', 'act_123456789', 'Ana Meta', 'EAAtoken-first-aaaaaaaaaaaaaaa') as meta_account \gset
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts where id = :'meta_account' and token_secret_id is not null and status = 'active'), 1,
  'connected account references a Vault secret');
reset role; -- inspect Vault as the database owner
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts a join vault.decrypted_secrets s on s.id = a.token_secret_id
   where a.id = :'meta_account' and s.decrypted_secret = 'EAAtoken-first-aaaaaaaaaaaaaaa'), 1,
  'token is stored in Vault');
set local role service_role;
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts where id = :'meta_account' and public.get_ad_account_token(id) = 'EAAtoken-first-aaaaaaaaaaaaaaa'), 1,
  'service role reads the token');
update public.ad_accounts set last_sync_error = 'Token inválido' where id = :'meta_account';
select public.connect_ad_account(:'ana_org', '00000000-0000-0000-0000-00000000000a', 'meta', 'act_123456789', 'Ana Meta 2', 'EAAtoken-second-aaaaaaaaaaaaaa') as reconnected \gset
select pg_temp.expect_eq((select count(*) from public.ad_accounts where id = :'reconnected' and id = :'meta_account'), 1,
  'reconnecting returns the same account');
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts where id = :'meta_account'
     and public.get_ad_account_token(id) = 'EAAtoken-second-aaaaaaaaaaaaaa' and name = 'Ana Meta 2' and last_sync_error is null), 1,
  'reconnecting replaces the token and clears the error');
reset role;
select pg_temp.expect_eq((select count(*) from vault.secrets where name like 'ad_account_token:%'), 1, 'token replacement reuses the secret');
set local role service_role;
select pg_temp.expect_eq(
  (select count(*) from public.audit_log where action = 'ad_account.connected' and entity_id = :'meta_account'), 2,
  'connections are audited');
select pg_temp.expect_eq(
  (select count(*) from public.audit_log where action = 'ad_account.connected' and details::text like '%EAAtoken%'), 0,
  'audit log never contains the token');
commit;

begin;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
select pg_temp.expect_error($$select token_secret_id from public.ad_accounts$$, 'Vault reference stays hidden');
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts where platform = 'meta' and last_sync_error is null), 1,
  'members read the connected account and its sync error');
select pg_temp.expect_error($$select * from vault.decrypted_secrets$$, 'users cannot read Vault');
commit;

begin;
set local role service_role;
select pg_temp.expect_error(
  format($$select public.disconnect_ad_account(%L, '00000000-0000-0000-0000-00000000000c')$$, :'meta_account'),
  'plain members cannot disconnect');
select public.disconnect_ad_account(:'meta_account', '00000000-0000-0000-0000-00000000000a');
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts where id = :'meta_account' and status = 'disconnected' and token_secret_id is null), 1,
  'disconnect marks the account');
reset role;
select pg_temp.expect_eq((select count(*) from vault.secrets where name like 'ad_account_token:%'), 0, 'disconnect deletes the token');
set local role service_role;
select pg_temp.expect_eq(
  (select count(*) from public.ad_accounts where id = :'meta_account' and public.get_ad_account_token(id) is null), 1,
  'no token after disconnect');
commit;

\o
\echo 'ALL DATABASE TESTS PASSED'
