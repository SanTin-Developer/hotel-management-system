import { z } from "zod";

export const loginSchema = z.object({
  email: z
    .string()
    .trim()
    .min(1, "Please enter your email or phone number")
    .max(255),
  password: z.string().min(1, "Password is required"),
});

export const registerSchema = z
  .object({
    full_name: z
      .string()
      .trim()
      .min(2, "Full name must be at least 2 characters")
      .max(150, "Full name is too long"),
    country: z.string().trim().min(1, "Country is required").max(100),
    nationality: z.string().trim().min(1, "Nationality is required").max(100),
    date_of_birth: z.string().trim().min(1, "Date of birth is required"),
    address: z.string().trim().min(1, "Address is required").max(255),
    id_type: z.enum(["national_id", "passport"], {
      message: "Please select an ID type",
    }),
    id_number: z.string().trim().min(1, "ID number is required").max(100),
    email: z.string().trim().email("Please enter a valid email address"),
    phone: z
      .string()
      .trim()
      .min(7, "Please enter a valid phone number")
      .max(30, "Phone number is too long"),
    password: z.string().min(8, "Password must be at least 8 characters"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords do not match",
    path: ["password_confirmation"],
  });

export const verifyOtpSchema = z.object({
  otp: z
    .string()
    .trim()
    .regex(/^\d{6}$/, "OTP must be exactly 6 digits"),
});

export const forgotPasswordSchema = z.object({
  email: z.string().trim().email("Please enter a valid email address"),
});

export const resetPasswordSchema = z
  .object({
    email: z.string().trim().email("Please enter a valid email address"),
    otp: z.string().trim().min(6, "Reset code must be at least 6 digits"),
    password: z.string().min(8, "Password must be at least 8 characters"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords do not match",
    path: ["password_confirmation"],
  });