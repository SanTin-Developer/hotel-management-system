import { useState } from "react";
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

const IMAGE_KEYS = new Set([
  "photo_url",
  "image_url",
  "avatar_url",
  "thumbnail_url",
  "cover_url",
]);

function isImageUrl(value) {
  return (
    typeof value === "string" &&
    (value.startsWith("http://") ||
      value.startsWith("https://") ||
      value.startsWith("data:image/"))
  );
}

function ImageRow({ src, alt, onZoom }) {
  return (
    <button
      type="button"
      onClick={onZoom}
      className="block overflow-hidden rounded-lg border border-[#DCE3D5] transition-transform hover:scale-[1.02] hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7FA35C]"
      aria-label="Zoom photo"
    >
      <img
        src={src}
        alt={alt}
        className="h-40 w-40 object-cover"
        onError={(event) => {
          event.currentTarget.style.visibility = "hidden";
        }}
      />
    </button>
  );
}

function ImageZoom({ src, alt, onClose }) {
  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
      role="dialog"
      aria-modal="true"
      aria-label="Fullscreen photo"
      onClick={onClose}
    >
      <button
        type="button"
        onClick={onClose}
        aria-label="Close"
        className="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition-colors hover:bg-white/20"
      >
        ×
      </button>
      <img
        src={src}
        alt={alt}
        className="max-h-full max-w-full rounded-lg object-contain shadow-2xl"
        onClick={(event) => event.stopPropagation()}
      />
    </div>
  );
}

export default function DetailModal({
  open,
  onOpenChange,
  title = "Details",
  description,
  record,
}) {
  const [zoomSrc, setZoomSrc] = useState(null);

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
          const isImageKey = IMAGE_KEYS.has(key);
          const isUrlString = isImageUrl(raw) && !isNested(raw);
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
                {isImageKey && isUrlString ? (
                  <ImageRow
                    src={raw}
                    alt={titleCase(key)}
                    onZoom={() => setZoomSrc(raw)}
                  />
                ) : isNested(raw) ? (
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

      {zoomSrc && (
        <ImageZoom
          src={zoomSrc}
          alt={title}
          onClose={() => setZoomSrc(null)}
        />
      )}
    </FormModal>
  );
}