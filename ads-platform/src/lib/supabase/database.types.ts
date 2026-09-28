/**
 * Hand-written to match supabase/migrations. Regenerate with
 * `npx supabase gen types typescript --linked > src/lib/supabase/database.types.ts`
 * once the project is linked.
 */
type Timestamp = string;

type Table<Row, Required extends keyof Row = never> = {
  Row: Row;
  Insert: Partial<Row> & Pick<Row, Required>;
  Update: Partial<Row>;
  Relationships: [];
};

export type Database = {
  public: {
    Tables: {
      organizations: Table<{ id: string; name: string; created_at: Timestamp }, "name">;
      organization_members: Table<
        { organization_id: string; user_id: string; role: "owner" | "admin" | "member"; created_at: Timestamp },
        "organization_id" | "user_id"
      >;
      ad_accounts: Table<
        {
          id: string;
          organization_id: string;
          platform: "meta" | "fake";
          external_id: string;
          name: string;
          currency: "BRL";
          status: "active" | "disconnected";
          created_at: Timestamp;
          last_synced_at: Timestamp | null;
          last_sync_error: string | null;
        },
        "organization_id" | "platform" | "external_id" | "name"
      >;
      campaigns: Table<
        {
          id: string;
          organization_id: string;
          ad_account_id: string;
          external_id: string;
          name: string;
          status: "active" | "paused" | "archived";
          daily_budget_cents: number | null;
          synced_at: Timestamp;
        },
        "organization_id" | "ad_account_id" | "external_id" | "name" | "status"
      >;
      daily_insights: Table<
        {
          campaign_id: string;
          date: string;
          organization_id: string;
          spend_cents: number;
          impressions: number;
          clicks: number;
          leads: number;
          collected_at: Timestamp;
        },
        "campaign_id" | "date" | "organization_id"
      >;
      action_requests: Table<
        {
          id: string;
          organization_id: string;
          campaign_id: string;
          action_type: "pause_campaign" | "resume_campaign" | "update_daily_budget";
          payload: Record<string, unknown>;
          reason: string;
          source: "rule" | "user";
          rule_id: string | null;
          status: "pending" | "approved" | "rejected" | "executed" | "failed";
          requested_by: string | null;
          requested_at: Timestamp;
          decided_by: string | null;
          decided_at: Timestamp | null;
          executed_at: Timestamp | null;
          dry_run: boolean | null;
          error: string | null;
        },
        "organization_id" | "campaign_id" | "action_type" | "reason" | "source"
      >;
      audit_log: Table<
        {
          id: number;
          organization_id: string;
          actor_user_id: string | null;
          action: string;
          entity_type: string;
          entity_id: string | null;
          details: Record<string, unknown>;
          created_at: Timestamp;
        },
        "organization_id" | "action" | "entity_type"
      >;
    };
    Views: Record<never, never>;
    Functions: {
      create_organization: { Args: { org_name: string }; Returns: string };
      decide_action_request: { Args: { request_id: string; decision: "approved" | "rejected" }; Returns: undefined };
      is_org_member: { Args: { org_id: string }; Returns: boolean };
      connect_ad_account: {
        Args: {
          p_organization_id: string;
          p_user_id: string;
          p_platform: "meta";
          p_external_id: string;
          p_name: string;
          p_access_token: string;
        };
        Returns: string;
      };
      disconnect_ad_account: { Args: { p_ad_account_id: string; p_user_id: string }; Returns: undefined };
      get_ad_account_token: { Args: { p_ad_account_id: string }; Returns: string | null };
    };
    Enums: Record<never, never>;
    CompositeTypes: Record<never, never>;
  };
};
