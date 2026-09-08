import axios from "axios";

export const BASE_URL =
  import.meta.env.VITE_API_BASE_URL ||
  "https://hotel-management-system-vhox.onrender.com/api/v1";

export const http = axios.create({
  baseURL: BASE_URL,
  headers: {
    Accept: "application/json",
  },
});
