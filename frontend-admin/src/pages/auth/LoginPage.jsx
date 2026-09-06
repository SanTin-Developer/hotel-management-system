import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import { Eye, EyeOff, Mail, Lock, ArrowRight } from "lucide-react";
import { useLogin } from "@/features/auth/useLogin";
import { loginSchema } from "@/features/auth/loginSchema";

const LOGO_URL =
  "https://res.cloudinary.com/drercy9vt/image/upload/v1788588116/Gemini_Generated_Image_m6go6vm6go6vm6go-removebg-preview_guzd4n.png";

const SLIDES = [
  {
    src: "https://res.cloudinary.com/drercy9vt/image/upload/v1788587159/dad85246-4f85-439b-92f2-cc3eb84d7910_pgruf6.png",
    alt: "Tropical courtyard pool",
  },
  {
    src: "https://res.cloudinary.com/drercy9vt/image/upload/v1788587154/df49fbb5-bdda-41cf-9754-ecab74db3598_nrhlwk.png",
    alt: "Oceanfront resort pool",
  },
  {
    src: "https://res.cloudinary.com/drercy9vt/image/upload/v1788587131/6992e088-98be-4068-b462-ed61ce9019ce_gogs78.png",
    alt: "Suite bedroom with city view",
  },
];

const SLIDE_DURATION = 5000;

