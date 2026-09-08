import axios from "axios";

export const BASE_URL =
  import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api/v1";

export const http = axios.create({
  baseURL: BASE_URL,
  headers: {
    Accept: "application/json",
  },
});