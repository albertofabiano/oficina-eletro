import { z } from "zod";
import type { MetaAccountInfo } from "./meta-platform";
import { normalizeAdAccountId } from "./meta-platform";

export const connectMetaSchema = z.object({
  adAccountId: z
    .string()
    .transform((value, ctx) => {
      const id = normalizeAdAccountId(value);
      if (!id) {
        ctx.addIssue({ code: "custom", message: "Informe o ID da conta de anúncios (números, com ou sem act_)." });
        return z.NEVER;
      }
      return id;
    }),
  accessToken: z
    .string()
    .trim()
    .min(20, { message: "Cole o token de acesso completo." })
    .max(2000, { message: "Token muito longo." })
    .regex(/^[A-Za-z0-9_\-|.]+$/, { message: "O token tem caracteres inválidos. Cole só o token, sem espaços." }),
});

const REQUIRED_CURRENCY = "BRL";
const REQUIRED_TIMEZONE = "America/Sao_Paulo";
const CLOSED_STATUSES = new Map([
  [2, "desativada"],
  [101, "encerrada"],
]);

/**
 * Checks that an account fits the platform's rules: money in BRL cents and
 * days in the São Paulo calendar. Returns an error message, or null when it fits.
 */
export function checkAccountRequirements(info: MetaAccountInfo): string | null {
  if (info.currency !== REQUIRED_CURRENCY) {
    return `A conta "${info.name}" usa a moeda ${info.currency}. Por enquanto só contas em Real (BRL) são aceitas.`;
  }
  if (info.timezone !== REQUIRED_TIMEZONE) {
    return `A conta "${info.name}" está no fuso ${info.timezone}. É preciso usar America/Sao_Paulo para os dias baterem com o painel.`;
  }
  const closed = CLOSED_STATUSES.get(info.accountStatus);
  if (closed) return `A conta "${info.name}" está ${closed} na Meta.`;
  return null;
}
