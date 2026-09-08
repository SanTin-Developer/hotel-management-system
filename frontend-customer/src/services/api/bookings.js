import apiClient from "./apiClient";
import { toApiList } from "./helpers";

export function fetchMyBookings({ guestId, params = {} }) {
  return apiClient
    .get("/bookings", {
      params: { guest_id: guestId, ...params },
    })
    .then(toApiList);
}

export function createBooking(payload) {
  return apiClient.post("/bookings", payload);
}

export function payBookingDeposit(bookingId, paymentMethod, transactionId) {
  return apiClient
    .post(`/bookings/${bookingId}/deposit-payment`, {
      payment_method: paymentMethod,
      transaction_id: transactionId,
    })
    .then((res) => res.data);
}

export function requestBookingCancellation(bookingId) {
  return apiClient
    .post(`/bookings/${bookingId}/request-cancellation`)
    .then((res) => res.data);
}