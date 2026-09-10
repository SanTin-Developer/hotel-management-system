import { NavLink, Outlet } from "react-router-dom";
import { useState } from "react";
import {
  LayoutDashboard,
  Briefcase,
  UserCog,
  LogOut,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { PhotoViewer } from "@/components/profile/PhotoViewer";
import { avatarFallback } from "@/lib/avatar";
import { useAuth } from "@/hooks/useAuth";
import { ROUTES } from "@/constants/routes";
import { useI18n } from "@/i18n";

export function ProfileLayout() {
  const { user, guest, logout } = useAuth();
  const { t } = useI18n();
  const [viewerOpen, setViewerOpen] = useState(false);

  const NAV = [
    { to: ROUTES.profile, labelKey: "profile.overview", icon: LayoutDashboard, end: true },
    { to: ROUTES.profileBookings, labelKey: "profile.myStays", icon: Briefcase },
    { to: ROUTES.profileSettings, labelKey: "profile.settings", icon: UserCog },
  ];

  return (
    <section className="bg-cream py-10 sm:py-14">
      <div className="mx-auto max-w-6xl px-6">
        <div className="grid gap-8 lg:grid-cols-[260px_1fr]">
          <aside className="lg:sticky lg:top-24 lg:self-start">
            <div className="rounded-2xl border border-border bg-white p-6">
              <div className="flex items-center gap-4">
                <button
                  type="button"
                  onClick={() => user?.guest?.photo_url && setViewerOpen(true)}
                  disabled={!user?.guest?.photo_url}
                  title={user?.guest?.photo_url ? t("settings.viewPhoto") : undefined}
                  className="grid size-14 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-900 text-lg font-semibold text-white disabled:cursor-default"
                >
                  {user?.guest?.photo_url ? (
                    <img
                      src={user.guest.photo_url}
                      alt={guest?.full_name ?? user?.name ?? "avatar"}
                      className="size-full object-cover"
                    />
                  ) : (
                    avatarFallback(guest?.full_name ?? user?.name ?? "Guest")
                  )}
                </button>
                <div className="min-w-0">
                  <p className="truncate font-semibold">
                    {guest?.full_name ?? user?.name}
                  </p>
                  <p className="truncate text-xs text-muted-foreground">
                    {guest?.email ?? user?.email}
                  </p>
                </div>
              </div>

              <nav className="mt-6 space-y-1">
                {NAV.map((item) => {
                  const Icon = item.icon;
                  return (
                    <NavLink
                      key={item.to}
                      to={item.to}
                      end={item.end}
                      className={({ isActive }) =>
                        `flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors ${
                          isActive
                            ? "bg-brand-900 text-white"
                            : "text-muted-foreground hover:bg-brand-50 hover:text-brand-900"
                        }`
                      }
                    >
                      <Icon className="size-4" />
                      {t(item.labelKey)}
                    </NavLink>
                  );
                })}
              </nav>

              <Button
                variant="outline"
                size="pill"
                className="mt-6 w-full"
                onClick={logout}
              >
                <LogOut /> {t("profile.signOut")}
              </Button>
            </div>
          </aside>

          <main className="min-w-0">
            <Outlet />
          </main>
        </div>
      </div>

      <PhotoViewer
        open={viewerOpen}
        src={user?.guest?.photo_url}
        alt={guest?.full_name ?? user?.name ?? "avatar"}
        onOpenChange={setViewerOpen}
      />
    </section>
  );
}