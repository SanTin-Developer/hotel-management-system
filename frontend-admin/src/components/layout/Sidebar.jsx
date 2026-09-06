import { useState } from "react";
import { NavLink, useLocation } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import {
  LayoutDashboard,
  CalendarCheck,
  Users,
  BedDouble,
  CreditCard,
  Tag,
  Star,
  UserCog,
  UserRound,
  X,
  ChevronDown,
} from "lucide-react";

const LOGO_URL =
  "https://res.cloudinary.com/drercy9vt/image/upload/v1788588116/Gemini_Generated_Image_m6go6vm6go6vm6go-removebg-preview_guzd4n.png";

const NAV_SECTIONS = [
  {
    label: "Overview",
    items: [
      { to: "/dashboard", label: "Dashboard", icon: LayoutDashboard },
      { to: "/bookings", label: "Bookings", icon: CalendarCheck },
      {
        label: "Room",
        icon: BedDouble,
        children: [
          { to: "/rooms", label: "Rooms", end: true },
          { to: "/rooms/types", label: "Room types", end: true },
          { to: "/rooms/amenities", label: "Amenities", end: true },
        ],
      },
    ],
  },
  {
    label: "Business",
    items: [
      { to: "/guests", label: "Guests", icon: Users },
      { to: "/payments", label: "Payments", icon: CreditCard },
      { to: "/coupons", label: "Coupons", icon: Tag },
      { to: "/reviews", label: "Reviews", icon: Star },
    ],
  },
  {
    label: "Administration",
    items: [
      {
        to: "/staff",
        label: "Staff",
        icon: UserCog,
        roles: ["admin", "manager"],
      },
      { to: "/profile", label: "Profile", icon: UserRound },
    ],
  },
];

function LinkMenuItem({ item, onClose }) {
  const Icon = item.icon;

  return (
    <NavLink
      to={item.to}
      onClick={onClose}
      className={({ isActive }) =>
        `group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
          isActive
            ? "bg-[#7FA35C]/15 text-[#F2F5EC]"
            : "text-[#AEC2A2] hover:bg-white/5 hover:text-[#F2F5EC]"
        }`
      }
    >
      {({ isActive }) => (
        <>
          {isActive && (
            <span className="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-[#9DBE7C]" />
          )}
          <Icon
            className={`h-[18px] w-[18px] shrink-0 transition-colors ${
              isActive
                ? "text-[#9DBE7C]"
                : "text-[#8FA38F] group-hover:text-[#9DBE7C]"
            }`}
            strokeWidth={1.5}
          />
          {item.label}
        </>
      )}
    </NavLink>
  );
}

function CollapsibleMenuItem({ item, onClose }) {
  const { pathname } = useLocation();
  const Icon = item.icon;
  const anyActive = item.children.some(
    (child) => child.to === pathname || pathname.startsWith(child.to),
  );
  const [open, setOpen] = useState(anyActive);

  if (anyActive && !open) {
    setOpen(true);
  }

  return (
    <div>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        className={`group relative flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
          anyActive
            ? "bg-[#7FA35C]/15 text-[#F2F5EC]"
            : "text-[#AEC2A2] hover:bg-white/5 hover:text-[#F2F5EC]"
        }`}
      >
        {anyActive && (
          <span className="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-[#9DBE7C]" />
        )}
        <Icon
          className={`h-[18px] w-[18px] shrink-0 transition-colors ${
            anyActive
              ? "text-[#9DBE7C]"
              : "text-[#8FA38F] group-hover:text-[#9DBE7C]"
          }`}
          strokeWidth={1.5}
        />
        {item.label}
        <ChevronDown
          className={`ml-auto h-4 w-4 transition-transform duration-200 ${
            anyActive ? "text-[#9DBE7C]" : "text-[#8FA38F]"
          } ${open ? "rotate-180" : ""}`}
          strokeWidth={1.5}
        />
      </button>
      {open && (
        <div className="mt-1 ml-2 space-y-1 border-l border-[#9DBE7C]/20 pl-3.5">
          {item.children.map((child) => (
            <NavLink
              key={child.to}
              to={child.to}
              end={child.end ?? false}
              onClick={onClose}
              className={({ isActive }) =>
                `group relative flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200 ${
                  isActive
                    ? "bg-[#7FA35C]/15 text-[#F2F5EC]"
                    : "text-[#AEC2A2] hover:bg-white/5 hover:text-[#F2F5EC]"
                }`
              }
            >
              {({ isActive }) => (
                <>
                  {isActive && (
                    <span className="absolute left-0 top-1/2 h-4 w-1 -translate-y-1/2 rounded-r-full bg-[#9DBE7C]" />
                  )}
                  <span
                    className={`h-1.5 w-1.5 shrink-0 rounded-full transition-colors ${
                      isActive ? "bg-[#9DBE7C]" : "bg-[#8FA38F]"
                    }`}
                  />
                  {child.label}
                </>
              )}
            </NavLink>
          ))}
        </div>
      )}
    </div>
  );
}

