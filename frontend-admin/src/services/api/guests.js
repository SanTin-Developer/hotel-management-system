import { list, getOne, create, update, remove } from "./index";

export const fetchGuests = (params) => list("/guests", params);
export const fetchGuest = (id) => getOne(`/guests/${id}`);
export const createGuest = (data) => create("/guests", data);
export const updateGuest = (id, data) => update(`/guests/${id}`, data);
export const deleteGuest = (id) => remove(`/guests/${id}`);