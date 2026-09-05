import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Loader2 } from "lucide-react";

export default function FormModal({
  open,
  onOpenChange,
  title,
  description,
  children,
  footer,
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] gap-5 overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle
            className="text-lg"
            style={{ fontFamily: "'Fraunces', serif" }}
          >
            {title}
          </DialogTitle>
          {description && (
            <DialogDescription className="text-sm">{description}</DialogDescription>
          )}
        </DialogHeader>
        <div className="space-y-5">{children}</div>
        {footer}
      </DialogContent>
    </Dialog>
  );
}

export function FormActions({ onCancel, submitLabel, loading }) {
  return (
    <div className="flex flex-col-reverse gap-2 border-t border-[#EEF1E9] pt-4 sm:flex-row sm:justify-end">
      <button
        type="button"
        onClick={onCancel}
        disabled={loading}
        className="flex h-11 items-center justify-center rounded-lg border border-[#DCE3D5] bg-white px-5 text-sm font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] disabled:opacity-50"
      >
        Cancel
      </button>
      <button
        type="submit"
        disabled={loading}
        className="flex h-11 items-center justify-center gap-2 rounded-lg bg-[#16261F] px-5 text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-none"
      >
        {loading && <Loader2 className="h-4 w-4 animate-spin" />}
        {submitLabel}
      </button>
    </div>
  );
}