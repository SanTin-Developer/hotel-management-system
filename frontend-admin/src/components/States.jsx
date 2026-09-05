import { Inbox } from "lucide-react";

export function EmptyState({ title = "Nothing here yet", description, action }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-[#DCE3D5] bg-white px-6 py-14 text-center">
      <span className="flex h-12 w-12 items-center justify-center rounded-full bg-[#F1F3ED] text-[#A8B39F]">
        <Inbox className="h-6 w-6" strokeWidth={1.5} />
      </span>
      <h3 className="mt-4 text-base font-medium text-[#1E2B22]" style={{ fontFamily: "'Fraunces', serif" }}>
        {title}
      </h3>
      {description && (
        <p className="mt-1.5 max-w-sm text-sm text-[#7A8677]">{description}</p>
      )}
      {action && <div className="mt-5">{action}</div>}
    </div>
  );
}

export function LoadingState({ label = "Loading…" }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-xl border border-[#DCE3D5] bg-white px-6 py-14">
      <span className="h-8 w-8 animate-spin rounded-full border-2 border-[#7FA35C]/30 border-t-[#7FA35C]" />
      <p className="mt-4 text-sm text-[#7A8677]">{label}</p>
    </div>
  );
}

export function ErrorState({
  message = "Sorry, we couldn't load this data. Please try again.",
  onRetry,
}) {
  return (
    <div className="flex flex-col items-center justify-center rounded-xl border border-[#C25B50]/20 bg-[#C25B50]/5 px-6 py-14 text-center">
      <h3 className="text-base font-medium text-[#B3453A]">
        Something went wrong
      </h3>
      <p className="mt-1.5 max-w-sm text-sm text-[#7A8677]">{message}</p>
      {onRetry && (
        <button
          type="button"
          onClick={onRetry}
          className="mt-5 inline-flex h-9 items-center gap-2 rounded-lg bg-[#16261F] px-4 text-sm font-medium text-[#F2F5EC] transition-colors hover:bg-[#20372C]"
        >
          Retry
        </button>
      )}
    </div>
  );
}

export function TableSkeleton({ rows = 5 }) {
  return (
    <div className="overflow-hidden rounded-xl border border-[#DCE3D5] bg-white">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="flex items-center gap-4 border-b border-[#F1F3ED] px-5 py-4 last:border-0">
          <div className="h-4 w-32 animate-pulse rounded bg-[#EEF1E9]" />
          <div className="h-4 w-24 animate-pulse rounded bg-[#EEF1E9]" />
          <div className="ml-auto h-7 w-20 animate-pulse rounded-full bg-[#EEF1E9]" />
        </div>
      ))}
    </div>
  );
}