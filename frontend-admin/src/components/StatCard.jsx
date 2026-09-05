export default function StatCard({ label, value, icon: Icon, accent = "leaf", sublabel }) {
  const accents = {
    leaf: "bg-[#7FA35C]/12 text-[#4F7A3B]",
    dark: "bg-[#16261F] text-[#9DBE7C]",
    sand: "bg-[#B5C9A4]/20 text-[#5E6B5A]",
    rose: "bg-[#C25B50]/10 text-[#B3453A]",
    gold: "bg-[#D9A441]/15 text-[#A67C16]",
  };

  return (
    <div className="rounded-xl border border-[#DCE3D5] bg-white p-4 transition-shadow duration-200 hover:shadow-[0_8px_24px_rgba(30,43,34,0.06)] sm:p-5">
      <div className="flex items-start justify-between">
        <div className="min-w-0">
          <p className="text-[13px] font-medium text-[#7A8677]">{label}</p>
          <p className="mt-1.5 text-2xl font-semibold tracking-tight text-[#1E2B22] sm:text-[28px]">
            {value}
          </p>
          {sublabel && <p className="mt-1 text-xs text-[#A8B39F]">{sublabel}</p>}
        </div>
        {Icon && (
          <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${accents[accent] ?? accents.leaf}`}>
            <Icon className="h-5 w-5" strokeWidth={1.5} />
          </span>
        )}
      </div>
    </div>
  );
}