import { Phone, Mail, Clock } from "lucide-react";
import { FaFacebook, FaInstagramSquare, FaTiktok } from "react-icons/fa";
import {
  HOTEL_PHONE,
  HOTEL_EMAIL,
  HOTEL_HOURS,
  HOTEL_SOCIAL,
} from "@/constants/routes";
import { FinisherShapes } from "@/components/common/FinisherShapes";

const SOCIAL_LINKS = [
  { href: HOTEL_SOCIAL.facebook, label: "Facebook", icon: FaFacebook },
  { href: HOTEL_SOCIAL.instagram, label: "Instagram", icon: FaInstagramSquare },
  { href: HOTEL_SOCIAL.tiktok, label: "Tik Tok", icon: FaTiktok },
];

function CambodiaFlag({ className }) {
  return (
    <svg
      viewBox="0 0 25 16"
      className={className}
      aria-hidden="true"
      role="img"
    >
      <rect width="25" height="4" fill="#032ea1" />
      <rect y="4" width="25" height="8" fill="#e00025" />
      <rect y="12" width="25" height="4" fill="#032ea1" />
      <g fill="#ffffff">
        <path d="M7 12.1h11v1.2h-11z" />
        <path d="M8.4 12.1V10.9l1.2-1.6 1.2 1.6v1.2z" />
        <path d="M9.6 9.3v-1.2" stroke="#ffffff" strokeWidth="1" />
        <path d="M11 12.1v-1.9l1.5-2.1 1.5 2.1v1.9z" />
        <path d="M12.5 8.1v-1.5" stroke="#ffffff" strokeWidth="1" />
        <path d="M14.2 12.1V10.9l1.2-1.6 1.2 1.6v1.2z" />
        <path d="M15.4 9.3v-1.2" stroke="#ffffff" strokeWidth="1" />
      </g>
    </svg>
  );
}

export function Header() {
  return (
    <div className="relative isolate hidden overflow-hidden bg-brand-950 text-brand-100 lg:block">
      <FinisherShapes />
      <div className="pointer-events-none absolute inset-0 bg-brand-950/60" />
      <div className="relative mx-auto flex h-14 max-w-7xl items-center justify-between px-6 text-xs md:h-16">
        <div className="flex items-center gap-5">
          <a
            href={`tel:${HOTEL_PHONE.replace(/\s/g, "")}`}
            className="inline-flex items-center gap-1.5 transition-colors hover:text-gold-300"
          >
            <CambodiaFlag className="size-4 shrink-0 rounded-[2px] shadow-sm ring-1 ring-white/15" />
            <Phone className="size-3.5" />
            {HOTEL_PHONE}
          </a>
          <div className="flex items-center gap-2 border-l border-white/15 pl-5">
            {SOCIAL_LINKS.map(({ href, label, icon: Icon }) => (
              <a
                key={label}
                href={href}
                target="_blank"
                rel="noreferrer"
                aria-label={label}
                className="grid size-8 place-items-center rounded-full bg-white/10 text-white transition-all duration-200 hover:bg-gold-400 hover:text-brand-950"
              >
                <Icon className="size-4" />
              </a>
            ))}
          </div>
          <a
            href={`mailto:${HOTEL_EMAIL}`}
            className="inline-flex items-center gap-1.5 transition-colors hover:text-gold-300"
          >
            <Mail className="size-3.5" />
            {HOTEL_EMAIL}
          </a>
        </div>
        <div className="inline-flex items-center gap-1.5 text-brand-200">
          <Clock className="size-3.5 text-gold-400" />
          <span className="inline-flex items-center gap-1.5">
            <CambodiaFlag className="size-4 rounded-[2px] shadow-sm ring-1 ring-white/15" />
            {HOTEL_HOURS}
          </span>
        </div>
      </div>
    </div>
  );
}
