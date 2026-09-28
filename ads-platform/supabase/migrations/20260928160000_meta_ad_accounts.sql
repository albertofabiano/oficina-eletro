-- Real ad accounts (Meta). The access token lives only in Supabase Vault; these
-- security definer functions are the only code that touches it, and only the
-- service role (server-side jobs and actions) may execute them.

alter table public.ad_accounts add column last_sync_error text;
grant select (last_sync_error) on public.ad_accounts to authenticated;

-- Connects an ad account, or replaces the token of one already connected.
-- p_user_id comes from the verified session on the server; only owners and admins may connect.
create function public.connect_ad_account(
  p_organization_id uuid,
  p_user_id uuid,
  p_platform text,
  p_external_id text,
  p_name text,
  p_access_token text
)
returns uuid
language plpgsql
security definer
set search_path = ''
as $$
declare
  member_role text;
  existing public.ad_accounts;
  secret_id uuid;
  account_id uuid;
begin
  if p_platform <> 'meta' then
    raise exception 'unsupported platform %', p_platform;
  end if;
  if p_external_id !~ '^act_[0-9]{5,20}$' then
    raise exception 'invalid ad account id';
  end if;
  if coalesce(length(p_access_token), 0) < 20 then
    raise exception 'invalid access token';
  end if;

  select role into member_role
  from public.organization_members
  where organization_id = p_organization_id and user_id = p_user_id;
  if member_role is null or member_role not in ('owner', 'admin') then
    raise exception 'not allowed';
  end if;

  select * into existing
  from public.ad_accounts
  where organization_id = p_organization_id and platform = p_platform and external_id = p_external_id
  for update;

  if found and existing.token_secret_id is not null then
    perform vault.update_secret(existing.token_secret_id, p_access_token);
    secret_id := existing.token_secret_id;
  else
    secret_id := vault.create_secret(
      p_access_token,
      'ad_account_token:' || p_organization_id || ':' || p_platform || ':' || p_external_id,
      'Access token for an ad account'
    );
  end if;

  insert into public.ad_accounts (organization_id, platform, external_id, name, token_secret_id, status, last_sync_error)
  values (p_organization_id, p_platform, p_external_id, trim(p_name), secret_id, 'active', null)
  on conflict (organization_id, platform, external_id) do update
    set name = excluded.name,
        token_secret_id = excluded.token_secret_id,
        status = 'active',
        last_sync_error = null
  returning id into account_id;

  insert into public.audit_log (organization_id, actor_user_id, action, entity_type, entity_id, details)
  values (p_organization_id, p_user_id, 'ad_account.connected', 'ad_account', account_id,
          jsonb_build_object('platform', p_platform, 'external_id', p_external_id, 'token_replaced', found));

  return account_id;
end;
$$;

-- Stops collecting an account and deletes its token. Campaigns and history are kept.
create function public.disconnect_ad_account(p_ad_account_id uuid, p_user_id uuid)
returns void
language plpgsql
security definer
set search_path = ''
as $$
declare
  account public.ad_accounts;
  member_role text;
begin
  select * into account from public.ad_accounts where id = p_ad_account_id for update;
  if not found then
    raise exception 'ad account not found';
  end if;

  select role into member_role
  from public.organization_members
  where organization_id = account.organization_id and user_id = p_user_id;
  if member_role is null or member_role not in ('owner', 'admin') then
    raise exception 'not allowed';
  end if;

  update public.ad_accounts
  set status = 'disconnected', token_secret_id = null
  where id = p_ad_account_id;

  if account.token_secret_id is not null then
    delete from vault.secrets where id = account.token_secret_id;
  end if;

  insert into public.audit_log (organization_id, actor_user_id, action, entity_type, entity_id, details)
  values (account.organization_id, p_user_id, 'ad_account.disconnected', 'ad_account', p_ad_account_id,
          jsonb_build_object('platform', account.platform, 'external_id', account.external_id));
end;
$$;

-- Decrypted token of an active account, for the collector and the approval executor.
create function public.get_ad_account_token(p_ad_account_id uuid)
returns text
language sql
stable
security definer
set search_path = ''
as $$
  select s.decrypted_secret
  from public.ad_accounts a
  join vault.decrypted_secrets s on s.id = a.token_secret_id
  where a.id = p_ad_account_id and a.status = 'active';
$$;

revoke execute on function public.connect_ad_account(uuid, uuid, text, text, text, text) from public, anon, authenticated;
revoke execute on function public.disconnect_ad_account(uuid, uuid) from public, anon, authenticated;
revoke execute on function public.get_ad_account_token(uuid) from public, anon, authenticated;
grant execute on function public.connect_ad_account(uuid, uuid, text, text, text, text) to service_role;
grant execute on function public.disconnect_ad_account(uuid, uuid) to service_role;
grant execute on function public.get_ad_account_token(uuid) to service_role;
