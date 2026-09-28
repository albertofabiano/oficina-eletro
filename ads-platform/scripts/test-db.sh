#!/usr/bin/env bash
# Applies the migrations to a throwaway local Postgres database and runs the SQL tests.
# Requires a local Postgres server; set PG* env vars (PGHOST, PGUSER, ...) as needed.
set -euo pipefail
cd "$(dirname "$0")/.."

DB="ads_platform_test_$$"
createdb "$DB"
trap 'dropdb --if-exists "$DB"' EXIT

run() { psql -X -q -v ON_ERROR_STOP=1 -d "$DB" "$@"; }

run -c 'create extension if not exists pgcrypto'
run -f supabase/tests/supabase-stub.sql
for migration in supabase/migrations/*.sql; do
  run -f "$migration"
done
run -f supabase/tests/database.test.sql
