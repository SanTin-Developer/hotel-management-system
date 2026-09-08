import { Link } from "react-router-dom";
import { Users, Ruler, ArrowUpRight } from "lucide-react";
import { RoomImage } from "@/components/common/RoomImage";
import { useI18n } from "@/i18n";
import { formatCurrency } from "@/utils/formatCurrency";
import { roomTypeToSlug, formatBedType } from "@/utils/rooms";
import { ROUTES } from "@/constants/routes";
import { cn } from "@/lib/utils";

export function RoomTypeCard({ roomType, className, showCapacity = true }) {
  const { t } = useI18n();

  return (
    <Link
      to={ROUTES.roomDetails(roomTypeToSlug(roomType))}
      className={cn(
        "group flex flex-col overflow-hidden rounded-2xl border border-border bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-950/10",
        className
      )}
    >
      <div className="relative overflow-hidden">
        <RoomImage
          src={roomType.image_url}
          alt={roomType.name}
          className="aspect-[4/3] w-full transition-transform duration-500 group-hover:scale-105"
        />
        <div className="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-brand-900 backdrop-blur">
          {formatBedType(roomType.bed_type)}
        </div>
        <span className="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 text-brand-900 opacity-0 backdrop-blur transition-opacity group-hover:opacity-100">
          <ArrowUpRight className="size-4" />
        </span>
      </div>

      <div className="flex flex-1 flex-col p-5">
        <div className="flex items-start justify-between gap-3">
          <h3 className="text-lg font-semibold text-foreground transition-colors group-hover:text-brand-900">
            {roomType.name}
          </h3>
        </div>

        <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted-foreground">
          {showCapacity && (
            <span className="inline-flex items-center gap-1">
              <Users className="size-3.5" />
              {roomType.capacity} {t("rooms.capacity")}
            </span>
          )}
          {roomType.size ? (
            <span className="inline-flex items-center gap-1">
              <Ruler className="size-3.5" />
              {roomType.size} {t("rooms.size")}
            </span>
          ) : null}
        </div>

        <div className="mt-auto flex items-end justify-between pt-5">
          <p className="text-sm">
            <span className="text-lg font-semibold text-brand-900">
              {t("rooms.from")} {formatCurrency(roomType.base_price)}
            </span>
            <span className="text-muted-foreground">{t("rooms.perNight")}</span>
          </p>
        </div>
      </div>
    </Link>
  );
}