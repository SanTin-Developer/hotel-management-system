import { useI18n } from "@/i18n";
import { cn } from "@/lib/utils";

export function LanguageToggle({ className }) {
  const { lang, setLang } = useI18n();
  const base =
    "flex items-center rounded-full border border-border bg-white p-0.5 text-xs font-semibold";
  const pill = (active) =>
    cn(
      "rounded-full px-2.5 py-1 transition-colors",
      active
        ? "bg-brand-900 text-white"
        : "text-muted-foreground hover:text-foreground"
    );

  return (
    <div className={cn(base, className)} role="group" aria-label="Language">
      <button
        type="button"
        onClick={() => setLang("en")}
        className={pill(lang === "en")}
        aria-pressed={lang === "en"}
      >
        EN
      </button>
      <button
        type="button"
        onClick={() => setLang("kh")}
        className={pill(lang === "kh")}
        aria-pressed={lang === "kh"}
      >
        KH
      </button>
    </div>
  );
}