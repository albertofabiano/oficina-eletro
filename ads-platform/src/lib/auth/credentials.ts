import { z } from "zod";

export const credentialsSchema = z.object({
  email: z.string().trim().toLowerCase().pipe(z.email({ message: "Informe um e-mail válido." })),
  password: z.string().min(8, { message: "A senha precisa ter pelo menos 8 caracteres." }).max(72),
});

export const organizationNameSchema = z
  .string()
  .trim()
  .min(2, { message: "Informe o nome da empresa." })
  .max(120, { message: "Nome muito longo." });

export interface FormState {
  error?: string;
  message?: string;
}

export function firstIssue(error: z.ZodError): string {
  return error.issues[0]?.message ?? "Dados inválidos.";
}
