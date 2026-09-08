import apiClient from "@/services/api/apiClient";

export const authService = {
  async register(payload) {
    const res = await apiClient.post("/auth/register", payload);
    return res.data;
  },

  async verifyOtp(payload) {
    const res = await apiClient.post("/auth/verify-otp", payload);
    return res.data;
  },

  async resendOtp(payload) {
    const res = await apiClient.post("/auth/resend-otp", payload);
    return res.data;
  },

  async login(payload) {
    const res = await apiClient.post("/auth/login", payload);
    return res.data;
  },

  async me() {
    const res = await apiClient.get("/auth/me");
    return res.data;
  },

  async updateProfile(payload) {
    const res = await apiClient.put("/auth/me", payload);
    return res.data;
  },

  async uploadPhoto(file) {
    const formData = new FormData();
    formData.append("photo", file);
    const res = await apiClient.post("/auth/me/photo", formData);
    return res.data;
  },

  async removePhoto() {
    const res = await apiClient.delete("/auth/me/photo");
    return res.data;
  },

  async logout() {
    const res = await apiClient.post("/auth/logout");
    return res.data;
  },

  async forgotPassword(payload) {
    const res = await apiClient.post("/auth/password/forgot", payload);
    return res.data;
  },

  async resetPassword(payload) {
    const res = await apiClient.post("/auth/password/reset", payload);
    return res.data;
  },
};