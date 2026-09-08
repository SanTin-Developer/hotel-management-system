import { create } from "zustand";
import { persist } from "zustand/middleware";

const initialState = {
  checkIn: null,
  checkOut: null,
  adults: 2,
  children: 0,
  selectedRooms: [],
  guest: {
    full_name: "",
    email: "",
    phone: "",
    country: "",
    id_type: "national_id",
    id_number: "",
    address: "",
    special_request: "",
    coupon_code: "",
  },
};

export const useBookingStore = create(
  persist(
    (set, get) => ({
      ...initialState,

      setDates: (checkIn, checkOut) => set({ checkIn, checkOut }),

      setOccupancy: (adults, children) => set({ adults, children }),

      setSelectedRooms: (selectedRooms) => set({ selectedRooms }),

      toggleRoom: (room) => {
        const current = get().selectedRooms;
        const exists = current.some((r) => r.id === room.id);
        set({
          selectedRooms: exists
            ? current.filter((r) => r.id !== room.id)
            : [...current, room],
        });
      },

      setGuest: (guest) => set({ guest: { ...get().guest, ...guest } }),

      setCoupon: (couponCode) =>
        set({ guest: { ...get().guest, coupon_code: couponCode } }),

      reset: () => set(initialState),
    }),
    {
      name: "hotel-customer-booking",
      partialize: (state) => ({
        checkIn: state.checkIn,
        checkOut: state.checkOut,
        adults: state.adults,
        children: state.children,
        selectedRooms: state.selectedRooms,
        guest: state.guest,
      }),
    }
  )
);