import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";

function pageNumbers(current, last) {
  const delta = 2;
  const range = [];
  for (let p = 1; p <= last; p++) {
    if (p === 1 || p === last || Math.abs(p - current) <= delta) {
      range.push(p);
    } else if (range[range.length - 1] !== "…") {
      range.push("…");
    }
  }
  return range;
}

export default function Pagination({ current, total, perPage = 15, onPageChange }) {
  const last = Math.max(1, Math.ceil(total / perPage));

  if (last <= 1) return null;

  const from = total === 0 ? 0 : (current - 1) * perPage + 1;
  const to = Math.min(total, current * perPage);

  const btn =
    "flex h-8 min-w-8 items-center justify-center rounded-lg border border-[#DCE3D5] bg-white px-2 text-sm text-[#5E6B5A] transition-colors hover:border-[#7FA35C] hover:text-[#1E2B22] disabled:pointer-events-none disabled:opacity-40";

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[#EEF1E9] px-5 py-4">
      <p className="text-xs text-[#7A8677]">
        Showing <span className="font-medium text-[#1E2B22]">{from}</span>–
        <span className="font-medium text-[#1E2B22]">{to}</span> of{" "}
        <span className="font-medium text-[#1E2B22]">{total}</span>
      </p>
      <div className="flex items-center gap-1.5">
        <button
          type="button"
          className={btn}
          disabled={current <= 1}
          onClick={() => onPageChange(current - 1)}
          aria-label="Previous page"
        >
          <ChevronLeft className="h-4 w-4" strokeWidth={1.5} />
        </button>
        {pageNumbers(current, last).map((p, i) =>
          p === "…" ? (
            <span key={`e${i}`} className="px-1 text-xs text-[#A8B39F]">
              …
            </span>
          ) : (
            <button
              key={p}
              type="button"
              onClick={() => onPageChange(p)}
              aria-current={p === current ? "page" : undefined}
              className={cn(
                btn,
                p === current &&
                  "border-[#16261F] bg-[#16261F] text-[#F2F5EC] hover:border-[#20372C] hover:text-[#F2F5EC]",
              )}
            >
              {p}
            </button>
          ),
        )}
        <button
          type="button"
          className={btn}
          disabled={current >= last}
          onClick={() => onPageChange(current + 1)}
          aria-label="Next page"
        >
          <ChevronRight className="h-4 w-4" strokeWidth={1.5} />
        </button>
      </div>
    </div>
  );
}