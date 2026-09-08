import apiClient from "./apiClient";
import { toApiList, toApiItem } from "./helpers";

export function fetchAmenities(params = {}) {
  return apiClient.get("/amenities", { params }).then(toApiList);
}

export function fetchAmenity(id) {
  return apiClient.get(`/amenities/${id}`).then(toApiItem);
}