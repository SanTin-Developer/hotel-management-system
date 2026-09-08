import { Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";

export function Loading({ label, className, size = "default" }) {
  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center gap-3 py-16 text-muted-foreground",
        className
      )}
    >
      <Loader2
        className={cn(
          "animate-spin text-brand-700",
          size === "sm" ? "size-5" : "size-8"
        )}
      />
      {label && <p className="text-sm">{label}</p>}
    </div>
  );
}