import { useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import { authService } from "@/services/auth/authService";
import { ROUTES } from "@/constants/routes";

export function useAuth() {
  const navigate = useNavigate();
  const { token, user, guest, setAuth, setUser, clearAuth } = useAuthStore();

  const isAuthenticated = Boolean(token);

  const login = useCallback(
    async (credentials) => {
      const result = await authService.login(credentials);
      setAuth(result);
      return result;
    },
    [setAuth]
  );

  const logout = useCallback(async () => {
    try {
      if (useAuthStore.getState().token) {
        await authService.logout();
      }
    } catch {
      // ignore network errors during logout
    } finally {
      clearAuth();
      navigate(ROUTES.home);
    }
  }, [clearAuth, navigate]);

  const refreshUser = useCallback(async () => {
    const result = await authService.me();
    setUser(result.user);
    return result.user;
  }, [setUser]);

  const updateProfile = useCallback(
    async (payload) => {
      const result = await authService.updateProfile(payload);
      setAuth(result);
      return result.user;
    },
    [setAuth]
  );

  const uploadPhoto = useCallback(
    async (file) => {
      const result = await authService.uploadPhoto(file);
      setAuth(result);
      return result.user;
    },
    [setAuth]
  );

  const removePhoto = useCallback(async () => {
    const result = await authService.removePhoto();
    setAuth(result);
    return result.user;
  }, [setAuth]);

  return {
    token,
    user,
    guest,
    isAuthenticated,
    login,
    logout,
    refreshUser,
    updateProfile,
    uploadPhoto,
    removePhoto,
    setAuth,
  };
}