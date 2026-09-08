import { Link, Outlet } from "react-router-dom";
import { motion } from "framer-motion";
import { ShieldCheck, Sparkles, CalendarCheck, ArrowLeft } from "lucide-react";
import { LanguageToggle } from "@/components/common/LanguageToggle";
import { useI18n } from "@/i18n";
import { HOTEL_NAME, HOTEL_LOGO_URL } from "@/constants/routes";
import { IMAGES } from "@/lib/images";

const PERKS = [
  { icon: CalendarCheck, labelKey: "auth.perkBook" },
  { icon: ShieldCheck, labelKey: "auth.perkSecure" },
  { icon: Sparkles, labelKey: "auth.perkOffers" },
];

export function AuthLayout() {
  const { t } = useI18n();
  return (
    <div className="grid min-h-screen bg-cream lg:grid-cols-[1.1fr_1fr]">
      {/* Brand panel */}
      <div className="relative hidden overflow-hidden bg-brand-950 lg:block">
        <img
          src={IMAGES.aboutMain}
          alt=""
          className="absolute inset-0 h-full w-full object-cover opacity-35"
        />
        <div
          className="absolute inset-0"
          style={{
            backgroundImage:
              "linear-gradient(180deg, rgba(14,26,21,0.6) 0%, rgba(14,26,21,0.28) 45%, rgba(14,26,21,0.82) 100%)",
          }}
        />
        <div
          className="absolute inset-0"
          style={{
            backgroundImage:
              "radial-gradient(circle at 78% 18%, rgba(200,162,75,0.35) 0, transparent 42%)",
          }}
        />

        <div className="relative z-10 flex h-full flex-col justify-between px-14 py-12">
          <Link to="/" className="flex items-center gap-3">
            <span className="grid size-11 shrink-0 place-items-center overflow-hidden rounded-xl border border-gold-400/40 bg-white p-1">
              <img
                src={HOTEL_LOGO_URL}
                alt={`${HOTEL_NAME} logo`}
                className="h-full w-full object-contain"
              />
            </span>
            <span className="text-2xl font-semibold tracking-tight text-cream">
              {HOTEL_NAME}
            </span>
          </Link>

          <div className="max-w-sm text-cream">
            <p className="text-lg leading-relaxed text-cream/90">
              {t("auth.tagline").replace("{hotel}", HOTEL_NAME)}
            </p>
            <ul className="mt-8 space-y-4">
              {PERKS.map(({ icon: Icon, labelKey }) => (
                <li
                  key={labelKey}
                  className="flex items-center gap-3 text-sm text-cream/80"
                >
                  <span className="grid size-8 shrink-0 place-items-center rounded-full border border-gold-400/40 bg-brand-900/60 text-gold-400">
                    <Icon className="size-4" />
                  </span>
                  {t(labelKey)}
                </li>
              ))}
            </ul>
          </div>

          <p className="text-xs text-cream/50">
            {new Date().getFullYear()} © {HOTEL_NAME}. {t("auth.allRights")}{" "}
            {t("auth.developedBy")}
          </p>
        </div>
      </div>

      {/* Form panel */}
      <div className="flex min-h-screen items-center justify-center px-5 py-12 sm:px-8">
        <div className="w-full max-w-md">
          {/* Top controls */}
          <div className="mb-5 flex items-center justify-between gap-3">
            <Link
              to="/"
              className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-800 transition-colors hover:text-brand-950 hover:underline"
            >
              <ArrowLeft className="size-4" />
              {t("auth.backHome")}
            </Link>
            <LanguageToggle />
          </div>

          <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.45, ease: "easeOut" }}
            className="border border-brand-900/10 bg-white shadow-xl shadow-brand-950/5 sm:rounded-3xl"
          >
            <div className="sm:rounded-3xl sm:p-9 p-6">
              <Outlet />
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  );
}
