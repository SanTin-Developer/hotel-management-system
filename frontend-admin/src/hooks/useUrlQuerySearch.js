import { useState, useEffect } from "react";
import { useSearchParams } from "react-router-dom";

export default function useUrlQuerySearch(delay = 300) {
  const [searchParams, setSearchParams] = useSearchParams();
  const urlQ = searchParams.get("q") ?? "";
  const [search, setSearch] = useState(urlQ);
  const [prevUrlQ, setPrevUrlQ] = useState(urlQ);

  if (prevUrlQ !== urlQ) {
    setPrevUrlQ(urlQ);
    setSearch(urlQ);
  }

  useEffect(() => {
    const timer = setTimeout(() => {
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev);
          const value = search.trim();
          if (value) next.set("q", value);
          else next.delete("q");
          return next;
        },
        { replace: true }
      );
    }, delay);

    return () => clearTimeout(timer);
  }, [search, delay, setSearchParams]);

  return [search, setSearch];
}