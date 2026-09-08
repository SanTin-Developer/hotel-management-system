import { AlertTriangle } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useI18n } from "@/i18n";

export function ErrorMessage({ title, message, onRetry, className, compact }) {
  const { t } = useI18n();
  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center rounded-2xl border border-red-100 bg-red-50/60 px-6 py-12 text-center",
        compact && "py-8",
        className
      )}
    >
      <div className="grid size-12 place-items-center rounded-full bg-red-100 text-red-600">
        <AlertTriangle className="size-6" />
      </div>
      <h3 className="mt-4 text-base font-semibold text-red-800">
        {title ?? t("common.somethingWentWrong")}
      </h3>
      {message && (
        <p className="mt-2 max-w-md text-sm text-red-700/80">{message}</p>
      )}
      {onRetry && (
        <Button variant="outline" size="pill-sm" className="mt-6" onClick={onRetry}>
          {t("common.tryAgain")}
        </Button>
      )}
    </div>
  );
}