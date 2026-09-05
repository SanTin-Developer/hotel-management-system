import { list, getOne, create, update, remove } from "./index";
import apiClient from "@/lib/apiClient";

export const fetchStaff = (params) => list("/staff", params);
export const fetchStaffMember = (id) => getOne(`/staff/${id}`);
export const createStaff = (data) => create("/staff", data);
export const updateStaff = (id, data) => update(`/staff/${id}`, data);
export const deleteStaff = (id) => remove(`/staff/${id}`);
export const uploadStaffPhoto = (id, file) => {
  const formData = new FormData();
  formData.append("photo", file);
  return apiClient.post(`/staff/${id}/photo`, formData);
};
export const deleteStaffPhoto = (id) => remove(`/staff/${id}/photo`);