export const ROUTES = {
  home: "/",
  rooms: "/rooms",
  roomDetails: (slug) => `/rooms/${slug}`,
  booking: "/booking",
  bookingConfirmation: "/booking/confirmation",
  payment: "/booking/payment",
  offers: "/offers",
  amenities: "/amenities",
  reviews: "/reviews",
  about: "/about",
  contact: "/contact",
  policy: "/policy",
  login: "/login",
  register: "/register",
  forgotPassword: "/forgot-password",
  resetPassword: "/reset-password",
  profile: "/profile",
  profileBookings: "/profile/bookings",
  bookingDetails: (id) => `/profile/bookings/${id}`,
  profileSettings: "/profile/settings",
  notFound: "/404",
};

export const HOTEL_NAME = "Kumpuchea Otel";
export const HOTEL_TAGLINE = "Your Escape Awaits";
export const HOTEL_ADDRESS = "219B Preah Sisowath Quay, Phnom Penh 12204";
export const HOTEL_PHONE = "+855 23 000 000";
export const HOTEL_EMAIL = "stay@kumpucheaotel.com";
export const HOTEL_HOURS = "Front desk open 24/7";
export const HOTEL_LOGO_URL =
  "https://res.cloudinary.com/drercy9vt/image/upload/v1788588116/Gemini_Generated_Image_m6go6vm6go6vm6go-removebg-preview_guzd4n.png";
export const HOTEL_MAP_EMBED =
  "https://www.google.com/maps?q=219B%20Preah%20Sisowath%20Quay%2C%20Phnom%20Penh%2012204%2C%20Cambodia&z=17&output=embed";

export const HOTEL_SOCIAL = {
  facebook:
    "https://www.facebook.com/profile.php?id=61585872256716&__cft__[0]=AZiyaCB-uZltPJUpSrm8hJ72UfEQ5oQLsGX1PuCkygI2zJVYUEoKL2o5Qb6D6IqE9B17GdcO4Q2CGaENPnlsccu-N9-VlKygAjgJPDGQuYKMO-vXPwN_fPAOepQ04kk2UdJDMDtVI_zNhXr2EZ-2cRYmKbdRil-TeHA1v3mW1keVxWo15f3ZKZqk3dO7dVU&__tn__=-UC%2CP-R",
  instagram:
    "https://www.instagram.com/cambodiahotel.com1?igsh=MjQzNDlocXFyMWp4&utm_source=qr",

  tiktok:
    "https://www.tiktok.com/@cambodiahotel.com?is_from_webapp=1&sender_device=pc",
};

export const COUNTRIES = [
  "Cambodia",
  "China",
  "South Korea",
  "Japan",
  "Vietnam",
  "Thailand",
  "Singapore",
  "Malaysia",
  "Philippines",
  "Indonesia",
  "India",
  "Australia",
  "United States",
  "United Kingdom",
  "Canada",
  "France",
  "Germany",
  "Spain",
  "Portugal",
  "Italy",
  "Netherlands",
  "Switzerland",
  "Other",
];
