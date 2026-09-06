import { useState, useRef, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import {
  Menu,
  Search,
  LogOut,
  UserRound,
  ChevronDown,
  Loader2,
  ArrowRight,
} from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import { logout } from "@/features/auth/authApi";
import { fetchBookings } from "@/services/api/bookings";
import { fetchGuests } from "@/services/api/guests";
import { fetchRooms } from "@/services/api/rooms";
import { fetchCoupons } from "@/services/api/coupons";
import { toast } from "sonner";
import LoadingOverlay from "@/components/LoadingOverlay";
import DebouncedInput from "@/components/DebouncedInput";

const SEARCH_TARGETS = [
  {
    key: "bookings",
    label: "Bookings",
    page: "/bookings",
    fetch: (params) => fetchBookings(params),
    title: (item) => item.booking_code,
    subtitle: (item) =>
      item.guest ? `${item.guest.full_name} · ${item.status}` : item.status,
  },
  {
    key: "guests",
    label: "Guests",
    page: "/guests",
    fetch: (params) => fetchGuests(params),
    title: (item) => item.full_name,
    subtitle: (item) => item.email || item.phone,
  },
  {
    key: "rooms",
    label: "Rooms",
    page: "/rooms",
    fetch: (params) => fetchRooms(params),
    title: (item) => `${item.room_number} · ${item.room_type?.name ?? ""}`,
    subtitle: (item) => item.status,
  },
  {
    key: "coupons",
    label: "Coupons",
    page: "/coupons",
    fetch: (params) => fetchCoupons(params),
    title: (item) => item.code,
    subtitle: (item) => item.status,
  },
];

export default function Navbar({ onOpenSidebar }) {
  const { user, clearAuth } = useAuthStore();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [q, setQ] = useState("");
  const menuRef = useRef(null);
  const searchRef = useRef(null);

  useEffect(() => {
    function handleClick(e) {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setMenuOpen(false);
      }
      if (searchRef.current && !searchRef.current.contains(e.target)) {
        setSearchOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  const globalQuery = useQuery({
    queryKey: ["navbar-search", q],
    queryFn: async () => {
      const params = { search: q, per_page: 4 };
      const [bookings, guests, rooms, coupons] = await Promise.all([
        fetchBookings(params),
        fetchGuests(params),
        fetchRooms(params),
        fetchCoupons(params),
      ]);
      return { bookings, guests, rooms, coupons };
    },
    enabled: q.trim().length > 0,
    staleTime: 60_000,
  });

  function goTo(page, term) {
    setSearchOpen(false);
    setQ(term);
    navigate(`${page}?q=${encodeURIComponent(term)}`);
  }

  async function handleLogout() {
    setMenuOpen(false);
    setLoggingOut(true);
    try {
      await logout();
    } catch {
      // even if it fails, clear local state so the user can leave
    } finally {
      clearAuth();
      toast.success("Logged out successfully. See you next time!");
      navigate("/login");
    }
  }

  const initials = (user?.name || "U")
    .split(" ")
    .map((n) => n[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-[#DCE3D5] bg-[#F8F9F4]/90 px-4 backdrop-blur-md sm:px-6 lg:px-8">
      <button
        type="button"
        onClick={onOpenSidebar}
        aria-label="Open menu"
        className="flex h-9 w-9 items-center justify-center rounded-lg border border-[#DCE3D5] bg-white text-[#5E6B5A] transition-colors hover:text-[#1E2B22] lg:hidden"
      >
        <Menu className="h-4 w-4" strokeWidth={1.5} />
      </button>

      <div className="relative hidden w-full max-w-sm md:block" ref={searchRef}>
        <Search
          className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#A8B39F]"
          strokeWidth={1.5}
        />
        <DebouncedInput
          type="search"
          placeholder="Search bookings, guests, rooms…"
          value={q}
          onChange={(v) => {
            setQ(v);
            setSearchOpen(v.trim().length > 0);
          }}
          className="h-10 w-full rounded-lg border border-[#DCE3D5] bg-white pl-10 pr-3 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:border-[#7FA35C] focus:ring-4 focus:ring-[#7FA35C]/15"
        />
        {searchOpen && (
          <div className="absolute left-0 right-0 top-full mt-2 origin-top-left rounded-xl border border-[#DCE3D5] bg-white shadow-lg ring-1 ring-[#000]/5 animate-in fade-in-0 zoom-in-95 duration-150">
            {globalQuery.isPending ? (
              <div className="flex items-center gap-2 px-4 py-3 text-sm text-[#7A8677]">
                <Loader2 className="h-4 w-4 animate-spin" strokeWidth={1.5} />
                Searching…
              </div>
            ) : globalQuery.isError ? (
              <div className="px-4 py-3 text-sm text-[#B3453A]">
                Search failed. Please try again.
              </div>
            ) : (
              <div className="max-h-[70vh] overflow-y-auto p-1.5">
                {SEARCH_TARGETS.filter(
                  (target) =>
                    globalQuery.data?.[target.key]?.items?.length > 0
                ).length === 0 ? (
                  <div className="px-4 py-3 text-sm text-[#7A8677]">
                    No results for “{q}”.
                  </div>
                ) : (
                  SEARCH_TARGETS.map((target) => {
                    const items = globalQuery.data?.[target.key]?.items ?? [];
                    if (items.length === 0) return null;

                    return (
                      <div key={target.key} className="mb-1">
                        <div className="flex items-center justify-between px-3 py-1.5">
                          <span className="text-xs font-semibold uppercase tracking-wide text-[#7A8677]">
                            {target.label}
                          </span>
                          <button
                            type="button"
                            onClick={() => goTo(target.page, q)}
                            className="flex items-center gap-1 text-xs font-medium text-[#7FA35C] transition-colors hover:text-[#16261F]"
                          >
                            See all
                            <ArrowRight className="h-3 w-3" strokeWidth={1.5} />
                          </button>
                        </div>
                        {items.slice(0, 3).map((item) => (
                          <button
                            key={`${target.key}-${item.id}`}
                            type="button"
                            onClick={() => goTo(target.page, q)}
                            className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors hover:bg-[#F1F3ED]"
                          >
                            <span className="min-w-0 flex-1">
                              <span className="block truncate text-sm font-medium text-[#1E2B22]">
                                {target.title(item)}
                              </span>
                              <span className="block truncate text-xs text-[#7A8677]">
                                {target.subtitle(item)}
                              </span>
                            </span>
                          </button>
                        ))}
                      </div>
                    );
                  })
                )}
              </div>
            )}
          </div>
        )}
      </div>

      <div className="ml-auto flex items-center gap-2">
        <div className="relative" ref={menuRef}>
          <button
            type="button"
            onClick={() => setMenuOpen((v) => !v)}
            className="flex items-center gap-3 rounded-lg border border-[#DCE3D5] bg-white py-1.5 pl-1.5 pr-3 transition-all duration-200 hover:border-[#7FA35C]/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7FA35C]/15"
          >
            <span className="flex h-8 w-8 items-center justify-center rounded-md bg-[#16261F] text-xs font-semibold text-[#9DBE7C]">
              {initials}
            </span>
            <span className="hidden text-left sm:block">
              <span className="block max-w-[140px] truncate text-sm font-medium text-[#1E2B22]">
                {user?.name}
              </span>
              <span className="block text-xs capitalize text-[#7A8677]">
                {user?.roles?.[0]?.name}
              </span>
            </span>
            <ChevronDown
              className={`h-4 w-4 text-[#7A8677] transition-transform duration-200 ${menuOpen ? "rotate-180" : ""}`}
              strokeWidth={1.5}
            />
          </button>

          {menuOpen && (
            <div className="absolute right-0 top-full mt-2 w-56 origin-top-right rounded-xl border border-[#DCE3D5] bg-white p-1.5 shadow-lg ring-1 ring-[#000]/5 animate-in fade-in-0 zoom-in-95 duration-150">
              <div className="border-b border-[#EEF1E9] px-3 py-2.5">
                <p className="text-sm font-medium text-[#1E2B22]">{user?.name}</p>
                <p className="truncate text-xs text-[#7A8677]">{user?.email}</p>
              </div>
              <Link
                to="/profile"
                onClick={() => setMenuOpen(false)}
                className="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
              >
                <UserRound className="h-4 w-4" strokeWidth={1.5} />
                Profile
              </Link>
              <button
                type="button"
                onClick={handleLogout}
                disabled={loggingOut}
                className="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-[#B3453A] transition-colors hover:bg-[#C25B50]/10 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {loggingOut ? (
                  <Loader2 className="h-4 w-4 animate-spin" strokeWidth={1.5} />
                ) : (
                  <LogOut className="h-4 w-4" strokeWidth={1.5} />
                )}
                {loggingOut ? "Signing out…" : "Sign out"}
              </button>
            </div>
          )}
        </div>
      </div>

      {loggingOut && <LoadingOverlay label="Signing out…" />}
    </header>
  );
}