import { cn } from "@/lib/utils";

export function Eyebrow({ children, className }) {
  return (
    <p
      className={cn(
        "inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-brand-700",
        className
      )}
    >
      <span className="h-px w-6 bg-gold-400" />
      {children}
    </p>
  );
}

export function SectionHeading({
  eyebrow,
  title,
  subtitle,
  align = "center",
  className,
  titleClassName,
}) {
  return (
    <div
      className={cn(
        "max-w-2xl",
        align === "center" && "mx-auto text-center",
        className
      )}
    >
      {eyebrow && (
        <Eyebrow
          className={cn(
            align === "center" && "justify-center",
            align === "center" && "[&>span]:hidden"
          )}
        >
          {eyebrow}
        </Eyebrow>
      )}
      <h2
        className={cn(
          "mt-3 text-3xl font-semibold tracking-tight sm:text-4xl",
          titleClassName
        )}
      >
        {title}
      </h2>
      {subtitle && (
        <p className="mt-4 text-base leading-relaxed text-muted-foreground">
          {subtitle}
        </p>
      )}
    </div>
  );
}