export default function LoginPage() {
  const [form, setForm] = useState({ email: "", password: "" });
  const [errors, setErrors] = useState({});
  const [showPassword, setShowPassword] = useState(false);
  const [slideIndex, setSlideIndex] = useState(0);
  const { mutate: doLogin, isPending } = useLogin();

  useEffect(() => {
    const timer = setInterval(() => {
      setSlideIndex((i) => (i + 1) % SLIDES.length);
    }, SLIDE_DURATION);
    return () => clearInterval(timer);
  }, []);

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  function handleSubmit(e) {
    e.preventDefault();

    const result = loginSchema.safeParse(form);
    if (!result.success) {
      const fieldErrors = {};
      result.error.issues.forEach((issue) => {
        fieldErrors[issue.path[0]] = issue.message;
      });
      setErrors(fieldErrors);
      return;
    }

    setErrors({});
    doLogin(result.data);
  }

  return (
    <div className="grid min-h-screen grid-cols-1 md:grid-cols-2 bg-[#F8F9F4]">
      <div className="relative hidden overflow-hidden bg-[#16261F] md:block">
        <AnimatePresence mode="sync">
          <motion.div
            key={slideIndex}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 1.1, ease: "easeInOut" }}
            className="absolute inset-0"
          >
            <img
              src={SLIDES[slideIndex].src}
              alt={SLIDES[slideIndex].alt}
              loading="lazy"
              className="h-full w-full object-cover"
            />
          </motion.div>
        </AnimatePresence>

        <div
          className="pointer-events-none absolute inset-0"
          style={{
            backgroundImage:
              "linear-gradient(180deg, rgba(22,38,31,0.55) 0%, rgba(22,38,31,0.05) 30%, rgba(22,38,31,0.05) 60%, rgba(22,38,31,0.75) 100%)",
          }}
        />

        <div className="relative z-10 flex h-full flex-col justify-between px-14 py-12">
          <div className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-md border border-[#7FA35C]/50 bg-[#16261F]/40 p-1.5 backdrop-blur-sm">
              <img
                src={LOGO_URL}
                alt="Kampuchea Otel"
                className="h-full w-full object-contain"
              />
            </span>
            <span
              className="text-2xl text-[#F2F5EC]"
              style={{ fontFamily: "'Fraunces', serif" }}
            >
              Kampuchea Otel
            </span>
          </div>

          <div className="max-w-sm">
            <p className="text-[15px] leading-relaxed text-[#F2F5EC]/90">
              Front desk operations, reservations, and housekeeping — in one
              place.
            </p>

            <div className="mt-6 flex items-center gap-2">
              {SLIDES.map((slide, i) => (
                <button
                  key={slide.src}
                  type="button"
                  onClick={() => setSlideIndex(i)}
                  aria-label={`Show slide ${i + 1}`}
                  aria-current={i === slideIndex}
                  className="h-1 rounded-full transition-all duration-300"
                  style={{
                    width: i === slideIndex ? "28px" : "10px",
                    backgroundColor:
                      i === slideIndex ? "#9DBE7C" : "rgba(242,245,236,0.35)",
                  }}
                />
              ))}
            </div>

            <p className="mt-6 text-xs text-[#AEC2A2]">
              Staff and administrator access only
            </p>
          </div>
        </div>
      </div>

      <div className="flex min-h-screen items-center justify-center px-6 py-12 sm:px-8">
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.45, ease: "easeOut" }}
          className="w-full max-w-[400px]"
        >
          <div className="mb-9 flex items-center gap-3 md:hidden">
            <span className="flex h-9 w-9 items-center justify-center rounded-md border border-[#7FA35C]/50 p-1.5">
              <img
                src={LOGO_URL}
                alt="Kampuchea Otel"
                className="h-full w-full object-contain"
              />
            </span>
            <span
              className="text-xl text-[#1E2B22]"
              style={{ fontFamily: "'Fraunces', serif" }}
            >
              Kampuchea Otel
            </span>
          </div>

          <h1
            className="text-[28px] leading-tight text-[#1E2B22]"
            style={{ fontFamily: "'Fraunces', serif" }}
          >
            Sign in
          </h1>
          <p className="mt-2 text-sm text-[#5E6B5A]">
            Enter your credentials to reach the console.
          </p>

          {/* fieldset disables every input/button inside it at once while isPending is true */}
          <fieldset disabled={isPending} className="mt-8">
            <form onSubmit={handleSubmit} noValidate className="space-y-5">
              <div>
                <label
                  htmlFor="email"
                  className="mb-1.5 block text-sm font-medium text-[#1E2B22]"
                >
                  Email address
                </label>
                <div className="relative">
                  <Mail
                    className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#A8B39F]"
                    strokeWidth={1.5}
                  />
                  <input
                    id="email"
                    type="email"
                    name="email"
                    autoComplete="email"
                    value={form.email}
                    onChange={handleChange}
                    aria-invalid={Boolean(errors.email)}
                    aria-describedby={errors.email ? "email-error" : undefined}
                    className={`h-[46px] w-full rounded-lg border bg-white pl-10 pr-3 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:ring-4 disabled:cursor-not-allowed disabled:bg-[#F3F4EF] disabled:opacity-70 ${
                      errors.email
                        ? "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10"
                        : "border-[#DCE3D5] focus:border-[#7FA35C] focus:ring-[#7FA35C]/15"
                    }`}
                    placeholder="admin@hotel.com"
                  />
                </div>
                {errors.email && (
                  <p id="email-error" className="mt-1.5 text-xs text-[#B3453A]">
                    {errors.email}
                  </p>
                )}
              </div>

              <div>
                <div className="mb-1.5 flex items-center justify-between">
                  <label
                    htmlFor="password"
                    className="block text-sm font-medium text-[#1E2B22]"
                  >
                    Password
                  </label>
                  <Link
                    to="/forgot-password"
                    className="text-xs text-[#4F7A3B] transition-colors duration-200 hover:text-[#7FA35C]"
                  >
                    Forgot password?
                  </Link>
                </div>
                <div className="relative">
                  <Lock
                    className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#A8B39F]"
                    strokeWidth={1.5}
                  />
                  <input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    name="password"
                    autoComplete="current-password"
                    value={form.password}
                    onChange={handleChange}
                    aria-invalid={Boolean(errors.password)}
                    aria-describedby={
                      errors.password ? "password-error" : undefined
                    }
                    className={`h-[46px] w-full rounded-lg border bg-white pl-10 pr-10 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:ring-4 disabled:cursor-not-allowed disabled:bg-[#F3F4EF] disabled:opacity-70 ${
                      errors.password
                        ? "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10"
                        : "border-[#DCE3D5] focus:border-[#7FA35C] focus:ring-[#7FA35C]/15"
                    }`}
                    placeholder="••••••••"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((v) => !v)}
                    aria-label={
                      showPassword ? "Hide password" : "Show password"
                    }
                    className="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#A8B39F] transition-colors duration-200 hover:text-[#5E6B5A] focus:outline-none focus-visible:text-[#5E6B5A] disabled:cursor-not-allowed"
                  >
                    {showPassword ? (
                      <EyeOff className="h-4 w-4" strokeWidth={1.5} />
                    ) : (
                      <Eye className="h-4 w-4" strokeWidth={1.5} />
                    )}
                  </button>
                </div>
                {errors.password && (
                  <p
                    id="password-error"
                    className="mt-1.5 text-xs text-[#B3453A]"
                  >
                    {errors.password}
                  </p>
                )}
              </div>

              <button
                type="submit"
                className="group flex h-[47px] w-full items-center justify-center gap-2 rounded-lg bg-[#16261F] text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)] focus:outline-none focus-visible:shadow-[0_0_0_3px_rgba(127,163,92,0.4)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-none"
              >
                {isPending ? "Signing in…" : "Sign in"}
                {!isPending && (
                  <ArrowRight
                    className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                    strokeWidth={1.5}
                  />
                )}
              </button>

              <p className="text-center text-xs text-[#7A8677]">
                Can't access your account?{" "}
                <a
                  href="mailto:admin@ledger-hotel.com"
                  className="font-medium text-[#4F7A3B] transition-colors duration-200 hover:text-[#7FA35C]"
                >
                  Contact your administrator.
                </a>
              </p>
            </form>
          </fieldset>
        </motion.div>
      </div>
    </div>
  );
}
