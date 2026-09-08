import { useState } from "react";
import { BedDouble } from "lucide-react";
import { cn } from "@/lib/utils";
import { useI18n } from "@/i18n";

export function RoomImage({ src, alt, className, iconClassName }) {
  const { t } = useI18n();
  const [broken, setBroken] = useState(false);
  const showFallback = !src || broken;

  if (showFallback) {
    return (
      <div
        className={cn(
          "flex items-center justify-center bg-gradient-to-br from-brand-700 to-brand-950",
          className
        )}
        role="img"
        aria-label={alt ?? t("booking.roomNumber")}
      >
        <BedDouble className={cn("size-12 text-white/50", iconClassName)} />
      </div>
    );
  }

  return (
    <img
      src={src}
      alt={alt ?? t("booking.roomNumber")}
      loading="lazy"
      onError={() => setBroken(true)}
      className={cn("object-cover", className)}
    />
  );
}