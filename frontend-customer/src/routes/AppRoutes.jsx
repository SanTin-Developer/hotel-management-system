import { Route, Routes } from "react-router-dom";
import { HomePage } from "@/pages/Home/HomePage";
import { RoomsPage } from "@/pages/Rooms/RoomsPage";
import { RoomDetailsPage } from "@/pages/Rooms/RoomDetailsPage";
import { BookingPage } from "@/pages/Booking/BookingPage";
import { PaymentPage } from "@/pages/Booking/PaymentPage";
import { BookingConfirmationPage } from "@/pages/Booking/BookingConfirmationPage";
import { OffersPage } from "@/pages/Offers/OffersPage";
import { AmenitiesPage } from "@/pages/Amenities/AmenitiesPage";
import { ReviewsPage } from "@/pages/Reviews/ReviewsPage";
import { AboutPage } from "@/pages/About/AboutPage";
import { ContactPage } from "@/pages/Contact/ContactPage";
import { PolicyPage } from "@/pages/Policy/PolicyPage";
import { LoginPage } from "@/pages/Auth/LoginPage";
import { RegisterPage } from "@/pages/Auth/RegisterPage";
import { ForgotPasswordPage } from "@/pages/Auth/ForgotPasswordPage";
import { ResetPasswordPage } from "@/pages/Auth/ResetPasswordPage";
import { ProfileLayout } from "@/pages/Profile/ProfileLayout";
import { ProfileOverviewPage } from "@/pages/Profile/ProfileOverviewPage";
import { MyBookingsPage } from "@/pages/Profile/MyBookingsPage";
import { BookingDetailsPage } from "@/pages/Profile/BookingDetailsPage";
import { SettingsPage } from "@/pages/Profile/SettingsPage";
import { NotFoundPage } from "@/pages/NotFound/NotFoundPage";
import { SiteLayout } from "@/routes/SiteLayout";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { ROUTES } from "@/constants/routes";
import { ProtectedRoute, GuestRoute } from "@/routes/guards";

export function AppRoutes() {
  return (  
    <Routes>
      <Route element={<SiteLayout />}>
        <Route path={ROUTES.home} element={<HomePage />} />
        <Route path={ROUTES.rooms} element={<RoomsPage />} />
        <Route path={`${ROUTES.rooms}/:slug`} element={<RoomDetailsPage />} />
        <Route
          path={ROUTES.booking}
          element={
            <ProtectedRoute>
              <BookingPage />
            </ProtectedRoute>
          }
        />
        <Route
          path={ROUTES.payment}
          element={
            <ProtectedRoute>
              <PaymentPage />
            </ProtectedRoute>
          }
        />
        <Route
          path={ROUTES.bookingConfirmation}
          element={
            <ProtectedRoute>
              <BookingConfirmationPage />
            </ProtectedRoute>
          }
        />
        <Route path={ROUTES.offers} element={<OffersPage />} />
        <Route path={ROUTES.amenities} element={<AmenitiesPage />} />
        <Route path={ROUTES.reviews} element={<ReviewsPage />} />
        <Route path={ROUTES.about} element={<AboutPage />} />
        <Route path={ROUTES.contact} element={<ContactPage />} />
        <Route path={ROUTES.policy} element={<PolicyPage />} />

        <Route
          path={ROUTES.profile}
          element={
            <ProtectedRoute>
              <ProfileLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<ProfileOverviewPage />} />
          <Route path="bookings" element={<MyBookingsPage />} />
          <Route path="bookings/:id" element={<BookingDetailsPage />} />
          <Route path="settings" element={<SettingsPage />} />
        </Route>

        <Route path={ROUTES.notFound} element={<NotFoundPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>

      <Route element={<AuthLayout />}>
        <Route
          path={ROUTES.login}
          element={
            <GuestRoute>
              <LoginPage />
            </GuestRoute>
          }
        />
        <Route
          path={ROUTES.register}
          element={
            <GuestRoute>
              <RegisterPage />
            </GuestRoute>
          }
        />
        <Route path={ROUTES.forgotPassword} element={<ForgotPasswordPage />} />
        <Route path={ROUTES.resetPassword} element={<ResetPasswordPage />} />
      </Route>
    </Routes>
  );
}