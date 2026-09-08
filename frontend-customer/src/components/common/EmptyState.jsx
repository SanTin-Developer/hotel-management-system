import { cn } from "@/lib/utils";
import { useI18n } from "@/i18n";

export function EmptyState({
  icon,
  title,
  description,
  action,
  className,
}) {
  const { t } = useI18n();
  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/40 px-6 py-16 text-center",
        className
      )}
    >
      {icon && (
        <div className="grid size-14 place-items-center rounded-full bg-brand-100 text-brand-800">
          {icon}
        </div>
      )}
      <h3 className="mt-4 text-lg font-semibold">{title ?? t("common.noResults")}</h3>
      {description && (
        <p className="mt-2 max-w-sm text-sm text-muted-foreground">{description}</p>
      )}
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
}