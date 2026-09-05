import { list, create, action } from "./index";

export const fetchReviews = (params) => list("/reviews", params);
export const createReview = (data) => create("/reviews", data);
export const approveReview = (id) => action(`/reviews/${id}/approve`);
export const rejectReview = (id) => action(`/reviews/${id}/reject`);