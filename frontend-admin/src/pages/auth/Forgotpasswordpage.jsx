import { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import {
  Eye,
  EyeOff,
  Mail,
  Lock,
  ArrowRight,
  ArrowLeft,
  CheckCircle2,
} from "lucide-react";
import apiClient from "@/lib/apiClient";

const LOGO_URL =
  "https://res.cloudinary.com/drercy9vt/image/upload/v1788588116/Gemini_Generated_Image_m6go6vm6go6vm6go-removebg-preview_guzd4n.png";

// Swap these for your own hotel photography — same three used elsewhere.
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
const RESEND_COOLDOWN = 60;

async function postJson(path, body) {
  try {
    const res = await apiClient.post(`/auth/password${path}`, body);
    return res.data;
  } catch (error) {
    const data = error.response?.data || {};
    const fieldError = data?.errors?.[Object.keys(data.errors || {})[0]]?.[0];
    throw new Error(
      fieldError || data?.message || "Something went wrong. Please try again.",
      { cause: error },
    );
  }
}

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

export default function ForgotPasswordPage() {
  const [slideIndex, setSlideIndex] = useState(0);
  const [step, setStep] = useState("email"); // "email" | "otp" | "done"

  // Step 1 state
  const [email, setEmail] = useState("");
  const [emailError, setEmailError] = useState("");
  const [requestLoading, setRequestLoading] = useState(false);
  const [requestNotice, setRequestNotice] = useState("");
  const [cooldown, setCooldown] = useState(0);

  // Step 2 state
  const [otp, setOtp] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});
  const [resetLoading, setResetLoading] = useState(false);
  const [resetError, setResetError] = useState("");

  const cooldownTimer = useRef(null);

  useEffect(() => {
    const timer = setInterval(() => {
      setSlideIndex((i) => (i + 1) % SLIDES.length);
    }, SLIDE_DURATION);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    if (cooldown <= 0) return;
    cooldownTimer.current = setInterval(() => {
      setCooldown((c) => (c <= 1 ? 0 : c - 1));
    }, 1000);
    return () => clearInterval(cooldownTimer.current);
  }, [cooldown]);

  async function requestCode(e) {
    e?.preventDefault();
    setRequestNotice("");

    if (!email.trim()) {
      setEmailError("Email is required.");
      return;
    }
    if (!isValidEmail(email) || email.length > 255) {
      setEmailError("Enter a valid email address.");
      return;
    }
    setEmailError("");
    setRequestLoading(true);

    try {
      const data = await postJson("/forgot", { email: email.trim() });
      setRequestNotice(
        data?.message || "If the email exists, a reset code has been sent.",
      );
      setCooldown(RESEND_COOLDOWN);
      setStep("otp");
    } catch (err) {
      setEmailError(err.message);
    } finally {
      setRequestLoading(false);
    }
  }

  async function resendCode() {
    if (cooldown > 0 || requestLoading) return;
    setResetError("");
    setRequestLoading(true);
    try {
      const data = await postJson("/forgot", { email: email.trim() });
      setRequestNotice(
        data?.message || "If the email exists, a reset code has been sent.",
      );
      setCooldown(RESEND_COOLDOWN);
    } catch (err) {
      setResetError(err.message);
    } finally {
      setRequestLoading(false);
    }
  }

  async function submitReset(e) {
    e.preventDefault();
    const errors = {};

    if (!/^\d{6}$/.test(otp)) {
      errors.otp = "Enter the 6-digit code from your email.";
    }
    if (password.length < 8) {
      errors.password = "Password must be at least 8 characters.";
    }
    if (password !== passwordConfirmation) {
      errors.passwordConfirmation = "Passwords do not match.";
    }

    setFieldErrors(errors);
    if (Object.keys(errors).length > 0) return;

    setResetError("");
    setResetLoading(true);
    try {
      await postJson("/reset", {
        email: email.trim(),
        otp,
        password,
        password_confirmation: passwordConfirmation,
      });
      setStep("done");
    } catch (err) {
      setResetError(err.message);
    } finally {
      setResetLoading(false);
    }
  }

  return (
    <div className="grid min-h-screen grid-cols-1 md:grid-cols-2 bg-[#F8F9F4]">
      {/* Photo panel */}
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

      {/* Form panel */}
      <div className="flex min-h-screen items-center justify-center px-6 py-12 sm:px-8">
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.45, ease: "easeOut" }}
          className="w-full max-w-[400px]"
        >
          {/* Mobile-only mark */}
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

          <AnimatePresence mode="wait">
            {step === "email" && (
              <motion.div
                key="email-step"
                initial={{ opacity: 0, x: 8 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -8 }}
                transition={{ duration: 0.25 }}
              >
                <p className="text-xs text-[#7A8677]">Step 1 of 2</p>
                <h1
                  className="mt-1 text-[28px] leading-tight text-[#1E2B22]"
                  style={{ fontFamily: "'Fraunces', serif" }}
                >
                  Reset your password
                </h1>
                <p className="mt-2 text-sm text-[#5E6B5A]">
                  Enter your account email and we'll send you a reset code.
                </p>

                <form
                  onSubmit={requestCode}
                  noValidate
                  className="mt-8 space-y-5"
                >
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
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        aria-invalid={Boolean(emailError)}
                        aria-describedby={
                          emailError ? "email-error" : undefined
                        }
                        className={`h-[46px] w-full rounded-lg border bg-white pl-10 pr-3 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:ring-4 ${
                          emailError
                            ? "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10"
                            : "border-[#DCE3D5] focus:border-[#7FA35C] focus:ring-[#7FA35C]/15"
                        }`}
                        placeholder="admin@hotel.com"
                      />
                    </div>
                    {emailError && (
                      <p
                        id="email-error"
                        className="mt-1.5 text-xs text-[#B3453A]"
                      >
                        {emailError}
                      </p>
                    )}
                  </div>

                  <button
                    type="submit"
                    disabled={requestLoading}
                    className="group flex h-[47px] w-full items-center justify-center gap-2 rounded-lg bg-[#16261F] text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)] focus:outline-none focus-visible:shadow-[0_0_0_3px_rgba(127,163,92,0.4)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-none"
                  >
                    {requestLoading ? "Sending code…" : "Send reset code"}
                    {!requestLoading && (
                      <ArrowRight
                        className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                        strokeWidth={1.5}
                      />
                    )}
                  </button>

                  <p className="text-center text-xs text-[#7A8677]">
                    Remembered it?{" "}
                    <Link
                      to="/login"
                      className="font-medium text-[#4F7A3B] transition-colors duration-200 hover:text-[#7FA35C]"
                    >
                      Back to sign in
                    </Link>
                  </p>
                </form>
              </motion.div>
            )}

            {step === "otp" && (
              <motion.div
                key="otp-step"
                initial={{ opacity: 0, x: 8 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -8 }}
                transition={{ duration: 0.25 }}
              >
                <button
                  type="button"
                  onClick={() => {
                    setStep("email");
                    setResetError("");
                    setFieldErrors({});
                  }}
                  className="mb-4 flex items-center gap-1.5 text-xs text-[#5E6B5A] transition-colors duration-200 hover:text-[#1E2B22]"
                >
                  <ArrowLeft className="h-3.5 w-3.5" strokeWidth={1.5} />
                  Use a different email
                </button>

                <p className="text-xs text-[#7A8677]">Step 2 of 2</p>
                <h1
                  className="mt-1 text-[28px] leading-tight text-[#1E2B22]"
                  style={{ fontFamily: "'Fraunces', serif" }}
                >
                  Enter your code
                </h1>
                <p className="mt-2 text-sm text-[#5E6B5A]">
                  {requestNotice ||
                    "If the email exists, a reset code has been sent."}{" "}
                  Sent to <span className="text-[#1E2B22]">{email}</span>.
                </p>

                <form
                  onSubmit={submitReset}
                  noValidate
                  className="mt-8 space-y-5"
                >
                  <div>
                    <label
                      htmlFor="otp"
                      className="mb-1.5 block text-sm font-medium text-[#1E2B22]"
                    >
                      6-digit code
                    </label>
                    <input
                      id="otp"
                      type="text"
                      inputMode="numeric"
                      autoComplete="one-time-code"
                      maxLength={6}
                      value={otp}
                      onChange={(e) =>
                        setOtp(e.target.value.replace(/\D/g, "").slice(0, 6))
                      }
                      aria-invalid={Boolean(fieldErrors.otp)}
                      aria-describedby={
                        fieldErrors.otp ? "otp-error" : undefined
                      }
                      className={`h-[46px] w-full rounded-lg border bg-white px-3 text-center text-lg tracking-[0.5em] text-[#1E2B22] outline-none transition-colors duration-200 placeholder:tracking-normal placeholder:text-[#A8B39F] focus:ring-4 ${
                        fieldErrors.otp
                          ? "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10"
                          : "border-[#DCE3D5] focus:border-[#7FA35C] focus:ring-[#7FA35C]/15"
                      }`}
                      placeholder="000000"
                    />
                    {fieldErrors.otp && (
                      <p
                        id="otp-error"
                        className="mt-1.5 text-xs text-[#B3453A]"
                      >
                        {fieldErrors.otp}
                      </p>
                    )}
                    <div className="mt-2 flex items-center justify-between">
                      <span className="text-xs text-[#7A8677]">
                        Code expires in 10 minutes.
                      </span>
                      <button
                        type="button"
                        onClick={resendCode}
                        disabled={cooldown > 0 || requestLoading}
                        className="text-xs font-medium text-[#4F7A3B] transition-colors duration-200 hover:text-[#7FA35C] disabled:cursor-not-allowed disabled:text-[#A8B39F] disabled:hover:text-[#A8B39F]"
                      >
                        {cooldown > 0
                          ? `Resend in ${cooldown}s`
                          : "Resend code"}
                      </button>
                    </div>
                  </div>

                  <div>
                    <label
                      htmlFor="password"
                      className="mb-1.5 block text-sm font-medium text-[#1E2B22]"
                    >
                      New password
                    </label>
                    <div className="relative">
                      <Lock
                        className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#A8B39F]"
                        strokeWidth={1.5}
                      />
                      <input
                        id="password"
                        type={showPassword ? "text" : "password"}
                        name="password"
                        autoComplete="new-password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        aria-invalid={Boolean(fieldErrors.password)}
                        aria-describedby={
                          fieldErrors.password ? "password-error" : undefined
                        }
                        className={`h-[46px] w-full rounded-lg border bg-white pl-10 pr-10 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:ring-4 ${
                          fieldErrors.password
                            ? "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10"
                            : "border-[#DCE3D5] focus:border-[#7FA35C] focus:ring-[#7FA35C]/15"
                        }`}
                        placeholder="At least 8 characters"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword((v) => !v)}
                        aria-label={
                          showPassword ? "Hide password" : "Show password"
                        }
                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#A8B39F] transition-colors duration-200 hover:text-[#5E6B5A]"
                      >
                        {showPassword ? (
                          <EyeOff className="h-4 w-4" strokeWidth={1.5} />
                        ) : (
                          <Eye className="h-4 w-4" strokeWidth={1.5} />
                        )}
                      </button>
                    </div>
                    {fieldErrors.password && (
                      <p className="mt-1.5 text-xs text-[#B3453A]">
                        {fieldErrors.password}
                      </p>
                    )}
                  </div>

                  <div>
                    <label
                      htmlFor="passwordConfirmation"
                      className="mb-1.5 block text-sm font-medium text-[#1E2B22]"
                    >
                      Confirm new password
                    </label>
                    <div className="relative">
                      <Lock
                        className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#A8B39F]"
                        strokeWidth={1.5}
                      />
                      <input
                        id="passwordConfirmation"
                        type={showConfirm ? "text" : "password"}
                        name="passwordConfirmation"
                        autoComplete="new-password"
                        value={passwordConfirmation}
                        onChange={(e) =>
                          setPasswordConfirmation(e.target.value)
                        }
                        aria-invalid={Boolean(fieldErrors.passwordConfirmation)}
                        aria-describedby={
                          fieldErrors.passwordConfirmation
                            ? "password-confirmation-error"
                            : undefined
                        }
                        className={`h-[46px] w-full rounded-lg border bg-white pl-10 pr-10 text-sm text-[#1E2B22] outline-none transition-colors duration-200 placeholder:text-[#A8B39F] focus:ring-4 ${
                          fieldErrors.passwordConfirmation
                            ? "border-[#C25B50] focus:border-[#C25B50] focus:ring-[#C25B50]/10"
                            : "border-[#DCE3D5] focus:border-[#7FA35C] focus:ring-[#7FA35C]/15"
                        }`}
                        placeholder="Re-enter new password"
                      />
                      <button
                        type="button"
                        onClick={() => setShowConfirm((v) => !v)}
                        aria-label={
                          showConfirm ? "Hide password" : "Show password"
                        }
                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#A8B39F] transition-colors duration-200 hover:text-[#5E6B5A]"
                      >
                        {showConfirm ? (
                          <EyeOff className="h-4 w-4" strokeWidth={1.5} />
                        ) : (
                          <Eye className="h-4 w-4" strokeWidth={1.5} />
                        )}
                      </button>
                    </div>
                    {fieldErrors.passwordConfirmation && (
                      <p
                        id="password-confirmation-error"
                        className="mt-1.5 text-xs text-[#B3453A]"
                      >
                        {fieldErrors.passwordConfirmation}
                      </p>
                    )}
                  </div>

                  {resetError && (
                    <p className="text-xs text-[#B3453A]">{resetError}</p>
                  )}

                  <button
                    type="submit"
                    disabled={resetLoading}
                    className="group flex h-[47px] w-full items-center justify-center gap-2 rounded-lg bg-[#16261F] text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)] focus:outline-none focus-visible:shadow-[0_0_0_3px_rgba(127,163,92,0.4)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-none"
                  >
                    {resetLoading ? "Resetting…" : "Reset password"}
                    {!resetLoading && (
                      <ArrowRight
                        className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                        strokeWidth={1.5}
                      />
                    )}
                  </button>
                </form>
              </motion.div>
            )}

            {step === "done" && (
              <motion.div
                key="done-step"
                initial={{ opacity: 0, y: 8 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3 }}
                className="text-center"
              >
                <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#7FA35C]/15 text-[#4F7A3B]">
                  <CheckCircle2 className="h-6 w-6" strokeWidth={1.5} />
                </span>
                <h1
                  className="mt-5 text-[28px] leading-tight text-[#1E2B22]"
                  style={{ fontFamily: "'Fraunces', serif" }}
                >
                  Password reset
                </h1>
                <p className="mt-2 text-sm text-[#5E6B5A]">
                  Your password has been reset successfully. Sign in with your
                  new password.
                </p>

                <Link
                  to="/login"
                  className="group mt-8 flex h-[47px] w-full items-center justify-center gap-2 rounded-lg bg-[#16261F] text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)]"
                >
                  Continue to sign in
                  <ArrowRight
                    className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                    strokeWidth={1.5}
                  />
                </Link>
              </motion.div>
            )}
          </AnimatePresence>
        </motion.div>
      </div>
    </div>
  );
}
