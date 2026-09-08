import { useQuery, useMutation } from "@tanstack/react-query";
import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import {
  Check,
  ChevronLeft,
  ChevronRight,
  Loader2,
  CalendarDays,
  Users,
  BedDouble,
  Copy,
  Tag,
  ShieldCheck,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Loading } from "@/components/common/Loading";
import { SearchWidget } from "@/components/booking/SearchWidget";
import { GuestInfoForm } from "@/components/booking/GuestInfoForm";
import { LoginForm } from "@/components/auth/LoginForm";
import { RegisterForm } from "@/components/auth/RegisterForm";
import { formatDateRange } from "@/utils/formatDate";
import { formatBedType } from "@/utils/rooms";
import { formatCurrency } from "@/utils/formatCurrency";
import { calculateBookingPricing } from "@/lib/pricing";
import { useBookingStore } from "@/store/bookingStore";
import { useAuthStore } from "@/store/authStore";
import { useI18n } from "@/i18n";
import { fetchAvailableRooms } from "@/services/api/rooms";
import { validateCoupon } from "@/services/api/coupons";
import { createBooking } from "@/services/api/bookings";
import { toast } from "sonner";
import { ROUTES } from "@/constants/routes";

export function BookingPage() {
  const { t } = useI18n();
  const [searchParams] = useSearchParams();
  const preselectedRoomType = searchParams.get("roomType") ?? null;
  const { checkIn, checkOut } = useBookingStore();
  const selectedCount = useBookingStore((state) => state.selectedRooms.length);
  const isAuthenticated = Boolean(useAuthStore((state) => state.token));
  const [step, setStep] = useState(0);
  const STEPS = [
    t("booking.stepDates"),
    t("booking.stepRooms"),
    t("booking.stepGuests"),
    t("booking.stepReview"),
  ];

  useEffect(() => {
    if (!checkIn || !checkOut) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setStep(0);
    } else if (step === 0) {
      setStep(1);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [checkIn, checkOut]);

  const lastStep = STEPS.length - 1;

  return (
    <section className="min-h-[70vh] bg-cream pb-20">
      <div className="border-b border-border bg-white">
        <div className="mx-auto max-w-5xl px-6 py-6">
          <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            {t("booking.title")}
          </h1>
          <ol className="mt-5 flex items-center gap-2 sm:gap-4">
            {STEPS.map((label, index) => {
              const state =
                index < step ? "done" : index === step ? "active" : "todo";
              return (
                <li key={label} className="flex items-center gap-2 sm:gap-4">
                  {state === "done" ? (
                    <button
                      type="button"
                      onClick={() => setStep(index)}
                      className="flex items-center gap-2 text-sm font-medium text-brand-800 hover:underline"
                    >
                      <span className="grid size-6 place-items-center rounded-full bg-brand-900 text-white">
                        <Check className="size-3.5" />
                      </span>
                      <span className="hidden sm:inline">{label}</span>
                    </button>
                  ) : (
                    <span
                      className={`flex items-center gap-2 text-sm ${
                        state === "active"
                          ? "font-semibold text-brand-900"
                          : "font-medium text-muted-foreground"
                      }`}
                    >
                      <span
                        className={`grid size-6 place-items-center rounded-full border-2 text-xs font-semibold ${
                          state === "active"
                            ? "border-brand-900 bg-brand-900 text-white"
                            : "border-border bg-white"
                        }`}
                      >
                        {index + 1}
                      </span>
                      <span className="hidden sm:inline">{label}</span>
                    </span>
                  )}
                  {index < lastStep && (
                    <span className="h-px w-6 bg-border sm:w-10" />
                  )}
                </li>
              );
            })}
          </ol>
        </div>
      </div>

      <div className="mx-auto max-w-5xl px-6 pt-10">
        {/* Step 0: dates */}
        {step === 0 && (
          <div className="rounded-2xl border border-border bg-white p-6 sm:p-8">
            <h2 className="text-lg font-semibold">{t("booking.datesTitle")}</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              {t("booking.datesDesc")}
            </p>
            <div className="mt-6">
              <SearchWidget variant="default" onSearch={() => setStep(1)} />
            </div>
          </div>
        )}

        {/* Step 1: choose rooms */}
        {step === 1 && <RoomPicker preselectedRoomType={preselectedRoomType} />}

        {/* Step 2: guest details */}
        {step === 2 &&
          (isAuthenticated ? (
            <GuestInfoForm onSaved={() => setStep(3)} />
          ) : (
            <GuestAuthPanel onAuthed={() => setStep(3)} />
          ))}

        {/* Step 3: review */}
        {step === 3 && <ReviewStep onBack={() => setStep(2)} />}

        <div className="mt-8 flex items-center justify-between">
          <Button
            variant="ghost"
            size="pill"
            onClick={() => setStep(step - 1)}
            disabled={step === 0}
          >
            <ChevronLeft />
          </Button>
          {step < lastStep && step !== 2 && (
            <Button
              size="pill"
              onClick={() => setStep(step + 1)}
              disabled={step === 1 && selectedCount === 0}
            >
              {t("booking.continue")} <ChevronRight />
            </Button>
          )}
        </div>
      </div>
    </section>
  );
}

function RoomPicker({ preselectedRoomType }) {
  const { t } = useI18n();
  const { checkIn, checkOut, adults, children, toggleRoom, selectedRooms } =
    useBookingStore();

  const {
    data: rooms = [],
    isLoading,
    isError,
    refetch,
  } = useQuery({
    queryKey: ["available-rooms", checkIn, checkOut],
    queryFn: () => fetchAvailableRooms({ checkIn, checkOut }),
    enabled: Boolean(checkIn && checkOut),
    staleTime: 60_000,
  });

  useEffect(() => {
    if (preselectedRoomType && rooms.length > 0 && selectedRooms.length === 0) {
      const preferred = rooms.filter(
        (room) => String(room.room_type_id) === preselectedRoomType,
      );
      if (preferred.length > 0) {
        preferred.slice(0, 1).forEach((room) => toggleRoom(room));
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [rooms, preselectedRoomType]);

  const grouped = useMemo(() => {
    const map = new Map();
    rooms.forEach((room) => {
      const typeName = room.room_type?.name ?? `Room id ${room.room_type_id}`;
      if (!map.has(typeName)) map.set(typeName, []);
      map.get(typeName).push(room);
    });
    return Array.from(map.entries());
  }, [rooms]);

  if (isLoading)
    return <Loading label={t("common.loading")} className="min-h-72" />;
  if (isError) {
    return (
      <div className="rounded-2xl border border-border bg-white p-8 text-center">
        <p className="text-sm text-muted-foreground">
          {t("common.loading.roomPicker")}
        </p>
        <Button
          className="mt-4"
          size="pill"
          variant="dark"
          onClick={() => refetch()}
        >
          {t("common.roomPicker.btn")}
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-2 rounded-2xl border border-border bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3 text-sm">
          <CalendarDays className="size-4 text-brand-700" />
          <span className="font-semibold">
            {formatDateRange(checkIn, checkOut)}
          </span>
          <span className="text-muted-foreground">
            {
              calculateBookingPricing({
                rooms: selectedRooms,
                checkIn,
                checkOut,
              }).nights
            }{" "}
            {t("booking.nights")}
          </span>
        </div>
        <div className="flex items-center gap-3 text-sm text-muted-foreground">
          <Users className="size-4 text-brand-700" />
          {adults} {t("booking.adults")}
          {children > 0 &&
            t(children === 1 ? "booking.child" : "booking.children", {
              count: children,
            })}
        </div>
      </div>

      <p className="text-sm font-semibold text-muted-foreground">
        {t(
          rooms.length === 1
            ? "booking.roomAvailable"
            : "booking.roomsAvailable",
          {
            count: rooms.length,
          },
        )}
      </p>

      {grouped.map(([typeName, typeRooms]) => (
        <div key={typeName}>
          <h3 className="mb-3 text-lg font-semibold">{typeName}</h3>
          <div className="grid gap-4 md:grid-cols-2">
            {typeRooms.map((room) => {
              const selected = selectedRooms.some((r) => r.id === room.id);
              return (
                <button
                  key={room.id}
                  type="button"
                  onClick={() => toggleRoom(room)}
                  className={`flex gap-4 rounded-2xl border-2 bg-white p-3 text-left transition-all ${
                    selected
                      ? "border-brand-900 shadow-md"
                      : "border-border hover:border-brand-300"
                  }`}
                >
                  <div className="w-28 shrink-0 overflow-hidden rounded-xl bg-brand-100">
                    {room.image_url ? (
                      <img
                        src={room.image_url}
                        alt={room.room_number}
                        className="size-full object-cover"
                      />
                    ) : (
                      <div className="grid size-full place-items-center bg-gradient-to-br from-brand-700 to-brand-950">
                        <BedDouble className="size-8 text-white/60" />
                      </div>
                    )}
                  </div>
                  <div className="flex min-w-0 flex-1 flex-col">
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="font-semibold">
                          {t("booking.roomNumber")} {room.room_number}
                          <span className="ml-2 text-xs font-normal text-muted-foreground">
                            {t("booking.floor")} {room.floor}
                          </span>
                        </p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                          {formatBedType(room.room_type?.bed_type)}{" "}
                          {t("booking.capacity")} {room.room_type?.capacity}
                        </p>
                      </div>
                      <span
                        className={`grid size-6 shrink-0 place-items-center rounded-full border-2 ${
                          selected
                            ? "border-brand-900 bg-brand-900 text-white"
                            : "border-border"
                        }`}
                      >
                        {selected && <Check className="size-3.5" />}
                      </span>
                    </div>
                    <div className="mt-auto pt-2 text-sm">
                      <span className="text-lg font-semibold text-brand-900">
                        {formatCurrency(room.room_type?.base_price)}
                      </span>
                      <span className="text-muted-foreground">
                        {" "}
                        / {t("booking.night")}
                      </span>
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      ))}

      {selectedRooms.length > 0 && (
        <div className="flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4">
          <p className="text-sm font-semibold text-brand-900">
            {t(
              selectedRooms.length === 1
                ? "booking.roomSelected"
                : "booking.roomsSelected",
              {
                count: selectedRooms.length,
              },
            )}
          </p>
          <p className="text-sm font-medium text-brand-800">
            {t("booking.est")}{" "}
            {formatCurrency(
              calculateBookingPricing({
                rooms: selectedRooms,
                checkIn,
                checkOut,
              }).baseTotal,
            )}
          </p>
        </div>
      )}
    </div>
  );
}

function GuestAuthPanel({ onAuthed }) {
  const { t } = useI18n();
  const [mode, setMode] = useState("login");
  const isAuthenticated = Boolean(useAuthStore((state) => state.token));

  useEffect(() => {
    if (isAuthenticated) {
      onAuthed();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

  return (
    <div className="rounded-2xl border-2 border-dashed border-brand-300 bg-white p-6 sm:p-8">
      <div>
        <h2 className="text-lg font-semibold">{t("booking.guestTitle")}</h2>
        <p className="mt-1 text-sm text-muted-foreground">
          {t("booking.guestDesc")}
        </p>
      </div>

      <Tabs defaultValue={mode} onValueChange={setMode} className="mt-6">
        <TabsList>
          <TabsTrigger value="login">{t("booking.signIn")}</TabsTrigger>
          <TabsTrigger value="register">{t("createAccount")}</TabsTrigger>
        </TabsList>
        <TabsContent value="login" className="mt-5">
          <LoginForm compact />
        </TabsContent>
        <TabsContent value="register" className="mt-5">
          <RegisterForm />
        </TabsContent>
      </Tabs>
    </div>
  );
}

function ReviewStep({ onBack }) {
  const { t } = useI18n();
  const navigate = useNavigate();
  const { checkIn, checkOut, adults, children, selectedRooms, guest } =
    useBookingStore();
  const guestProfile = useAuthStore((state) => state.guest);
  const [coupon, setCoupon] = useState(null);
  const [couponCode, setCouponCode] = useState(guest.coupon_code ?? "");
  const [submitting, setSubmitting] = useState(false);

  const pricing = calculateBookingPricing({
    rooms: selectedRooms,
    checkIn,
    checkOut,
    coupon: coupon ?? null,
  });

  const validateMutation = useMutation({
    mutationFn: (code) => validateCoupon(code, pricing.baseTotal),
    onSuccess: (result) => {
      if (result.valid) {
        setCoupon({ ...result.coupon, valid: true });
        useBookingStore.getState().setCoupon(result.coupon.code);
        toast.success(result.message);
      } else {
        setCoupon(null);
        toast.error(result.message);
      }
    },
    onError: () => {
      setCoupon(null);
      toast.error(t("booking.couponValError"));
    },
  });

  const submit = async () => {
    const auth = useAuthStore.getState();
    if (!auth.token || !auth.guest?.id) return;

    setSubmitting(true);
    try {
      const response = await createBooking({
        guest_id: auth.guest.id,
        check_in: checkIn,
        check_out: checkOut,
        adults,
        children,
        room_ids: selectedRooms.map((room) => room.id),
        coupon_id: coupon?.id ?? null,
        special_request: guest.special_request || null,
        booking_source: "website",
      });
      const booking = response?.data?.data ?? response?.data;
      useBookingStore.getState().reset();
      navigate(`${ROUTES.payment}?code=${booking?.booking_code}`, {
        state: { booking },
      });
    } catch (error) {
      toast.error(
        error?.response?.data?.message ??
          error?.message ??
          t("booking.createFailed"),
      );
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
      <div className="space-y-6">
        {/* Rooms */}
        <section className="rounded-2xl border border-border bg-white">
          <div className="border-b border-border p-5">
            <h3 className="font-semibold">{t("booking.yourRooms")}</h3>
          </div>
          <ul className="divide-y divide-border">
            {pricing.items.map((item) => (
              <li key={item.roomId} className="flex items-center gap-4 p-5">
                <div className="w-16 shrink-0 overflow-hidden rounded-lg bg-brand-100">
                  {item.imageUrl ? (
                    <img
                      src={item.imageUrl}
                      alt={item.roomNumber}
                      className="aspect-square w-full object-cover"
                    />
                  ) : (
                    <div className="grid aspect-square place-items-center bg-gradient-to-br from-brand-700 to-brand-950">
                      <BedDouble className="size-5 text-white/60" />
                    </div>
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="font-semibold">
                    {t("booking.roomNumber")} {item.roomNumber}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {item.roomType}
                  </p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    {formatCurrency(item.pricePerNight)} × {item.nights}{" "}
                    {t(item.nights === 1 ? "booking.night" : "booking.nights")}
                  </p>
                </div>
                <p className="font-semibold text-brand-900">
                  {formatCurrency(item.subtotal)}
                </p>
              </li>
            ))}
          </ul>
        </section>

        {/* Guest */}
        <section className="rounded-2xl border border-border bg-white">
          <div className="border-b border-border p-5">
            <h3 className="font-semibold">{t("booking.guestStay")}</h3>
          </div>
          <dl className="grid gap-x-8 gap-y-4 p-5 text-sm sm:grid-cols-2">
            <div>
              <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("booking.name")}
              </dt>
              <dd className="mt-1 font-medium">
                {guestProfile?.full_name ?? guest.full_name}
              </dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("booking.email")}
              </dt>
              <dd className="mt-1 font-medium">{guest.email}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("booking.phone")}
              </dt>
              <dd className="mt-1 font-medium">{guest.phone}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("booking.country")}
              </dt>
              <dd className="mt-1 font-medium">{guest.country}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("booking.checkIn")}
              </dt>
              <dd className="mt-1 font-medium">{checkIn}</dd>
            </div>
            <div>
              <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("booking.checkOut")}
              </dt>
              <dd className="mt-1 font-medium">{checkOut}</dd>
            </div>
            {guest.special_request && (
              <div className="sm:col-span-2">
                <dt className="text-xs uppercase tracking-wider text-muted-foreground">
                  {t("booking.specialRequest")}
                </dt>
                <dd className="mt-1 font-medium">{guest.special_request}</dd>
              </div>
            )}
          </dl>
          <div className="px-5 pb-5">
            <Button variant="link" size="pill" onClick={onBack}>
              <ChevronLeft /> {t("booking.editGuest")}
            </Button>
          </div>
        </section>
      </div>

      {/* Summary */}
      <aside className="lg:sticky lg:top-24 lg:self-start">
        <div className="rounded-2xl border border-border bg-white p-6">
          <h3 className="font-semibold">{t("booking.priceSummary")}</h3>

          <div className="mt-4 space-y-3 text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">
                {t("booking.baseTotal")} ({pricing.nights} {t("booking.night")})
              </span>
              <span className="font-medium">
                {formatCurrency(pricing.baseTotal)}
              </span>
            </div>
            {pricing.discount > 0 && (
              <div className="flex justify-between text-brand-800">
                <span className="flex items-center gap-1.5">
                  <Tag className="size-3.5" /> {t("booking.coupon")}{" "}
                  {coupon?.code}
                </span>
                <span className="font-medium">
                  −{formatCurrency(pricing.discount)}
                </span>
              </div>
            )}
            <div className="flex justify-between border-t border-dashed border-border pt-3 text-base">
              <span className="font-semibold">{t("booking.total")}</span>
              <span className="font-bold text-brand-900">
                {formatCurrency(pricing.total)}
              </span>
            </div>
            <p className="flex items-start gap-1.5 rounded-lg bg-brand-50 p-3 text-xs text-brand-800">
              <ShieldCheck className="mt-0.5 size-4 shrink-0" />

              {t("booking.depositNote", {
                rate: pricing.depositRate,
                deposit: formatCurrency(pricing.depositAmount),
              })}
            </p>
          </div>

          <div className="mt-5 space-y-1.5">
            <Label htmlFor="coupon">{t("booking.promo")}</Label>
            <div className="flex gap-2">
              <Input
                id="coupon"
                className="h-11"
                placeholder="e.g. WELCOME10"
                value={couponCode}
                onChange={(event) => {
                  setCouponCode(event.target.value.toUpperCase());
                  setCoupon(null);
                }}
              />
              <Button
                type="button"
                variant="dark"
                size="pill"
                className="shrink-0"
                onClick={() =>
                  couponCode && validateMutation.mutate(couponCode)
                }
                disabled={!couponCode || validateMutation.isPending}
              >
                {validateMutation.isPending ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : coupon ? (
                  <Check />
                ) : (
                  <Copy />
                )}
                {t("booking.apply")}
              </Button>
            </div>
            {coupon && (
              <p className="flex items-center gap-1.5 text-xs text-brand-700">
                <Tag className="size-3.5" />

                {t("booking.offApplied", {
                  off:
                    coupon.discount_type === "percentage"
                      ? t("booking.offPercent", {
                          value: coupon.discount_value,
                        })
                      : t("booking.offAmount", {
                          value: formatCurrency(coupon.discount_value),
                        }),
                })}
              </p>
            )}
          </div>

          <Button
            size="pill-lg"
            className="mt-6 w-full"
            onClick={submit}
            disabled={submitting}
          >
            {submitting && <Loader2 className="size-4 animate-spin" />}
            {t("booking.confirm")}
          </Button>
          <p className="mt-3 text-center text-xs text-muted-foreground">
            {t("booking.agree")}{" "}
            <Link to={ROUTES.about} className="text-brand-800 underline">
              {t("booking.houseRules")}
            </Link>
            .
          </p>
        </div>
      </aside>
    </div>
  );
}
