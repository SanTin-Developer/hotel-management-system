import { useEffect, useRef, useState } from "react";
import { Link, NavLink, useLocation, useNavigate } from "react-router-dom";
import { AnimatePresence, motion } from "framer-motion";
import { useQuery } from "@tanstack/react-query";
import { Menu, Search, ChevronDown } from "lucide-react";
import { Logo } from "./Logo";
import { LanguageToggle } from "@/components/common/LanguageToggle";
import { RoomImage } from "@/components/common/RoomImage";
import { Button } from "@/components/ui/button";
import { avatarFallback } from "@/lib/avatar";
import { useI18n } from "@/i18n";
import { useAuth } from "@/hooks/useAuth";
import { fetchRoomTypes } from "@/services/api/rooms";
import { roomTypeToSlug } from "@/utils/rooms";
import { formatCurrency } from "@/utils/formatCurrency";
import { ROUTES, HOTEL_NAME } from "@/constants/routes";
import { cn } from "@/lib/utils";

const NAV_LINKS = [
  { to: ROUTES.home, key: "nav.home" },
  { to: ROUTES.about, key: "nav.about" },
  { to: ROUTES.amenities, key: "nav.amenities" },
  { to: ROUTES.offers, key: "nav.offers" },
  { to: ROUTES.contact, key: "nav.contact" },
];

function RoomsMegaMenu() {
  const { t, isKh } = useI18n();
  const navigate = useNavigate();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const closeTimer = useRef(null);

  const { data: roomTypes = [] } = useQuery({
    queryKey: ["navbar-room-types"],
    queryFn: async () => {
      const { items } = await fetchRoomTypes({ status: "active", per_page: 24 });
      return items;
    },
    staleTime: 5 * 60_000,
  });

  useEffect(() => {
    // sync mega-menu closed state when navigating between pages
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setOpen(false);
  }, [location.pathname]);

  const openMenu = () => {
    clearTimeout(closeTimer.current);
    setOpen(true);
  };

  const closeMenu = () => {
    closeTimer.current = setTimeout(() => setOpen(false), 120);
  };

  return (
    <div
      className="relative"
      onMouseEnter={openMenu}
      onMouseLeave={closeMenu}
    >
      <button
        type="button"
        onClick={() => navigate(ROUTES.rooms)}
        onFocus={openMenu}
        onBlur={closeMenu}
        aria-expanded={open}
        className={cn(
          "inline-flex items-center gap-1 rounded-full px-3.5 py-2 text-sm font-medium text-foreground/80 transition-colors hover:bg-brand-50 hover:text-brand-900",
          open && "bg-brand-50 text-brand-900"
        )}
      >
        {t("nav.rooms")}
        <ChevronDown
          className={cn("size-4 transition-transform", open && "rotate-180")}
        />
      </button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 6 }}
            transition={{ duration: 0.16 }}
            className="absolute left-1/2 top-full z-50 w-[min(720px,90vw)] -translate-x-1/2 pt-3"
          >
            <div className="overflow-hidden rounded-2xl border border-brand-100 bg-white p-3 shadow-xl shadow-brand-950/10 ring-1 ring-black/5">
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                {roomTypes.slice(0, 6).map((roomType) => (
                  <Link
                    key={roomType.id}
                    to={ROUTES.roomDetails(roomTypeToSlug(roomType))}
                    onClick={() => setOpen(false)}
                    className="group flex flex-col overflow-hidden rounded-xl border border-transparent transition-colors hover:border-brand-200"
                  >
                    <RoomImage
                      src={roomType.image_url}
                      alt={roomType.name}
                      className="aspect-[4/3] w-full"
                      iconClassName="size-7"
                    />
                    <div className="px-3 py-2.5">
                      <p className="truncate text-sm font-semibold text-foreground group-hover:text-brand-900">
                        {isKh ? roomType.name_kh ?? roomType.name : roomType.name}
                      </p>
                      <p className="mt-0.5 text-xs text-muted-foreground">
                        {t("rooms.from")} {formatCurrency(roomType.base_price)}
                        {t("rooms.perNight")}
                      </p>
                    </div>
                  </Link>
                ))}
              </div>
              <div className="mt-2 flex items-center justify-between border-t border-brand-100 px-2.5 pt-3">
                <p className="text-xs text-muted-foreground">
                  {roomTypes.length > 0
                    ? t("nav.roomTypesAvailable", { count: roomTypes.length })
                    : ""}
                </p>
                <Link
                  to={ROUTES.rooms}
                  onClick={() => setOpen(false)}
                  className="inline-flex items-center gap-1 text-sm font-semibold text-brand-900 transition-colors hover:text-brand-700"
                >
                  {t("nav.viewAllRooms")}
                  <ChevronDown className="size-4 -rotate-90" />
                </Link>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

function UserMenu() {
  const { user } = useAuth();
  const { t } = useI18n();
  return (
    <Link
      to={ROUTES.profile}
      aria-label={user?.name ?? t("nav.profile")}
      className="grid size-9 place-items-center rounded-full bg-brand-900 text-sm font-semibold text-white transition-colors hover:bg-brand-800"
    >
      {avatarFallback(user?.name ?? t("nav.guest"))}
    </Link>
  );
}

export function Navbar({ onOpenMenu }) {
  const { t } = useI18n();
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12);
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <header
      className={cn(
        "sticky top-0 z-40 border-b bg-white/95 backdrop-blur transition-shadow",
        scrolled ? "shadow-sm shadow-brand-950/5" : "border-transparent"
      )}
    >
      <div className="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
        <Link to={ROUTES.home} aria-label={t("nav.homeAria", { hotel: HOTEL_NAME })}>
          <Logo />
        </Link>

        <nav className="hidden items-center gap-0.5 lg:flex" aria-label={t("nav.main")}>
          {NAV_LINKS.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) =>
                cn(
                  "rounded-full px-3.5 py-2 text-sm font-medium text-foreground/80 transition-colors hover:bg-brand-50 hover:text-brand-900",
                  isActive && "bg-brand-50 text-brand-900"
                )
              }
            >
              {t(link.key)}
            </NavLink>
          ))}

          <RoomsMegaMenu />
        </nav>

        <div className="flex items-center gap-2.5">
          <button
            type="button"
            onClick={() => navigate(ROUTES.rooms)}
            aria-label={t("nav.search")}
            className="hidden size-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-brand-50 hover:text-brand-900 sm:inline-flex"
          >
            <Search className="size-4.5" />
          </button>

          <div className="hidden sm:block">
            <LanguageToggle />
          </div>

          {isAuthenticated ? (
            <div className="hidden sm:block">
              <UserMenu />
            </div>
          ) : (
            <Link
              to={ROUTES.login}
              className="hidden md:inline-flex"
              aria-label={t("nav.login")}
            >
              <Button variant="default" size="pill-sm" className="shrink-0">
                {t("nav.login")}
              </Button>
            </Link>
          )}

          <button
            type="button"
            onClick={onOpenMenu}
            aria-label={t("nav.openMenu")}
            className="grid size-9 place-items-center rounded-full text-foreground transition-colors hover:bg-brand-50 lg:hidden"
          >
            <Menu className="size-5" />
          </button>
        </div>
      </div>
    </header>
  );
}

export { NAV_LINKS };