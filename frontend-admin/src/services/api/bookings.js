import { list, create, action } from "./index";
import apiClient from "@/lib/apiClient";

export const fetchBookings = (params) => list("/bookings", params);
export const createBooking = (data) => create("/bookings", data);
export const confirmBooking = (id) => action(`/bookings/${id}/confirm`);
export const cancelBooking = (id) => action(`/bookings/${id}/cancel`);
export const completeBooking = (id) => action(`/bookings/${id}/complete`);
export const fetchBookingPayments = (id) => list(`/bookings/${id}/payments`);
export const fetchAvailability = async (params) => {
  const res = await apiClient.get("/bookings/availability", { params });
  return res.data;
};