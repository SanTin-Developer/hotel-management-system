import apiClient from "@/lib/apiClient";

// POST /auth/login — actual response shape: { message, user, token }
export async function login({ email, password }) {
  const response = await apiClient.post("/auth/login", { email, password });
  return response.data; // { message, user, token }
}

// POST /auth/logout — revokes the current token server-side
export async function logout() {
  const response = await apiClient.post("/auth/logout");
  return response.data;
}

// GET /auth/me — current authenticated user
export async function getCurrentUser() {
  const response = await apiClient.get("/auth/me");
  return response.data.data;
}
