export function avatarFallback(name) {
  const clean = String(name ?? "").trim() || "G";
  const parts = clean.split(/\s+/).slice(0, 2);
  return parts
    .map((part) => part.charAt(0))
    .join("")
    .toUpperCase();
}