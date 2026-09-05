import { createPortal } from "react-dom";
import { Loader2 } from "lucide-react";

export default function LoadingOverlay({ label = "Loading…" }) {
  return createPortal(
    <div className="fixed inset-0 z-[100] flex flex-col items-center justify-center gap-4 bg-[#F8F9F4]/95 backdrop-blur-sm">
      <Loader2 className="h-10 w-10 animate-spin text-[#16261F]" strokeWidth={1.5} />
      <p className="text-sm font-medium text-[#5E6B5A]">{label}</p>
    </div>,
    document.body,
  );
}