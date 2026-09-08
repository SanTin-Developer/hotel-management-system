import apiClient from "./apiClient";

export function fetchActiveCoupons() {
  return apiClient.get("/coupons/active").then((res) => res.data?.data ?? []);
}

export function validateCoupon(code, amount = 0) {
  return apiClient
    .get(`/coupons/validate/${encodeURIComponent(code)}`, {
      params: { amount },
    })
    .then((res) => res.data);
}