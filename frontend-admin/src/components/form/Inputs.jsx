import { useState, useRef } from "react";
import { Eye, EyeOff, ImagePlus, Trash2 } from "lucide-react";
import { Field } from "./Field";

const baseInputClass =
  "h-[46px] w-full rounded-lg border border-[#DCE3D5] bg-white px-3.5 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:border-[#7FA35C] focus:ring-4 focus:ring-[#7FA35C]/15 disabled:cursor-not-allowed disabled:bg-[#F3F4EF] disabled:opacity-70";

const errorInputClass =
  "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10";

function fieldClass(error) {
  return `${baseInputClass} ${error ? errorInputClass : ""}`;
}

export function TextField({
  label,
  name,
  value,
  onChange,
  error,
  hint,
  type = "text",
  required,
  placeholder,
  autoComplete,
  inputMode,
  maxLength,
  disabled,
  readOnly,
}) {
  return (
    <Field label={label} error={error} hint={hint} htmlFor={name} required={required}>
      <input
        id={name}
        name={name}
        type={type}
        value={value ?? ""}
        onChange={(e) => onChange?.(e.target.value)}
        aria-invalid={Boolean(error)}
        className={fieldClass(error)}
        placeholder={placeholder}
        required={required}
        autoComplete={autoComplete}
        inputMode={inputMode}
        maxLength={maxLength}
        disabled={disabled}
        readOnly={readOnly}
      />
    </Field>
  );
}

export function PasswordField({
  label,
  name,
  value,
  onChange,
  error,
  hint,
  required,
  placeholder,
  autoComplete,
}) {
  const [show, setShow] = useState(false);
  return (
    <Field label={label} error={error} hint={hint} htmlFor={name} required={required}>
      <div className="relative">
        <input
          id={name}
          name={name}
          type={show ? "text" : "password"}
          value={value ?? ""}
          onChange={(e) => onChange?.(e.target.value)}
          aria-invalid={Boolean(error)}
          className={`${fieldClass(error)} pr-11`}
          placeholder={placeholder}
          required={required}
          autoComplete={autoComplete}
        />
        <button
          type="button"
          onClick={() => setShow((v) => !v)}
          aria-label={show ? "Hide password" : "Show password"}
          className="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#A8B39F] transition-colors hover:text-[#5E6B5A]"
        >
          {show ? (
            <EyeOff className="h-4 w-4" strokeWidth={1.5} />
          ) : (
            <Eye className="h-4 w-4" strokeWidth={1.5} />
          )}
        </button>
      </div>
    </Field>
  );
}

export function TextAreaField({
  label,
  name,
  value,
  onChange,
  error,
  hint,
  required,
  placeholder,
  rows = 3,
  maxLength,
}) {
  return (
    <Field label={label} error={error} hint={hint} htmlFor={name} required={required}>
      <textarea
        id={name}
        name={name}
        value={value ?? ""}
        onChange={(e) => onChange?.(e.target.value)}
        aria-invalid={Boolean(error)}
        className={`${fieldClass(error)} h-auto min-h-[92px] resize-y py-3`}
        placeholder={placeholder}
        required={required}
        rows={rows}
        maxLength={maxLength}
      />
    </Field>
  );
}

export function DateField({
  label,
  name,
  value,
  onChange,
  error,
  hint,
  required,
  min,
  max,
}) {
  return (
    <Field label={label} error={error} hint={hint} htmlFor={name} required={required}>
      <input
        id={name}
        name={name}
        type="date"
        value={value ?? ""}
        onChange={(e) => onChange?.(e.target.value)}
        aria-invalid={Boolean(error)}
        className={`${fieldClass(error)} ${!value ? "text-[#A8B39F]" : ""}`}
        required={required}
        min={min}
        max={max}
      />
    </Field>
  );
}

export function SelectField({
  label,
  name,
  value,
  onChange,
  error,
  hint,
  required,
  options,
  placeholder = "Select an option",
}) {
  return (
    <Field label={label} error={error} hint={hint} htmlFor={name} required={required}>
      <select
        id={name}
        name={name}
        value={value ?? ""}
        onChange={(e) => onChange?.(e.target.value)}
        aria-invalid={Boolean(error)}
        className={`${fieldClass(error)} appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2216%22%20height%3D%2216%22%20fill%3D%22%235E6B5A%22%20viewBox%3D%220%200%2016%2016%22%3E%3Cpath%20d%3D%22M4.646%206.146a.5.5%200%200%201%20.708%200L8%208.793l2.646-2.647a.5.5%200%200%201%20.708.708l-3%203a.5.5%200%200%201-.708%200l-3-3a.5.5%200%200%201%200-.708z%22/%3E%3C/svg%3E')] bg-[position:right_0.8rem_center] bg-no-repeat pr-10 ${!value ? "text-[#A8B39F]" : ""}`}
        required={required}
      >
        <option value="" disabled>
          {placeholder}
        </option>
        {options.map((option) => {
          const [val, label2] = Array.isArray(option) ? option : [option, option];
          return (
            <option key={val} value={val}>
              {label2}
            </option>
          );
        })}
      </select>
    </Field>
  );
}

export function ImageField({
  label,
  name,
  value,
  onChange,
  error,
  hint,
  initialUrl,
  onRemove,
  circular = false,
}) {
  const inputRef = useRef(null);
  const hasValue = Boolean(value || initialUrl);

  function handleFile(file) {
    if (!file) return;
    onChange?.(file);
  }

  return (
    <Field label={label} error={error} hint={hint} htmlFor={name}>
      <div className="flex items-center gap-4">
        {hasValue ? (
          <div className={`relative shrink-0 overflow-hidden ${circular ? "h-16 w-16 rounded-full" : "h-16 w-20 rounded-lg"} bg-[#EEF1E9]`}>
            {value ? (
              <img src={URL.createObjectURL(value)} alt="Preview" className="h-full w-full object-cover" />
            ) : (
              initialUrl && <img src={initialUrl} alt="Current" className="h-full w-full object-cover" />
            )}
          </div>
        ) : (
          <span className={`flex shrink-0 items-center justify-center ${circular ? "h-16 w-16 rounded-full" : "h-16 w-20 rounded-lg"} border border-dashed border-[#DCE3D5] bg-[#F8F9F4] text-[#A8B39F]`}>
            <ImagePlus className="h-5 w-5" strokeWidth={1.5} />
          </span>
        )}
        <div className="flex flex-col gap-2">
          <input
            ref={inputRef}
            id={name}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={(e) => {
              handleFile(e.target.files?.[0]);
              e.target.value = "";
            }}
          />
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            className="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED]"
          >
            <ImagePlus className="h-4 w-4" strokeWidth={1.5} />
            {value ? "Change" : "Choose image"}
          </button>
          {hasValue && (
            <button
              type="button"
              onClick={() => {
                onChange?.(null);
                onRemove?.();
              }}
              className="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium text-[#B3453A] transition-colors hover:bg-[#C25B50]/10"
            >
              <Trash2 className="h-3.5 w-3.5" strokeWidth={1.5} />
              Remove
            </button>
          )}
        </div>
      </div>
    </Field>
  );
}