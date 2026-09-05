import { getOne } from "./index";

export const fetchDashboard = () => getOne("/dashboard/summary");