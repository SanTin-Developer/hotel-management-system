import apiClient from "./apiClient";
import { toApiItem } from "./helpers";

export function fetchApprovedReviews() {
  return apiClient.get("/reviews/approved").then((res) => res.data?.data ?? []);
}

export function fetchReview(id) {
  return apiClient.get(`/reviews/${id}`).then(toApiItem);
}

export function createReview(payload) {
  return apiClient.post("/reviews", payload).then(toApiItem);
}