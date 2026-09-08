import { z } from "zod";

export function isRequired(value) {
  return value !== null && value !== undefined && String(value).trim() !== "";
}

export function toFormErrors(error) {
  if (error?.response?.data?.errors) {
    return error.response.data.errors;
  }
  return null;
}

export function firstError(error) {
  const errors = toFormErrors(error);
  if (!errors) return null;
  const [field] = Object.keys(errors);
  return Array.isArray(errors[field]) ? errors[field][0] : errors[field];
}

export function extractApiMessage(error, fallback = "Something went wrong. Please try again.") {
  if (error?.response?.data?.message) {
    return error.response.data.message;
  }
  if (error?.message) {
    return error.message;
  }
  return fallback;
}

export function isApiValidationError(error) {
  return Boolean(error?.response?.data?.errors);
}

export const emailSchema = z.string().trim().email("Please enter a valid email address");

export const passwordSchema = z
  .string()
  .min(8, "Password must be at least 8 characters");

export const phoneSchema = z
  .string()
  .trim()
  .min(7, "Please enter a valid phone number")
  .max(30, "Phone number is too long");