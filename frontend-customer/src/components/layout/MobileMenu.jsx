import { Link, useLocation } from "react-router-dom";
import { AnimatePresence, motion } from "framer-motion";
import { X, CalendarDays, UserRound, LogOut } from "lucide-react";
import { Logo } from "./Logo";
import { Button } from "@/components/ui/button";
import { LanguageToggle } from "@/components/common/LanguageToggle";
import { useI18n } from "@/i18n";
import { useAuth } from "@/hooks/useAuth";
import { ROUTES } from "@/constants/routes";
import { cn } from "@/lib/utils";

export function MobileMenu({ open, onClose }) {
  const { t } = useI18n();
  const { isAuthenticated, logout } = useAuth();
  const location = useLocation();

  const links = [
    { to: ROUTES.home, key: "nav.home" },
    { to: ROUTES.rooms, key: "nav.rooms" },
    { to: ROUTES.amenities, key: "nav.amenities" },
    { to: ROUTES.offers, key: "nav.offers" },
    { to: ROUTES.about, key: "nav.about" },
    { to: ROUTES.policy, key: "nav.policy" },
    { to: ROUTES.contact, key: "nav.contact" },
  ];

  const isActive = (to) => {
    if (to === ROUTES.home) return location.pathname === ROUTES.home;
    return location.pathname.startsWith(to);
  };

  return (
    <AnimatePresence>
      {open && (
        <>
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="fixed inset-0 z-50 bg-brand-950/40 backdrop-blur-sm"
          />
          <motion.div
            initial={{ x: "100%" }}
            animate={{ x: 0 }}
            exit={{ x: "100%" }}
            transition={{ type: "tween", duration: 0.28 }}
            className="fixed inset-y-0 right-0 z-50 flex w-[min(22rem,90vw)] flex-col bg-white shadow-2xl"
          >
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
              <Logo />
              <div className="flex items-center gap-2">
                <LanguageToggle />
                <button
                  type="button"
                  onClick={onClose}
                  aria-label={t("nav.closeMenu")}
                  className="grid size-9 place-items-center rounded-full text-muted-foreground hover:bg-brand-50"
                >
                  <X className="size-5" />
                </button>
              </div>
            </div>

            <nav className="flex-1 space-y-1 overflow-y-auto px-4 py-5" aria-label={t("nav.mobile")}>
              {links.map((link) => (
                <Link
                  key={link.to}
                  to={link.to}
                  onClick={onClose}
                  className={cn(
                    "flex items-center justify-between rounded-xl px-4 py-3 text-base font-medium text-foreground/80 transition-colors hover:bg-brand-50 hover:text-brand-900",
                    isActive(link.to) && "bg-brand-50 text-brand-900"
                  )}
                >
                  {t(link.key)}
                </Link>
              ))}
            </nav>

            <div className="space-y-3 border-t border-border px-5 py-5">
              {isAuthenticated ? (
                <>
                  <Link to={ROUTES.profileBookings} onClick={onClose}>
                    <Button
                      variant="secondary"
                      size="pill-lg"
                      className="w-full"
                    >
                      <CalendarDays />
                      {t("nav.myBookings")}
                    </Button>
                  </Link>
                  <Link to={ROUTES.profile} onClick={onClose}>
                    <Button
                      variant="secondary"
                      size="pill-lg"
                      className="w-full"
                    >
                      <UserRound />
                      {t("nav.myProfile")}
                    </Button>
                  </Link>
                  <Button
                    variant="outline"
                    size="pill-lg"
                    className="w-full"
                    onClick={() => {
                      onClose();
                      logout();
                    }}
                  >
                    <LogOut />
                    {t("nav.logout")}
                  </Button>
                </>
              ) : (
                <Link to={ROUTES.login} onClick={onClose}>
                  <Button size="pill-lg" className="w-full">
                    {t("nav.login")}
                  </Button>
                </Link>
              )}
            </div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}