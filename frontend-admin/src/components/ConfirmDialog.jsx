import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { AlertTriangle, Loader2 } from "lucide-react";

export default function ConfirmDialog({
  open,
  onOpenChange,
  title = "Are you sure?",
  description = "This action cannot be undone.",
  confirmLabel = "Confirm",
  variant = "danger",
  loading = false,
  onConfirm,
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader className="items-center gap-3 text-center">
          <span
            className={`flex h-12 w-12 items-center justify-center rounded-full ${
              variant === "danger"
                ? "bg-[#C25B50]/10 text-[#B3453A]"
                : "bg-[#7FA35C]/12 text-[#4F7A3B]"
            }`}
          >
            <AlertTriangle className="h-6 w-6" strokeWidth={1.5} />
          </span>
          <DialogTitle
            className="text-lg"
            style={{ fontFamily: "'Fraunces', serif" }}
          >
            {title}
          </DialogTitle>
          <DialogDescription className="text-sm leading-relaxed">
            {description}
          </DialogDescription>
        </DialogHeader>
        <DialogFooter className="grid grid-cols-2 gap-2 sm:justify-end">
          <button
            type="button"
            disabled={loading}
            onClick={() => onOpenChange(false)}
            className="flex h-11 items-center justify-center rounded-lg border border-[#DCE3D5] bg-white px-4 text-sm font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] disabled:opacity-50"
          >
            Cancel
          </button>
          <button
            type="button"
            disabled={loading}
            onClick={onConfirm}
            className={`flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium text-[#F2F5EC] transition-all disabled:opacity-50 ${
              variant === "danger"
                ? "bg-[#B3453A] hover:bg-[#9E3A31] hover:shadow-[0_0_0_3px_rgba(194,91,80,0.25)]"
                : "bg-[#16261F] hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)]"
            }`}
          >
            {loading && <Loader2 className="h-4 w-4 animate-spin" />}
            {confirmLabel}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}