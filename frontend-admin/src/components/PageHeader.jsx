export default function PageHeader({ title, description, children }) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-4 sm:mb-8">
      <div>
        <h1
          className="text-[26px] leading-tight text-[#1E2B22] sm:text-3xl"
          style={{ fontFamily: "'Fraunces', serif" }}
        >
          {title}
        </h1>
        {description && (
          <p className="mt-1.5 text-sm text-[#5E6B5A]">{description}</p>
        )}
      </div>
      {children && <div className="flex flex-wrap items-center gap-2">{children}</div>}
    </div>
  );
}