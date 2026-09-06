import FormModal from "@/components/FormModal";
import { titleCase } from "@/lib/format";

function formatValue(value) {
  if (value === null || value === undefined || value === "") return null;
  if (typeof value === "boolean") return value ? "Yes" : "No";
  if (Array.isArray(value)) {
    if (value.length === 0) return null;
    return value
      .map((item) =>
        typeof item === "object"
          ? JSON.stringify(item, null, 2)
          : String(item)
      )
      .join("\n");
  }
  if (typeof value === "object") return JSON.stringify(value, null, 2);
  return String(value);
}

function isNested(raw) {
  return Array.isArray(raw) || (raw !== null && typeof raw === "object");
}

export default function DetailModal({
  open,
  onOpenChange,
  title = "Details",
  description,
  record,
}) {
  const entries = record
    ? Object.entries(record).filter(([key, value]) => {
        if (key === "id") return false;
        return formatValue(value) !== null;
      })
    : [];

  return (
    <FormModal
      open={open}
      onOpenChange={onOpenChange}
      title={title}
      description={description}
    >
      <dl className="overflow-hidden rounded-xl border border-[#DCE3D5]">
        {entries.map(([key, raw]) => {
          const value = formatValue(raw);
          return (
            <div
              key={key}
              className="grid grid-cols-[130px_1fr] gap-3 border-b border-[#EEF1E9] px-4 py-3 last:border-0 sm:grid-cols-[180px_1fr]"
            >
              <dt className="pt-0.5 text-xs font-semibold uppercase tracking-wide text-[#7A8677]">
                {titleCase(key)}
              </dt>
              <dd className="min-w-0 text-sm text-[#1E2B22]">
                {isNested(raw) ? (
                  <pre className="whitespace-pre-wrap break-words rounded-lg bg-[#F8F9F4] p-2.5 text-xs leading-relaxed text-[#5E6B5A]">
                    {value}
                  </pre>
                ) : (
                  <span className="break-words">{value}</span>
                )}
              </dd>
            </div>
          );
        })}
      </dl>
    </FormModal>
  );
}