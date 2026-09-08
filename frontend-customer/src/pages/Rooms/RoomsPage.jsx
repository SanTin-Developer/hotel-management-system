import { useQuery } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { PageHeader } from "@/components/common/PageHeader";
import { RoomTypeCard } from "@/components/rooms/RoomTypeCard";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { EmptyState } from "@/components/common/EmptyState";
import { Input } from "@/components/ui/input";
import { Search, BedDouble } from "lucide-react";
import { useI18n } from "@/i18n";
import { fetchRoomTypes } from "@/services/api/rooms";
import { useDebounce } from "@/hooks/useDebounce";
import { useBookingStore } from "@/store/bookingStore";

export function RoomsPage() {
  const { t } = useI18n();
  const [searchParams] = useSearchParams();
  const setDates = useBookingStore((state) => state.setDates);
  const setOccupancy = useBookingStore((state) => state.setOccupancy);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounce(search, 300);

  useEffect(() => {
    const checkin = searchParams.get("checkin");
    const checkout = searchParams.get("checkout");
    const rawGuests = searchParams.get("guests");
    if (checkin && checkout) setDates(checkin, checkout);
    if (rawGuests) {
      const guests = Number(rawGuests);
      if (Number.isFinite(guests) && guests >= 1) {
        setOccupancy(Math.min(guests, 20), 0);
      }
    }
  }, [searchParams, setDates, setOccupancy]);

  const {
    data: items = [],
    isPending,
    isError,
    refetch,
  } = useQuery({
    queryKey: ["rooms-page", debouncedSearch],
    queryFn: async () => {
      const { items } = await fetchRoomTypes({
        status: "active",
        per_page: 100,
        search: debouncedSearch || undefined,
      });
      return items;
    },
    staleTime: 5 * 60_000,
  });

  return (
    <>
      <PageHeader
        eyebrow={t("rooms.eyebrow")}
        title={t("rooms.title")}
        subtitle={t("rooms.subtitle")}
        crumb={t("nav.rooms")}
      />

      <section className="bg-white py-14 sm:py-16">
        <div className="mx-auto max-w-7xl px-6">
          <div className="mb-10 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="relative w-full sm:max-w-sm">
              <Search className="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={t("rooms.search")}
                className="h-11 rounded-full pl-10"
                aria-label={t("rooms.searchAria")}
              />
            </div>
            <p className="text-sm text-muted-foreground">
              {items.length === 1
                ? t("rooms.type", { count: items.length })
                : t("rooms.types", { count: items.length })}
            </p>
          </div>

          {isPending ? (
            <Loading label={t("common.loading")} />
          ) : isError ? (
            <ErrorMessage message={t("rooms.loadError")} onRetry={refetch} />
          ) : items.length === 0 ? (
            <EmptyState
              icon={<BedDouble className="size-7" />}
              title={t("common.noResults")}
              description={t("rooms.noMatches")}
            />
          ) : (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {items.map((roomType) => (
                <RoomTypeCard key={roomType.id} roomType={roomType} />
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="bg-brand-950 py-16 text-white">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-6 text-center sm:flex-row sm:text-left">
          <div>
            <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
              {t("rooms.waiting.title")}
            </h2>
            <p className="mt-2 max-w-xl text-brand-200">
              {t("rooms.waiting.desc")}
            </p>
          </div>
        </div>
      </section>
    </>
  );
}
