import { list, getOne, create, update, remove } from "./index";

export const fetchCoupons = (params) => list("/coupons", params);
export const fetchCoupon = (id) => getOne(`/coupons/${id}`);
export const createCoupon = (data) => create("/coupons", data);
export const updateCoupon = (id, data) => update(`/coupons/${id}`, data);
export const deleteCoupon = (id) => remove(`/coupons/${id}`);