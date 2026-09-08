import { useEffect, useRef, useState } from "react";
import { motion } from "framer-motion";

export function SlideshowBackground({ images, interval = 7000, duration = 1700 }) {
  const [current, setCurrent] = useState(0);
  const [previous, setPrevious] = useState(null);
  const indexRef = useRef(0);

  useEffect(() => {
    const preloaded = images.map((src) => {
      const image = new Image();
      image.decoding = "async";
      image.src = src;
      return image;
    });

    const timer = setInterval(() => {
      setPrevious(indexRef.current);
      indexRef.current = (indexRef.current + 1) % images.length;
      setCurrent(indexRef.current);
    }, interval);

    return () => {
      clearInterval(timer);
      preloaded.forEach((image) => {
        image.src = "";
      });
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="absolute inset-0 overflow-hidden" aria-hidden="true">
      {previous !== null && (
        <motion.img
          key={`slide-prev-${previous}`}
          src={images[previous]}
          alt=""
          className="absolute inset-0 h-full w-full object-cover"
          initial={{ opacity: 1 }}
          animate={{ opacity: 0 }}
          transition={{ duration, ease: "easeInOut" }}
          onAnimationComplete={() => setPrevious(null)}
        />
      )}
      <motion.img
        key={`slide-current-${current}`}
        src={images[current]}
        alt=""
        className="absolute inset-0 h-full w-full object-cover"
        initial={{ opacity: previous === null ? 1 : 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration, ease: "easeInOut" }}
      />
    </div>
  );
}