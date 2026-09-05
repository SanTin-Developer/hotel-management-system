import { z } from "zod";

export const loginSchema = z.object({
  email: z.string().min(1, "Please enter your email").email("Email is invalid"),
  password: z
    .string()
    .min(1, "Please enter your password")
    .min(8, "Password must be at least 8 characters long"),
});
