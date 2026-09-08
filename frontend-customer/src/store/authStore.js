import { create } from "zustand";
import { persist } from "zustand/middleware";

export const useAuthStore = create(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      guest: null,

      setAuth: ({ token, user }) => {
        set({
          token: token ?? get().token,
          user: user ?? get().user,
          guest: user?.guest ?? get().guest,
        });
      },

      setUser: (user) => {
        set({ user, guest: user?.guest ?? null });
      },

      setGuest: (guest) => {
        set({ guest });
      },

      isAuthenticated: () => Boolean(get().token),

      clearAuth: () => {
        set({ token: null, user: null, guest: null });
      },
    }),
    {
      name: "hotel-customer-auth",
      partialize: (state) => ({
        token: state.token,
        user: state.user,
        guest: state.guest,
      }),
    }
  )
);