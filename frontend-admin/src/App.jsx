import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import LoginPage from "@/pages/auth/LoginPage";
import ForgotPasswordPage from "@/pages/auth/Forgotpasswordpage";
import ProtectedRoute from "@/routes/ProtectedRoute";
import RoleRoute from "@/routes/RoleRoute";
import MainLayout from "@/components/layout/MainLayout";
import DashboardPage from "@/pages/dashboard/DashboardPage";
import BookingsPage from "@/pages/bookings/BookingsPage";
import RoomsPage from "@/pages/rooms/RoomsPage";
import RoomTypesPage from "@/pages/rooms/RoomTypesPage";
import GuestsPage from "@/pages/guests/GuestsPage";
import PaymentsPage from "@/pages/payments/PaymentsPage";
import CouponsPage from "@/pages/coupons/CouponsPage";
import ReviewsPage from "@/pages/reviews/ReviewsPage";
import StaffPage from "@/pages/staff/StaffPage";
import ProfilePage from "@/pages/profile/ProfilePage";

const App = () => {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public routes */}
        <Route path="/login" element={<LoginPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />

        {/* Protected routes — everything inside requires login */}
        <Route
          element={
            <ProtectedRoute>
              <MainLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route path="/bookings" element={<BookingsPage />} />
          <Route path="/rooms" element={<RoomsPage />} />
          <Route path="/rooms/types" element={<RoomTypesPage />} />
          <Route path="/guests" element={<GuestsPage />} />
          <Route path="/payments" element={<PaymentsPage />} />
          <Route path="/coupons" element={<CouponsPage />} />
          <Route path="/reviews" element={<ReviewsPage />} />
          <Route
            path="/staff"
            element={
              <RoleRoute roles={["admin", "manager"]}>
                <StaffPage />
              </RoleRoute>
            }
          />
          <Route path="/profile" element={<ProfilePage />} />
        </Route>

        {/* Fallback: redirect unknown paths to dashboard (or login) */}
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
};

export default App;
