import apiClient from "./apiClient";
import { toApiList, toApiItem } from "./helpers";

// Room types
export function fetchRoomTypes(params = {}) {
  return apiClient.get("/room-types", { params }).then(toApiList);
}

export function fetchRoomType(id, params = {}) {
  return apiClient.get(`/room-types/${id}`, { params }).then(toApiItem);
}

// Rooms
export function fetchRooms(params = {}) {
  return apiClient.get("/rooms", { params }).then(toApiList);
}

export function fetchRoom(id, params = {}) {
  return apiClient.get(`/rooms/${id}`, {
    params: { include: params.include },
  }).then(toApiItem);
}

// Availability
export function fetchAvailableRooms({ checkIn, checkOut, params = {} }) {
  return apiClient
    .get("/bookings/availability", {
      params: { check_in: checkIn, check_out: checkOut, ...params },
    })
    .then((res) => res.data?.data ?? []);
}

export function fetchAvailabilityCalendar({ checkIn, checkOut }) {
  return apiClient
    .get("/bookings/calendar", {
      params: { check_in: checkIn, check_out: checkOut },
    })
    .then((res) => res.data);
}