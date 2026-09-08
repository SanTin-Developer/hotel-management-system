import { http } from "./axios";
import { useAuthStore } from "@/store/authStore";

http.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const { clearAuth } = useAuthStore.getState();
      clearAuth();
    }
    return Promise.reject(error);
  }
);

export default http;