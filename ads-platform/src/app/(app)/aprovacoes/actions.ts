"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { z } from "zod";
import type { FormState } from "@/lib/auth/credentials";
import { requireOrganization } from "@/lib/auth/session";
import { executeApproved } from "@/lib/jobs";
import { createAdminClient } from "@/lib/supabase/admin";
import { createClient } from "@/lib/supabase/server";

const decisionSchema = z.object({
  requestId: z.uuid(),
  decision: z.enum(["approved", "rejected"]),
});

export type DecisionOutcome = "aprovado-simulacao" | "aprovado" | "aprovado-pendente" | "falhou" | "rejeitado" | "erro";

export async function decideRequest(_state: FormState, formData: FormData): Promise<FormState> {
  const organization = await requireOrganization();
  const parsed = decisionSchema.safeParse({
    requestId: formData.get("requestId"),
    decision: formData.get("decision"),
  });
  if (!parsed.success) return { error: "Pedido inválido." };
  const { requestId, decision } = parsed.data;

  // The database function records the decision as the signed-in user and
  // checks membership and that the request is still pending.
  const supabase = await createClient();
  const { error } = await supabase.rpc("decide_action_request", { request_id: requestId, decision });

  let outcome: DecisionOutcome;
  if (error) {
    outcome = "erro";
  } else if (decision === "rejected") {
    outcome = "rejeitado";
  } else {
    outcome = "aprovado-pendente";
    try {
      const [result] = await executeApproved(createAdminClient(), { organizationId: organization.id, requestId });
      if (result?.status === "executed") outcome = result.dryRun ? "aprovado-simulacao" : "aprovado";
      else if (result?.status === "failed") outcome = "falhou";
    } catch (err) {
      // Stays approved; the daily routine retries the execution.
      console.error("execution after approval failed", err);
    }
  }

  // The decided item leaves the pending list, so the result is shown at page level.
  revalidatePath("/", "layout");
  redirect(`/aprovacoes?resultado=${outcome}`);
}
