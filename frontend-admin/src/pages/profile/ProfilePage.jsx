import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { LogOut, Mail, ShieldCheck, BadgeCheck, Loader2 } from "lucide-react";

import PageHeader from "@/components/PageHeader";
import { useAuthStore } from "@/store/authStore";
import { logout } from "@/features/auth/authApi";
import { toast } from "sonner";
import { initialsOf } from "@/lib/format";
import LoadingOverlay from "@/components/LoadingOverlay";

const ROLE_TINTS = {
  admin: "bg-[#16261F] text-[#9DBE7C]",
  manager: "bg-[#7FA35C]/15 text-[#4F7A3B]",
  staff: "bg-[#D9A441]/15 text-[#A67C16]",
};

export default function ProfilePage() {
  const { user, clearAuth } = useAuthStore();
  const navigate = useNavigate();
  const [loggingOut, setLoggingOut] = useState(false);

  async function handleLogout() {
    setLoggingOut(true);
    try {
      await logout();
    } catch {
      // even if it fails, clear local state so the user can leave
    } finally {
      clearAuth();
      toast.success("Logged out successfully. See you next time!");
      navigate("/login", { replace: true });
    }
  }

  const role = user?.roles?.[0]?.name ?? "staff";

  return (
    <div>
      <PageHeader
        title="Profile"
        description="Your account and role information on this console."
      />

      <div className="mx-auto max-w-2xl space-y-4">
        <div className="flex flex-col items-center gap-4 rounded-2xl border border-[#DCE3D5] bg-white p-8 text-center">
          <span className="flex h-20 w-20 items-center justify-center rounded-full bg-[#16261F] text-2xl font-semibold text-[#9DBE7C]">
            {initialsOf(user?.name)}
          </span>
          <div>
            <h2
              className="text-2xl text-[#1E2B22]"
              style={{ fontFamily: "'Fraunces', serif" }}
            >
              {user?.name ?? "—"}
            </h2>
            <span
              className={`mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                ROLE_TINTS[role] ?? ROLE_TINTS.staff
              }`}
            >
              <ShieldCheck className="h-3.5 w-3.5" strokeWidth={1.5} />
              {role}
            </span>
          </div>
          <p className="max-w-md text-sm text-[#7A8677]">
            This account was created through your staff invitation. Role changes are
            managed on the Staff page.
          </p>
        </div>

        <div className="overflow-hidden rounded-2xl border border-[#DCE3D5] bg-white">
          <div className="border-b border-[#EEF1E9] px-6 py-3">
            <p className="text-sm font-medium text-[#1E2B22]">Account details</p>
          </div>
          <dl className="divide-y divide-[#EEF1E9]">
            <div className="flex items-center gap-3 px-6 py-4">
              <Mail className="h-4 w-4 shrink-0 text-[#7A8677]" strokeWidth={1.5} />
              <dt className="w-24 text-sm text-[#7A8677]">Email</dt>
              <dd className="text-sm font-medium text-[#1E2B22]">{user?.email ?? "—"}</dd>
            </div>
            <div className="flex items-center gap-3 px-6 py-4">
              <BadgeCheck className="h-4 w-4 shrink-0 text-[#7A8677]" strokeWidth={1.5} />
              <dt className="w-24 text-sm text-[#7A8677]">Role</dt>
              <dd className="text-sm font-medium capitalize text-[#1E2B22]">{role}</dd>
            </div>
          </dl>
        </div>

        <div className="flex flex-col items-center gap-2 rounded-2xl border border-[#EEF1E9] bg-[#F8F9F4] p-6 text-center">
          <p className="text-sm text-[#7A8677]">
            Want to switch to a different account and back out?
          </p>
          <button
            type="button"
            onClick={handleLogout}
            disabled={loggingOut}
            className="mt-1 inline-flex items-center gap-2 rounded-lg border border-[#DCE3D5] bg-white px-4 py-2 text-sm font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] hover:text-[#B3453A] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loggingOut ? (
              <Loader2 className="h-4 w-4 animate-spin" strokeWidth={1.5} />
            ) : (
              <LogOut className="h-4 w-4" strokeWidth={1.5} />
            )}
            {loggingOut ? "Signing out…" : "Log out"}
          </button>
        </div>
      </div>

      {loggingOut && <LoadingOverlay label="Signing out…" />}
    </div>
  );
}