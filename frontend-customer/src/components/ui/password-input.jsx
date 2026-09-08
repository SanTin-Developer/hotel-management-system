import { useState } from "react";
import { Eye, EyeOff } from "lucide-react";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
import { useI18n } from "@/i18n";

function PasswordInput({ className, ...props }) {
  const { t } = useI18n();
  const [revealed, setRevealed] = useState(false);

  return (
    <div className="relative">
      <Input
        type={revealed ? "text" : "password"}
        className={cn("pr-10", className)}
        {...props}
      />
      <button
        type="button"
        onClick={() => setRevealed((value) => !value)}
        aria-label={revealed ? t("auth.hidePassword") : t("auth.showPassword")}
        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground focus:outline-none focus-visible:text-foreground"
      >
        {revealed ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
      </button>
    </div>
  );
}

export { PasswordInput }