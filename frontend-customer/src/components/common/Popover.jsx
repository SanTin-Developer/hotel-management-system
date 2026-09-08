import { useEffect, useRef, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { cn } from "@/lib/utils";

export function Popover({
  trigger,
  children,
  align = "start",
  side = "bottom",
  sideOffset = 8,
  className,
  contentClassName,
  matchTriggerWidth = true,
}) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef(null);

  useEffect(() => {
    if (!open) return;
    const onPointerDown = (event) => {
      if (!rootRef.current?.contains(event.target)) setOpen(false);
    };
    const onKeyDown = (event) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  return (
    <div ref={rootRef} className={cn("relative", className)}>
      <div onClick={() => setOpen((value) => !value)}>{trigger}</div>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: 6, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 4, scale: 0.98 }}
            transition={{ duration: 0.14 }}
            className={cn(
              "absolute z-40",
              side === "bottom" && "top-full",
              side === "top" && "bottom-full",
              align === "start" && "left-0",
              align === "end" && "right-0",
              matchTriggerWidth && "w-full",
              contentClassName
            )}
            style={
              side === "bottom"
                ? { marginTop: sideOffset }
                : { marginBottom: sideOffset }
            }
          >
            {children}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}