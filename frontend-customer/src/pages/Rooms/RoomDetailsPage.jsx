import { useQuery } from "@tanstack/react-query";
import { Link, useNavigate, useParams } from "react-router-dom";
import { useState } from "react";
import {
  ChevronRight,
  Users,
  Ruler,
  BedDouble,
  Check,
  CalendarDays,
  ArrowRight,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { useI18n } from "@/i18n";
import { fetchRoomType, fetchRooms } from "@/services/api/rooms";
import { slugToRoomTypeId } from "@/utils/rooms";
import { formatBedType } from "@/utils/rooms";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange, diffInDays } from "@/utils/formatDate";
import { useBookingStore } from "@/store/bookingStore";
import { ROUTES } from "@/constants/routes";

export function RoomDetailsPage() {
  const { t, isKh } = useI18n();
  const { slug } = useParams();
  const navigate = useNavigate();
  const roomTypeId = slugToRoomTypeId(slug);
  const { checkIn, checkOut } = useBookingStore();

  const [activeImage, setActiveImage] = useState(0);

  const {
    data: roomType,
    isPending,
    isError,
    refetch,
  } = useQuery({
    queryKey: ["room-type", roomTypeId],
    queryFn: () => fetchRoomType(roomTypeId),
    enabled: Boolean(roomTypeId),
  });

  const { data: rooms = [] } = useQuery({
    queryKey: ["room-type-rooms", roomTypeId],
    queryFn: async () => {
      const { items } = await fetchRooms({
        room_type_id: roomTypeId,
        per_page: 20,
      });
      return items;
    },
    enabled: Boolean(roomTypeId),
  });

  if (!roomTypeId) {
    return <RoomNotFound />;
  }

  if (isPending) {
    return <Loading label={t("common.loading")} className="min-h-96" />;
  }

  if (isError || !roomType) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-20">
        <ErrorMessage
          title={t("roomDetails.notFoundTitle")}
          message={t("roomDetails.notFoundMsg")}
          onRetry={refetch}
        />
      </div>
    );
  }

  const images = Array.from(
    new Set(
      [
        roomType.image_url,
        ...rooms.flatMap((room) => [
          room.image_url,
          ...(room.images ?? []).map((image) => image.image_url),
        ]),
      ].filter(Boolean),
    ),
  );

  const amenities = Array.from(
    new Map(
      rooms
        .flatMap((room) => room.amenities ?? [])
        .map((amenity) => [amenity.id, amenity]),
    ).values(),
  );

  const nights = checkIn && checkOut ? diffInDays(checkIn, checkOut) : null;
  const estimatedTotal =
    nights && nights > 0 ? roomType.base_price * nights : roomType.base_price;

  const goToBooking = () => {
    navigate(`${ROUTES.booking}?roomType=${roomType.id}`);
  };

  return (
    <>
      <div className="border-b border-border bg-cream">
        <div className="mx-auto flex max-w-7xl items-center gap-1.5 px-6 py-4 text-xs text-muted-foreground">
          <Link to={ROUTES.home} className="hover:text-brand-900">
            {t("nav.home")}
          </Link>
          <ChevronRight className="size-3.5" />
          <Link to={ROUTES.rooms} className="hover:text-brand-900">
            {t("nav.rooms")}
          </Link>
          <ChevronRight className="size-3.5" />
          <span className="font-medium text-foreground">
            {isKh ? roomType.name_kh ?? roomType.name : roomType.name}
          </span>
        </div>
      </div>

      <section className="bg-white py-10 sm:py-14">
        <div className="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[1.6fr_1fr]">
          {/* Gallery */}
          <div>
            <div className="overflow-hidden rounded-2xl bg-brand-100">
              {images.length > 0 ? (
                <img
                  key={images[activeImage]}
                  src={images[activeImage]}
                  alt={roomType.name}
                  className="aspect-[16/10] w-full object-cover"
                />
              ) : (
                <div className="flex aspect-[16/10] items-center justify-center bg-gradient-to-br from-brand-700 to-brand-950">
                  <BedDouble className="size-16 text-white/60" />
                </div>
              )}
            </div>
            {images.length > 1 && (
              <div className="mt-4 grid grid-cols-4 gap-3">
                {images.map((image, index) => (
                  <button
                    key={image}
                    type="button"
                    onClick={() => setActiveImage(index)}
                    className={`overflow-hidden rounded-xl transition-all ${
                      activeImage === index
                        ? "ring-2 ring-brand-900 ring-offset-2"
                        : "opacity-70 hover:opacity-100"
                    }`}
                  >
                    <img
                      src={image}
                      alt={`${roomType.name} view ${index + 1}`}
                      className="aspect-video w-full object-cover"
                    />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Summary */}
          <aside className="lg:sticky lg:top-24 lg:self-start">
            <div className="rounded-2xl border border-brand-100 bg-white shadow-lg shadow-brand-950/5">
              <div className="border-b border-border p-6">
                <p className="text-xs font-semibold uppercase tracking-wider text-brand-700">
                  {t("rooms.viewAll")}
                </p>
                <h1 className="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                  {isKh ? roomType.name_kh ?? roomType.name : roomType.name}
                </h1>
                <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                  <span className="inline-flex items-center gap-1.5">
                    <Users className="size-4 text-brand-700" />
                    {roomType.capacity} {t("rooms.capacity")}
                  </span>
                  <span className="inline-flex items-center gap-1.5">
                    <Ruler className="size-4 text-brand-700" />
                    {roomType.size} {t("rooms.size")}
                  </span>
                  <span className="inline-flex items-center gap-1.5">
                    <BedDouble className="size-4 text-brand-700" />
                    {formatBedType(roomType.bed_type)}
                  </span>
                </div>
              </div>

              <div className="space-y-4 p-6">
                {checkIn && checkOut && (
                  <div className="flex items-center gap-3 rounded-xl bg-brand-50 p-4">
                    <CalendarDays className="size-5 text-brand-800" />
                    <div className="text-sm">
                      <p className="font-semibold text-brand-900">
                        {formatDateRange(checkIn, checkOut)}
                      </p>
                      <p className="text-xs text-brand-700">
                        {nights} {t(nights === 1 ? "booking.night" : "booking.nights")}
                      </p>
                    </div>
                  </div>
                )}

                <div className="flex items-end justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">
                      {t("rooms.from")}
                    </p>
                    <p className="text-3xl font-semibold tracking-tight text-brand-900">
                      {formatCurrency(roomType.base_price)}
                      <span className="text-sm font-normal text-muted-foreground">
                        {t("rooms.perNight")}
                      </span>
                    </p>
                  </div>
                  <p className="text-sm text-muted-foreground">
                    {t("roomDetails.estTotal")}{" "}
                    <span className="font-semibold text-foreground">
                      {formatCurrency(estimatedTotal)}
                    </span>
                  </p>
                </div>

                <Button size="pill-lg" className="w-full" onClick={goToBooking}>
                  {t("common.bookNow")}
                  <ArrowRight />
                </Button>
                <p className="text-center text-xs text-muted-foreground">
                  {t("roomDetails.freeCancel")}
                </p>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <section className="bg-white pb-20">
        <div className="mx-auto grid max-w-7xl gap-12 px-6 lg:grid-cols-2">
          <div>
            <h2
              className={`${isKh ? "text-2xl" : "text-xl"} font-semibold tracking-tight`}
            >
              {t("roomDetails.about")}
            </h2>
            <p className="mt-4 text-base leading-relaxed text-muted-foreground">
              {isKh
                ? roomType.description_kh ?? roomType.description
                : roomType.description}
            </p>
          </div>

          <div>
            <h2 className="text-xl font-semibold tracking-tight">
              {t("roomDetails.amenities")}
            </h2>
            {amenities.length > 0 ? (
              <ul className="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                {amenities.map((amenity) => (
                  <li
                    key={amenity.id}
                    className="flex items-center gap-2.5 rounded-xl border border-border px-4 py-3 text-sm"
                  >
                    <span className="grid size-6 shrink-0 place-items-center rounded-full bg-brand-100 text-brand-800">
                      <Check className="size-3.5" />
                    </span>
                    {isKh ? amenity.name_kh ?? amenity.name : amenity.name}
                  </li>
                ))}
              </ul>
            ) : (
              <p className="mt-4 text-sm text-muted-foreground">
                {t("roomDetails.amenitiesPending")}
              </p>
            )}
          </div>
        </div>
      </section>
    </>
  );
}

function RoomNotFound() {
  const { t } = useI18n();
  return (
    <div className="mx-auto max-w-3xl px-6 py-20">
      <ErrorMessage
        title={t("roomDetails.notFoundTitle")}
        message={t("roomDetails.notFoundAltMsg")}
      />
      <div className="mt-8 text-center">
        <Link to={ROUTES.rooms}>
          <Button variant="dark" size="pill">
            {t("nav.viewAllRooms")}
          </Button>
        </Link>
      </div>
    </div>
  );
}
