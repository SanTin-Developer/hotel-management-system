import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { SlideshowBackground } from "@/components/common/SlideshowBackground";
import { IMAGES } from "@/lib/images";
import { ROUTES } from "@/constants/routes";

export function HeroSection() {
  const { t, isKh } = useI18n();

  return (
    <section className="relative overflow-hidden bg-brand-950">
      {/* Rotating background slide image */}
      <SlideshowBackground images={IMAGES.heroSlides} />
      {/* Dark tint for readability */}
      <div className="absolute inset-0 bg-brand-950/80" />
      {/* Green / gold banner */}
      <div
        className="pointer-events-none absolute inset-0 opacity-[0.14]"
        style={{
          backgroundImage:
            "radial-gradient(circle at 15% 20%, #C8A24B 0, transparent 30%), radial-gradient(circle at 90% 80%, #0E5838 0, transparent 45%)",
        }}
      />
      <div className="relative mx-auto grid max-w-7xl gap-10 px-6 py-12 pb-28 md:py-14 lg:grid-cols-[1.25fr_1fr] lg:gap-12">
        {/* Text overlay block */}
        <motion.div
          initial={{ opacity: 0, y: 24 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
          className="flex flex-col justify-center text-white"
        >
          <p className="text-xs font-semibold uppercase tracking-[0.24em] text-gold-400">
            {t("hero.eyebrow")}
          </p>
          <h1
            className={
              isKh
                ? "mt-4 max-w-2xl text-3xl font-semibold leading-tight tracking-tight sm:text-4xl lg:text-5xl"
                : "mt-4 max-w-2xl text-4xl font-semibold leading-[1.06] tracking-tight sm:text-5xl lg:text-6xl"
            }
          >
            {t("hero.title")}
          </h1>
          <p className="mt-5 max-w-xl text-base leading-relaxed text-brand-100/90 sm:text-lg">
            {t("hero.subtitle")}
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link to={ROUTES.booking}>
              <Button variant="gold" size="pill-lg">
                {t("hero.bookNow")}
                <ArrowRight />
              </Button>
            </Link>
            <Link to={ROUTES.rooms}>
              <Button variant="outlineLight" size="pill-lg">
                {t("hero.exploreRooms")}
              </Button>
            </Link>
          </div>
        </motion.div>

        {/* Split-grid photo collage */}
        <motion.div
          initial={{ opacity: 0, scale: 0.98 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.6, delay: 0.1 }}
          className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-[1.15fr_0.85fr] lg:grid-rows-2"
        >
          <div className="col-span-2 row-span-2 lg:col-span-1">
            <img
              src={IMAGES.heroPrimary}
              alt="Riverside resort pool at dusk"
              className="h-full min-h-64 w-full rounded-2xl object-cover"
            />
          </div>
          <img
            src={IMAGES.heroSideA}
            alt="Hotel lobby"
            className="h-40 w-full rounded-2xl object-cover sm:h-48 lg:h-40 lg:max-h-44"
          />
          <img
            src={IMAGES.heroSideB}
            alt="Infinity pool"
            className="h-40 w-full rounded-2xl object-cover sm:h-48 lg:h-40 lg:max-h-44"
          />
        </motion.div>
      </div>
    </section>
  );
}