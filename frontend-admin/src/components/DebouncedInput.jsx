import { useEffect, useState } from "react";

export default function DebouncedInput({
  value,
  onChange,
  debounce = 300,
  ...props
}) {
  const [text, setText] = useState(value ?? "");
  const [prevValue, setPrevValue] = useState(value);

  if (prevValue !== value) {
    setPrevValue(value);
    setText(value ?? "");
  }

  useEffect(() => {
    if (text === value) {
      return;
    }

    const timer = setTimeout(() => onChange(text), debounce);
    return () => clearTimeout(timer);
  }, [text, debounce, onChange, value]);

  return <input {...props} value={text} onChange={(e) => setText(e.target.value)} />;
}