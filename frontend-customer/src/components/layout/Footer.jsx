import { Link } from "react-router-dom";
import { Phone, Mail, MapPin } from "lucide-react";
import { Logo } from "./Logo";
import { useI18n } from "@/i18n";
import {
  ROUTES,
  HOTEL_NAME,
  HOTEL_PHONE,
  HOTEL_EMAIL,
  HOTEL_SOCIAL,
} from "@/constants/routes";
import { FaFacebook, FaInstagramSquare, FaTiktok } from "react-icons/fa";

const FOOTER_LINKS = [
  { to: ROUTES.rooms, key: "footer.links.rooms" },
  { to: ROUTES.offers, key: "footer.links.offers" },
  { to: ROUTES.amenities, key: "footer.links.amenities" },
  { to: ROUTES.reviews, key: "footer.links.reviews" },
  { to: ROUTES.about, key: "footer.links.about" },
  { to: ROUTES.contact, key: "footer.links.contact" },
  { to: ROUTES.policy, key: "footer.links.policy" },
];

export function Footer() {
  const { t } = useI18n();

  return (
    <footer className="bg-brand-950 text-brand-100">
      <div className="mx-auto grid max-w-7xl gap-10 px-6 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <Logo textClass="text-white" />
          <p className="mt-5 max-w-xs text-sm leading-relaxed text-brand-200">
            {t("footer.aboutText")}
          </p>
        </div>

        <div>
          <h3 className="text-sm font-semibold uppercase tracking-wider text-gold-400">
            {t("footer.quickLinks")}
          </h3>
          <ul className="mt-5 space-y-3 text-sm">
            {FOOTER_LINKS.map((link) => (
              <li key={link.to}>
                <Link
                  to={link.to}
                  className="text-brand-200 transition-colors hover:text-white"
                >
                  {t(link.key)}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="text-sm font-semibold uppercase tracking-wider text-gold-400">
            {t("footer.contactTitle")}
          </h3>
          <ul className="mt-5 space-y-3 text-sm text-brand-200">
            <li className="flex items-start gap-3">
              <MapPin className="mt-0.5 size-4 shrink-0 text-gold-400" />
              <span>{t("contact.addressValue")}</span>
            </li>
            <li className="flex items-center gap-3">
              <Phone className="size-4 shrink-0 text-gold-400" />
              <a
                href={`tel:${HOTEL_PHONE.replace(/\s/g, "")}`}
                className="hover:text-white"
              >
                {HOTEL_PHONE}
              </a>
            </li>
            <li className="flex items-center gap-3">
              <Mail className="size-4 shrink-0 text-gold-400" />
              <a href={`mailto:${HOTEL_EMAIL}`} className="hover:text-white">
                {HOTEL_EMAIL}
              </a>
            </li>
          </ul>
        </div>

        <div>
          <h3 className="text-sm font-semibold uppercase tracking-wider text-gold-400">
            {t("footer.follow")}
          </h3>
          <div className="mt-5 flex gap-3">
            <a
              href={HOTEL_SOCIAL.facebook}
              target="https://www.facebook.com/profile.php?id=61585872256716&__cft__[0]=AZiyaCB-uZltPJUpSrm8hJ72UfEQ5oQLsGX1PuCkygI2zJVYUEoKL2o5Qb6D6IqE9B17GdcO4Q2CGaENPnlsccu-N9-VlKygAjgJPDGQuYKMO-vXPwN_fPAOepQ04kk2UdJDMDtVI_zNhXr2EZ-2cRYmKbdRil-TeHA1v3mW1keVxWo15f3ZKZqk3dO7dVU&__tn__=-UC%2CP-R"
              rel="noreferrer"
              aria-label="Facebook"
              className="grid size-10 place-items-center rounded-full border border-brand-800 text-brand-200 transition-colors hover:border-gold-400 hover:text-gold-400"
            >
              <FaFacebook className="size-4.5" />
            </a>
            <a
              href={HOTEL_SOCIAL.instagram}
              target="_blank"
              rel="noreferrer"
              aria-label="Instagram"
              className="grid size-10 place-items-center rounded-full border border-brand-800 text-brand-200 transition-colors hover:border-gold-400 hover:text-gold-400"
            >
              <FaInstagramSquare className="size-4.5" />
            </a>
            <a
              href={HOTEL_SOCIAL.tiktok}
              target="_blank"
              rel="noreferrer"
              aria-label="TikTok"
              className="grid size-10 place-items-center rounded-full border border-brand-800 text-brand-200 transition-colors hover:border-gold-400 hover:text-gold-400"
            >
              <FaTiktok className="size-4.5" />
            </a>
          </div>
          <p className="mt-5 text-xs text-brand-300">
            © {new Date().getFullYear()} {HOTEL_NAME} {t("footer.hotel")}.{" "}
            {t("footer.rights")} {t("auth.developedBy")}
          </p>
        </div>
      </div>
    </footer>
  );
}
