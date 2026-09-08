import { Link } from "react-router-dom";
import {
  Clock,
  CreditCard,
  CalendarX2,
  Users,
  PawPrint,
  ShieldAlert,
  LockKeyhole,
  Check,
  MessageCircleQuestion,
} from "lucide-react";
import { PageHeader } from "@/components/common/PageHeader";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function PolicyPage() {
  const { t } = useI18n();

  const SECTIONS = [
    {
      icon: Clock,
      titleKey: "policy.arrival.title",
      items: [
        "policy.arrival.1",
        "policy.arrival.2",
        "policy.arrival.3",
        "policy.arrival.4",
      ],
    },
    {
      icon: CreditCard,
      titleKey: "policy.payment.title",
      items: [
        "policy.payment.1",
        "policy.payment.2",
        "policy.payment.3",
        "policy.payment.4",
      ],
    },
    {
      icon: CalendarX2,
      titleKey: "policy.cancel.title",
      items: [
        "policy.cancel.1",
        "policy.cancel.2",
        "policy.cancel.3",
        "policy.cancel.4",
      ],
    },
    {
      icon: Users,
      titleKey: "policy.children.title",
      items: [
        "policy.children.1",
        "policy.children.2",
        "policy.children.3",
      ],
    },
    {
      icon: PawPrint,
      titleKey: "policy.pets.title",
      items: ["policy.pets.1", "policy.pets.2", "policy.pets.3"],
    },
    {
      icon: ShieldAlert,
      titleKey: "policy.security.title",
      items: [
        "policy.security.1",
        "policy.security.2",
        "policy.security.3",
        "policy.security.4",
      ],
    },
    {
      icon: LockKeyhole,
      titleKey: "policy.privacy.title",
      items: [
        "policy.privacy.1",
        "policy.privacy.2",
        "policy.privacy.3",
      ],
    },
  ];

  return (
    <>
      <PageHeader
        eyebrow={t("policy.eyebrow")}
        title={t("policy.title")}
        subtitle={t("policy.subtitle")}
        crumb={t("policy.crumb")}
      />

      <section className="bg-white py-16 sm:py-20">
        <div className="mx-auto max-w-5xl px-6">
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-2">
            {SECTIONS.map(({ icon: Icon, titleKey, items }) => (
              <div
                key={titleKey}
                className="rounded-2xl border border-border bg-cream/50 p-6 sm:p-7"
              >
                <div className="flex items-center gap-3">
                  <span className="grid size-11 shrink-0 place-items-center rounded-full bg-brand-900 text-gold-400">
                    <Icon className="size-5" />
                  </span>
                  <h2 className="text-lg font-semibold tracking-tight">
                    {t(titleKey)}
                  </h2>
                </div>
                <ul className="mt-5 space-y-3">
                  {items.map((key) => (
                    <li key={key} className="flex items-start gap-2.5">
                      <Check className="mt-0.5 size-4 shrink-0 text-brand-700" />
                      <span className="text-sm leading-relaxed text-muted-foreground">
                        {t(key)}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>

          <div className="mt-12 flex flex-col items-center gap-4 rounded-2xl border border-brand-200 bg-brand-50 px-6 py-10 text-center sm:px-10">
            <MessageCircleQuestion className="size-9 text-brand-800" />
            <h2 className="text-xl font-semibold tracking-tight sm:text-2xl">
              {t("policy.noteTitle")}
            </h2>
            <p className="max-w-xl text-sm leading-relaxed text-brand-800">
              {t("policy.noteDesc")}
            </p>
            <Link to={ROUTES.contact} className="mt-1 inline-block">
              <Button variant="dark" size="pill-lg">
                {t("policy.contactUs")}
              </Button>
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}