import { z } from "zod";

export const codPaymentSchema = z.object({
  payment_method: z.literal("cash_on_arrival"),
  agree_terms: z.literal(true, {
    message: "Please accept the terms and conditions",
  }),
});