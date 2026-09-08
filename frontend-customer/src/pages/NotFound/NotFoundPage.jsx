import { Link } from "react-router-dom";
import { Home, Compass } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function NotFoundPage() {
  const { t } = useI18n();
  return (
    <section className="min-h-[70vh] bg-cream py-24">
      <div className="mx-auto max-w-xl px-6 text-center">
        <p className="text-[7rem] font-bold leading-none tracking-tight text-brand-900">
          404
        </p>
        <h1 className="mt-4 text-2xl font-semibold tracking-tight">
          {t("notFound.title")}
        </h1>
        <p className="mx-auto mt-3 max-w-sm text-pretty text-muted-foreground">
          {t("notFound.body")}
        </p>
        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Link to={ROUTES.home}>
            <Button size="pill" variant="dark">
              <Home /> {t("notFound.backHome")}
            </Button>
          </Link>
          <Link to={ROUTES.rooms}>
            <Button size="pill">
              <Compass /> {t("notFound.browseRooms")}
            </Button>
          </Link>
        </div>
      </div>
    </section>
  );
}