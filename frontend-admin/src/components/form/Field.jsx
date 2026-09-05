export function Field({ label, error, hint, htmlFor, required, children }) {
  return (
    <div>
      {label && (
        <label
          htmlFor={htmlFor}
          className="mb-1.5 block text-sm font-medium text-[#1E2B22]"
        >
          {label}
          {required && <span className="text-[#C25B50]"> *</span>}
        </label>
      )}
      {children}
      {hint && !error && <p className="mt-1.5 text-xs text-[#7A8677]">{hint}</p>}
      {error && <p className="mt-1.5 text-xs text-[#B3453A]">{error}</p>}
    </div>
  );
}