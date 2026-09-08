import { useLocation } from "react-router-dom";
import { useEffect } from "react";
import { Toaster } from "sonner";
import { AppRoutes } from "@/routes/AppRoutes";
import ScrollToTopButton from "@/components/common/ScrollToTopButton";

function ScrollToTop() {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo({ top: 0 });
  }, [pathname]);
  return null;
}

export default function App() {
  return (
    <>
      <ScrollToTop />
      <AppRoutes />
      <ScrollToTopButton />
      <Toaster position="top-center" richColors closeButton />
    </>
  );
}