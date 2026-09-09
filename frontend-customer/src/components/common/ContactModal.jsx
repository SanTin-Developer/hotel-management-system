import { Mail, Send, Phone } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { useI18n } from "@/i18n";

const CONTACT_OPTIONS = [
  {
    key: "email",
    icon: Mail,
    value: "santinoeurn0601@gmail.com",
    href: "mailto:santinoeurn0601@gmail.com",
  },
  {
    key: "telegram",
    icon: Send,
    value: "santin_oeurn",
    href: "https://t.me/santin_oeurn",
  },
  {
    key: "phone",
    icon: Phone,
    value: "076 103 9432",
    href: "tel:0761039432",
  },
];

export function ContactModal({ open, onOpenChange }) {
  const { t } = useI18n();

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{t("contact.modal.title")}</DialogTitle>
          <DialogDescription>
            {t("contact.modal.subtitle")}
          </DialogDescription>
        </DialogHeader>
        <div className="flex flex-col gap-2">
          {CONTACT_OPTIONS.map(({ key, icon: Icon, value, href }) => (
            <a
              key={key}
              href={href}
              target="_blank"
              rel="noreferrer"
              className="flex items-center gap-3 rounded-xl border border-border px-4 py-3 transition-colors hover:border-brand-300 hover:bg-brand-50"
            >
              <span className="grid size-10 shrink-0 place-items-center rounded-full bg-brand-900 text-white">
                <Icon className="size-4.5" />
              </span>
              <span>
                <span className="block text-xs font-medium uppercase tracking-wide text-muted-foreground">
                  {t(`contact.modal.${key}`)}
                </span>
                <span className="block text-sm font-semibold text-foreground">
                  {value}
                </span>
              </span>
            </a>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
}
