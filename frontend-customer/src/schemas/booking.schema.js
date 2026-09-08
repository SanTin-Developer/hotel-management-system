import { z } from "zod";
import { addDaysISO, todayISO } from "@/utils/formatDate";

export const searchSchema = z.object({
  check_in: z
    .string()
    .min(1, "Check-in date is required")
    .refine((value) => value >= todayISO(), "Check-in cannot be in the past"),
  check_out: z
    .string()
    .min(1, "Check-out date is required")
    .refine((value) => value > addDaysISO(todayISO(), 0), "Check-out date is invalid"),
  adults: z.coerce.number().int().min(1, "At least one adult").max(20),
  children: z.coerce.number().int().min(0).max(20).default(0),
});

export const guestInfoSchema = z.object({
  full_name: z.string().trim().min(2, "Full name is required").max(150),
  email: z.string().trim().email("Please enter a valid email"),
  phone: z.string().trim().min(7, "Please enter a valid phone number").max(30),
  country: z.string().trim().min(1, "Country is required").max(100),
  id_type: z.enum(["national_id", "passport"]),
  id_number: z.string().trim().min(1, "ID number is required").max(100),
  address: z.string().trim().max(300).optional().or(z.literal("")),
  special_request: z.string().trim().max(2000).optional().or(z.literal("")),
});