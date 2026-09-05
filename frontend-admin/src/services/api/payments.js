import { list, create, action } from "./index";

export const fetchPayments = (params) => list("/payments", params);
export const createPayment = (data) => create("/payments", data);
export const markPaid = (id, transactionId) =>
  action(`/payments/${id}/paid`, "post", transactionId ? { transaction_id: transactionId } : {});
export const markFailed = (id) => action(`/payments/${id}/failed`);
export const refundPayment = (id, transactionId) =>
  action(`/payments/${id}/refund`, "post", transactionId ? { transaction_id: transactionId } : {});
export const fetchBookingsForPayment = (params) => list("/bookings", params);