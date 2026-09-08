import { useEffect, useRef } from "react";
import "finisher-header";

const FINISHER_PALETTE = ["#C8A24B", "#0E5838", "#5AA783", "#DEBC64"];

export function FinisherShapes({ className = "" }) {
  const hostRef = useRef(null);

  useEffect(() => {
    const host = hostRef.current;

    if (!host || document.getElementById("finisher-canvas")) return undefined;

    new window.FinisherHeader({
      count: 6,
      size: {
        min: 200,
        max: 500,
        pulse: 0.5,
      },
      speed: {
        x: {
          min: 0.3,
          max: 1.1,
        },
        y: {
          min: 0,
          max: 0,
        },
      },
      colors: {
        background: "#072a1b",
        particles: FINISHER_PALETTE,
      },
      blending: "overlay",
      opacity: {
        center: 0.6,
        edge: 0.2,
      },
      skew: 0,
      shapes: ["t", "s"],
    });

    return () => {
      host.querySelector("canvas#finisher-canvas")?.remove();
    };
  }, []);

  return (
    <div
      ref={hostRef}
      className={`finisher-header pointer-events-none absolute inset-0 ${className}`}
      aria-hidden="true"
    />
  );
}