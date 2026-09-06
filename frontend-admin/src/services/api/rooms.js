import { list, getOne, create, update, remove, action } from "./index";
import apiClient from "@/lib/apiClient";

export const fetchRooms = (params) => list("/rooms", params);
export const fetchRoom = (id) => getOne(`/rooms/${id}`);
export const createRoom = (data) => create("/rooms", data);
export const updateRoom = (id, data) => update(`/rooms/${id}`, data);
export const deleteRoom = (id) => remove(`/rooms/${id}`);
export const changeRoomStatus = (id, data) =>
  action(`/rooms/${id}/status`, "put", data);
export const uploadRoomImage = (id, file) => {
  const formData = new FormData();
  formData.append("image", file);
  return apiClient.post(`/rooms/${id}/image`, formData);
};
export const deleteRoomImage = (id) => remove(`/rooms/${id}/image`);

export const fetchRoomTypes = (params) => list("/room-types", params);
export const fetchRoomType = (id) => getOne(`/room-types/${id}`);
export const createRoomType = (data) => create("/room-types", data);
export const updateRoomType = (id, data) => update(`/room-types/${id}`, data);
export const deleteRoomType = (id) => remove(`/room-types/${id}`);
export const uploadRoomTypeImage = (id, file) => {
  const formData = new FormData();
  formData.append("image", file);
  return apiClient.post(`/room-types/${id}/image`, formData);
};
export const deleteRoomTypeImage = (id) => remove(`/room-types/${id}/image`);