import { useQuery } from "@tanstack/react-query";
import { Check, Clock, Wifi, Utensils, Dumbbell, Waves } from "lucide-react";
import { PageHeader } from "@/components/common/PageHeader";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { fetchAmenities } from "@/services/api/amenities";
import { useI18n } from "@/i18n";

export function AmenitiesPage() {
  const { t, isKh } = useI18n();

  const SERVICES = [
    {
      icon: Wifi,
      title: t("amenities.wifi"),
      desc: t("amenities.wifi.desc"),
    },
    {
      icon: Utensils,
      title: t("amenities.breakfast"),
      desc: t("amenities.breakfast.desc"),
    },
    {
      icon: Waves,
      title: t("amenities.pool"),
      desc: t("amenities.pool.desc"),
    },
    {
      icon: Dumbbell,
      title: t("amenities.fitness"),
      desc: t("amenities.fitness.desc"),
    },
    {
      icon: Clock,
      title: t("amenities.frontdesk"),
      desc: t("amenities.frontdesk.desc"),
    },
    {
      icon: Check,
      title: t("amenities.housekeeping"),
      desc: t("amenities.housekeeping.desc"),
    },
  ];

  const {
    data = null,
    isPending,
    isError,
    refetch,
  } = useQuery({
    queryKey: ["amenities-page"],
    queryFn: async () => {
      const { items } = await fetchAmenities({ per_page: 100, sort: "name" });
      return items;
    },
    staleTime: 5 * 60_000,
  });

  const amenities = Array.isArray(data) ? data : [];

  return (
    <>
      <PageHeader
        eyebrow={t("amenities.eyebrow")}
        title={t("amenities.title")}
        subtitle={t("amenities.subtitle")}
        crumb={t("amenities.crumb")}
      />

      <section className="bg-white py-14 sm:py-16">
        <div className="mx-auto max-w-7xl px-6">
          {isPending ? (
            <Loading label={t("amenities.loading")} />
          ) : isError ? (
            <ErrorMessage message={t("amenities.loadError")} onRetry={refetch} />
          ) : amenities.length === 0 ? (
            <p className="rounded-2xl border border-dashed border-brand-300 bg-brand-50 px-6 py-16 text-center text-muted-foreground">
              {t("amenities.empty")}
            </p>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {amenities.map((amenity) => (
                <div
                  key={amenity.id}
                  className="rounded-2xl border border-border bg-white p-6 transition-all hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md"
                >
                  <span className="grid size-11 place-items-center rounded-xl bg-brand-900 text-gold-400">
                    <Check className="size-5" strokeWidth={2.5} />
                  </span>
                  <h3 className="mt-4 font-semibold">
                    {isKh ? amenity.name_kh ?? amenity.name : amenity.name}
                  </h3>
                  {(amenity.description || amenity.description_kh) && (
                    <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                      {isKh
                        ? amenity.description_kh ?? amenity.description
                        : amenity.description}
                    </p>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="bg-brand-950 py-16 text-white">
        <div className="mx-auto max-w-7xl px-6">
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            {t("amenities.everyStay")}
          </h2>
          <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {SERVICES.map((service) => {
              const Icon = service.icon;
              return (
                <div
                  key={service.title}
                  className="rounded-2xl bg-brand-900/60 p-6 ring-1 ring-white/10"
                >
                  <span className="grid size-11 place-items-center rounded-xl bg-gold-400 text-brand-950">
                    <Icon className="size-5" />
                  </span>
                  <h3 className="mt-4 font-semibold">{service.title}</h3>
                  <p className="mt-1.5 text-sm leading-relaxed text-brand-200">
                    {service.desc}
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </section>
    </>
  );
}