function MenuItem({ item, onClose }) {
  if (item.children) {
    return <CollapsibleMenuItem item={item} onClose={onClose} />;
  }

  return <LinkMenuItem item={item} onClose={onClose} />;
}

export default function Sidebar({ mobileOpen, onClose }) {
  const hasAnyRole = useAuthStore((state) => state.hasAnyRole);

  const content = (
    <>
      <div className="flex h-16 items-center gap-3 border-b border-[#9DBE7C]/15 px-6">
        <span className="flex h-9 w-9 items-center justify-center rounded-md border border-[#7FA35C]/50 bg-[#16261F]/60 p-1.5">
          <img
            src={LOGO_URL}
            alt="Kampuchea Otel"
            className="h-full w-full object-contain"
          />
        </span>
        <div className="min-w-0">
          <p
            className="truncate text-[17px] leading-tight text-[#F2F5EC]"
            style={{ fontFamily: "'Fraunces', serif" }}
          >
            Kampuchea Otel
          </p>
          <p className="text-[11px] tracking-wide text-[#AEC2A2]">
            Management Console
          </p>
        </div>
        <button
          type="button"
          onClick={onClose}
          aria-label="Close menu"
          className="ml-auto flex h-8 w-8 items-center justify-center rounded-md text-[#AEC2A2] transition-colors hover:bg-white/5 hover:text-[#F2F5EC] md:hidden"
        >
          <X className="h-4 w-4" strokeWidth={1.5} />
        </button>
      </div>
      <nav className="flex-1 space-y-6 overflow-y-auto px-4 py-6">
        {NAV_SECTIONS.map((section) => {
          const visibleItems = section.items.filter(
            (item) => !item.roles || hasAnyRole(item.roles),
          );
          if (visibleItems.length === 0) return null;

          return (
            <div key={section.label}>
              <p className="mb-2 px-3 text-[11px] font-medium uppercase tracking-[0.14em] text-[#AEC2A2]/70">
                {section.label}
              </p>
              <div className="space-y-1">
                {visibleItems.map((item) => (
                  <MenuItem key={item.to ?? item.label} item={item} onClose={onClose} />
                ))}
              </div>
            </div>
          );
        })}
      </nav>

      <div className="border-t border-[#9DBE7C]/15 p-4">
        <p className="px-3 text-[11px] text-[#AEC2A2]/70">
          © {new Date().getFullYear()} Kampuchea Otel
        </p>

        <p className="px-3 text-[11px] text-[#AEC2A2]/50">v1.0.0</p>

        <p className="px-3 text-[10px] text-[#AEC2A2]/40">
          Developed by <span className="text-[#9DBE7C]/80">SanTin</span>
        </p>
      </div>
    </>
  );

  return (
    <>
      {/* Desktop */}
      <aside className="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-[#16261F] lg:flex">
        {content}
      </aside>

      {/* Mobile drawer */}
      <div
        className={`fixed inset-0 z-50 transition-opacity duration-200 lg:hidden ${
          mobileOpen ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
      >
        <div
          className="absolute inset-0 bg-[#0F1712]/60 backdrop-blur-sm"
          onClick={onClose}
        />
        <aside
          className={`absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col bg-[#16261F] shadow-2xl transition-transform duration-250 ease-in-out ${
            mobileOpen ? "translate-x-0" : "-translate-x-full"
          }`}
        >
          {content}
        </aside>
      </div>
    </>
  );
}