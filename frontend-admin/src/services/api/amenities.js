import { list, getOne, create, update, remove } from "./index";

export const fetchAmenities = (params) => list("/amenities", params);
export const fetchAmenity = (id) => getOne(`/amenities/${id}`);
export const createAmenity = (data) => create("/amenities", data);
export const updateAmenity = (id, data) => update(`/amenities/${id}`, data);
export const deleteAmenity = (id) => remove(`/amenities/${id}`);