import { Link } from "react-router-dom";
import { ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";
import { useI18n } from "@/i18n";

export function PageHeader({ eyebrow, title, subtitle, crumb }) {
  const { t } = useI18n();

  return (
    <section className="relative overflow-hidden bg-brand-950 text-white">
      <div
        className="pointer-events-none absolute inset-0 opacity-[0.12]"
        style={{
          backgroundImage:
            "radial-gradient(circle at 20% 10%, #C8A24B 0, transparent 32%), radial-gradient(circle at 85% 30%, #0E5838 0, transparent 40%)",
        }}
      />
      <div className="relative mx-auto max-w-7xl px-6 py-16 sm:py-20">
        {crumb && (
          <nav
            className="mb-4 flex items-center gap-1.5 text-xs text-brand-300"
            aria-label="Breadcrumb"
          >
            <Link to="/" className="transition-colors hover:text-white">
              {t("common.home")}
            </Link>
            <ChevronRight className="size-3.5" />
            <span className="text-brand-100">{crumb}</span>
          </nav>
        )}
        {eyebrow && (
          <p className="text-xs font-semibold uppercase tracking-[0.22em] text-gold-400">
            {eyebrow}
          </p>
        )}
        <h1
          className={cn(
            "mt-3 text-3xl font-semibold tracking-tight sm:text-4xl md:text-5xl",
            eyebrow && "mt-2",
          )}
        >
          {title}
        </h1>
        {subtitle && (
          <p className="mt-4 max-w-2xl text-base leading-relaxed text-brand-200">
            {subtitle}
          </p>
        )}
      </div>
    </section>
  );
}
