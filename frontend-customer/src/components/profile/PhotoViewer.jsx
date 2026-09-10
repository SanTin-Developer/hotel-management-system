import { Dialog, DialogContent } from "@/components/ui/dialog";
import { useI18n } from "@/i18n";

export function PhotoViewer({ open, src, alt, onOpenChange }) {
  const { t } = useI18n();
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton
        className="max-w-[min(92vw,900px)] border-0 bg-transparent p-0 shadow-none ring-0"
      >
        <div className="overflow-hidden rounded-2xl bg-black/90 p-2">
          <img
            src={src}
            alt={alt ?? t("profile.avatarAlt")}
            className="mx-auto max-h-[80vh] w-auto rounded-xl object-contain"
          />
        </div>
      </DialogContent>
    </Dialog>
  );
}