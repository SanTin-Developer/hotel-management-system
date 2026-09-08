import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import { SectionHeading } from "@/components/common/SectionHeading";
import { RoomTypeCard } from "@/components/rooms/RoomTypeCard";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { fetchRoomTypes } from "@/services/api/rooms";
import { ROUTES } from "@/constants/routes";

export function RoomTypesSection({ limit = 8, showButton = true }) {
  const { t } = useI18n();

  const { data: items = [], isPending, isError, refetch } = useQuery({
    queryKey: ["home-room-types"],
    queryFn: async () => {
      const { items } = await fetchRoomTypes({ status: "active", per_page: 100 });
      return items;
    },
    staleTime: 5 * 60_000,
  });

  const roomTypes = items.slice(0, limit);

  return (
    <section className="bg-white py-20 sm:py-24">
      <div className="mx-auto max-w-7xl px-6">
        <SectionHeading
          eyebrow={t("rooms.eyebrow")}
          title={t("rooms.title")}
          subtitle={t("rooms.subtitle")}
        />

        {isPending ? (
          <Loading label={t("common.loading")} />
        ) : isError ? (
          <ErrorMessage
            message={t("rooms.loadError")}
            onRetry={refetch}
          />
        ) : roomTypes.length === 0 ? (
          <p className="mt-12 text-center text-muted-foreground">{t("common.noResults")}</p>
        ) : (
          <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {roomTypes.map((roomType) => (
              <RoomTypeCard key={roomType.id} roomType={roomType} />
            ))}
          </div>
        )}

        {showButton && roomTypes.length > 0 && (
          <div className="mt-14 flex justify-center">
            <Link to={ROUTES.rooms}>
              <Button variant="dark" size="pill-lg">
                {t("rooms.viewAll")}
                <ArrowRight />
              </Button>
            </Link>
          </div>
        )}
      </div>
    </section>
  );
}