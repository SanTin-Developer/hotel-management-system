import { Check, HeartHandshake, Sparkles, ShieldCheck } from "lucide-react";
import { PageHeader } from "@/components/common/PageHeader";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { useI18n } from "@/i18n";
import { IMAGES } from "@/lib/images";
import { ROUTES, HOTEL_NAME } from "@/constants/routes";

export function AboutPage() {
  const { t } = useI18n();

  const NUMBERS = [
    { value: "2007", label: t("about.numDoors") },
    { value: "68", label: t("about.numRooms") },
    { value: "45+", label: t("about.numTeam") },
    { value: "20k+", label: t("about.numNights") },
  ];

  const VALUES = [
    {
      icon: HeartHandshake,
      title: t("about.valueHospitality"),
      desc: t("about.valueHospitality.desc"),
    },
    {
      icon: Sparkles,
      title: t("about.valueDetail"),
      desc: t("about.valueDetail.desc"),
    },
    {
      icon: ShieldCheck,
      title: t("about.valueFair"),
      desc: t("about.valueFair.desc"),
    },
  ];
  return (
    <>
      <PageHeader
        eyebrow={t("about.eyebrow")}
        title={t("about.title")}
        subtitle={t("about.subtitle")}
        crumb={t("about.crumb")}
      />

      {/* Story */}
      <section className="bg-white py-16 sm:py-20">
        <div className="mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2">
          <div>
            <h2 className="text-3xl font-semibold tracking-tight">
              {t("about.storyTitle")}
            </h2>
            <div className="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground">
              <p>{HOTEL_NAME}</p>
              <p>{t("about.paragraph2")}</p>
              <p>{t("about.paragraph3")}</p>
            </div>
            <Link to={ROUTES.contact} className="mt-8 inline-block">
              <Button variant="dark" size="pill">
                {t("about.sayHello")}
              </Button>
            </Link>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <img
              src={IMAGES.aboutMain}
              alt={`${HOTEL_NAME} exterior`}
              className="aspect-[3/4] w-full rounded-2xl object-cover shadow-lg"
            />
            <img
              src={IMAGES.aboutInterior}
              alt="Hotel interior"
              className="mt-8 aspect-[3/4] w-full rounded-2xl object-cover shadow-lg"
            />
          </div>
        </div>
      </section>

      {/* Numbers */}
      <section className="bg-brand-950 py-16 text-white">
        <div className="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-6 sm:grid-cols-4">
          {NUMBERS.map((item) => (
            <div key={item.label} className="text-center">
              <p className="text-4xl font-semibold tracking-tight text-gold-400">
                {item.value}
              </p>
              <p className="mt-2 text-sm text-brand-200">{item.label}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Values */}
      <section className="bg-cream py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-6">
          <h2 className="text-center text-3xl font-semibold tracking-tight">
            {t("about.valuesTitle")}
          </h2>
          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {VALUES.map((value) => {
              const Icon = value.icon;
              return (
                <div
                  key={value.title}
                  className="rounded-2xl border border-border bg-white p-8 text-center"
                >
                  <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-900 text-gold-400">
                    <Icon className="size-7" />
                  </span>
                  <h3 className="mt-5 text-lg font-semibold">{value.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                    {value.desc}
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Good to know */}
      <section className="bg-white py-16">
        <div className="mx-auto max-w-4xl px-6">
          <div className="rounded-3xl border border-brand-200 bg-brand-50 p-8 sm:p-10">
            <h2 className="text-xl font-semibold">{t("about.goodTitle")}</h2>
            <ul className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
              {[
                t("about.good1"),
                t("about.good2"),
                t("about.good3"),
                t("about.good4"),
                t("about.good5"),
                t("about.good6"),
              ].map((item) => (
                <li key={item} className="flex items-start gap-2.5">
                  <span className="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-brand-900 text-white">
                    <Check className="size-3" />
                  </span>
                  {item}
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>
    </>
  );
}
