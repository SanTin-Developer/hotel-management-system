import { cn } from "@/lib/utils";
import { HOTEL_NAME, HOTEL_LOGO_URL } from "@/constants/routes";
import { useI18n } from "@/i18n";

export function Logo({ className, textClass, iconClass }) {
  const { t } = useI18n();
  return (
    <span className={cn("inline-flex items-center gap-2.5", className)}>
      <span
        className={cn(
          "grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl border border-brand-900/10 bg-white",
          iconClass
        )}
      >
        <img
          src={HOTEL_LOGO_URL}
          alt={t("logo.alt", { hotel: HOTEL_NAME })}
          className="h-full w-full object-contain"
        />
      </span>
      <span className={cn("flex flex-col leading-none", textClass)}>
        <span className="text-base font-semibold tracking-tight">
          {HOTEL_NAME}
        </span>
        <span className="mt-1 text-[0.62rem] font-medium uppercase tracking-[0.22em] text-gold-600">
          {t("logo.tagline")}
        </span>
      </span>
    </span>
  );
}