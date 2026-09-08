import { HeroSection } from "@/components/home/HeroSection";
import { SearchWidget } from "@/components/booking/SearchWidget";
import { WelcomeSection } from "@/components/home/WelcomeSection";
import { RoomTypesSection } from "@/components/home/RoomTypesSection";
import { WhyBookSection } from "@/components/home/WhyBookSection";
import { AmenitiesSection } from "@/components/home/AmenitiesSection";
import { OffersSection } from "@/components/home/OffersSection";
import { ReviewsSection } from "@/components/home/ReviewsSection";
import { ContactSection } from "@/components/home/ContactSection";

export function HomePage() {
  return (
    <>
      <HeroSection />

      <div className="relative z-10 mx-auto -mt-24 max-w-5xl px-4 sm:px-6 lg:-mt-20">
        <SearchWidget variant="floating" />
        <div className="h-16" />
      </div>

      <WelcomeSection />
      <RoomTypesSection />
      <WhyBookSection />
      <AmenitiesSection />
      <OffersSection />
      <ReviewsSection />
      <ContactSection />
    </>
  );
}