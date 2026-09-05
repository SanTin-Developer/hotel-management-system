import { create } from "zustand";
import { persist } from "zustand/middleware";

/**
 * Auth store — holds ONLY client-side auth state:
 * - the logged-in user object
 * - the Sanctum bearer token
 * - helper getters for role checks
 *
 * Do NOT put server data here (bookings, rooms, guests, etc.)
 * — that belongs in TanStack Query, not Zustand.
 */
export const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null, // { id, name, email, role: 'admin' | 'manager' | 'staff' }
      token: null,

      // Called after a successful login API response
      setAuth: (user, token) => set({ user, token }),

      // Called on logout, or when a 401 comes back from the API
      clearAuth: () => set({ user: null, token: null }),

      isAuthenticated: () => !!get().token,

      // user.roles is an array of role objects: [{ id, name, guard_name, ... }]
      hasRole: (role) =>
        get().user?.roles?.some((r) => r.name === role) ?? false,

      hasAnyRole: (roles) =>
        get().user?.roles?.some((r) => roles.includes(r.name)) ?? false,
    }),
    {
      name: "hotel-admin-auth", // localStorage key
      partialize: (state) => ({ user: state.user, token: state.token }),
    },
  ),
);
