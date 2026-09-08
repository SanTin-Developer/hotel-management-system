# 11 · User Guide

This guide provides end-to-end operational instructions for both **Guests / Customers** using the public booking website and **Hotel Staff & Administrators** operating the back-office console.

---

## 11.1 Customer User Guide

### Step 1: Create a Customer Account (Register)
1. Open the Customer Website (`http://localhost:5173`).
2. Click **Sign In** in the top navigation bar, then select **Don't have an account? Register**.
3. Complete the registration form:
   - Enter your **Full Name**, **Email Address**, and **Phone Number**.
   - Select your **Country of Residence** (this automatically sets your deposit rate: 20% for Cambodia, 30% for international guests).
   - Enter your **Password** and confirm it.
   - *(Optional)* Select an ID Document type (Passport, National ID) and enter your ID number.
   - *(Optional)* Upload a profile photo.
4. Click **Create Account**.
5. A modal will prompt you to enter the **6-digit verification code** sent to your email address.
6. Enter the 6 digits. Upon verification, your account is activated and you are automatically logged in.

---

### Step 2: Log In to Your Account
1. Click **Sign In** from the top navbar.
2. In the **Email or Phone** field, enter either your registered email address or your phone number.
3. Enter your **Password**.
4. Click **Log In**. Your name and avatar will appear in the top right corner.

> [!TIP]
> If you forget your password, click **Forgot Password?**, enter your email, and follow the OTP reset prompt to set a new password.

---

### Step 3: Browse and Search Rooms
1. On the **Home Page**, locate the floating search widget.
2. Select your **Check-in Date** and **Check-out Date**.
3. Specify the number of **Adults** and **Children**.
4. Click **Search Rooms** to navigate to the **Rooms Catalog**.
5. Use the filter bar to filter rooms by **Room Type**, **Floor**, or **Bed Configuration**.
6. Switch the interface language at any time between **English** and **Khmer (ភាសាខ្មែរ)** via the language toggle in the navbar.

---

### Step 4: Inspect Room Details
1. Click on any room card or click **View Details**.
2. On the **Room Details Page**, you can:
   - View high-resolution photo galleries.
   - Review room dimensions, bed types, and maximum capacity.
   - Check all included amenities (Free Wi-Fi, Air Conditioning, Breakfast, etc.).
   - Read verified guest reviews and star ratings.
3. If the room fits your itinerary, click **Book Now**.

---

### Step 5: Complete Booking Wizard
1. Review your selected dates and room line items in the booking summary.
2. Add additional rooms if booking for a family or group.
3. In the **Special Requests** box, enter any preferences (e.g. *"Quiet room on a high floor, late check-in at 8 PM"*).
4. If you have a promotional coupon code (such as `WELCOME10`), enter it in the **Promo Code** input and click **Apply**. The discount will immediately reflect in your total.
5. Confirm your contact information and click **Proceed to Payment**.

---

### Step 6: Submit Deposit Payment
1. The **Payment Page** displays your reservation total, your required **Deposit Amount** (20% or 30%), and the remaining balance due upon arrival.
2. Choose your preferred payment method:
   - **ABA KHQR**: Scan the displayed KHQR code using your ABA Mobile app.
   - **Wing / ACLEDA**: Scan or transfer to the hotel account numbers shown.
   - **Credit / Debit Card**: Submit card transfer reference.
   - **Cash on Arrival**: Request cash deposit handling at check-in.
3. Enter the **Transaction Reference ID** from your banking app receipt.
4. Click **Submit Deposit Proof**.

---

### Step 7: Confirmation & Download PDF Invoice
1. Upon submission, the **Booking Confirmation Page** displays your unique Booking Reference Code (e.g., `BK-20260908-J8F3KQ`).
2. Review your stay breakdown, check-in instructions, and hotel contact information.
3. Click **Download PDF Invoice**.
4. The system renders an official printable PDF receipt complete with the hotel logo, booking reference, QR verification code, and financial balance breakdown.

---

### Step 8: View Booking History & Status
1. Click on your profile avatar in the header and select **My Bookings**.
2. Your bookings are categorized by status:
   - `Pending`: Reservation received, awaiting staff confirmation or deposit verification.
   - `Confirmed`: Deposit confirmed; room reserved.
   - `In House`: You have checked in at the front desk.
   - `Completed`: Stay finished.
   - `Cancellation Requested`: Cancellation review in progress.
   - `Cancelled`: Booking cancelled.
3. Click **View Details** on any booking card to review the full itinerary.

---

### Step 9: Request Cancellation & Write Reviews
- **Request Cancellation**: If your travel plans change, open the booking details page and click **Request Cancellation**. If your request is made more than 48 hours prior to check-in, the system submits it for review, and approved deposits will be refunded.
- **Submit Stay Review**: After completing a stay, visit the **Reviews** section or open the completed booking to submit a 1 to 5 star rating and comment.

---

## 11.2 Staff & Admin User Guide

### Step 1: Staff Authentication
1. Open the Admin Console (`http://localhost:5174`).
2. Enter your employee email address and password.
3. Click **Sign In to Console**. The sidebar displays options matching your assigned role (`admin`, `manager`, or `staff`).

---

### Step 2: Operating the Executive Dashboard
1. Click **Dashboard** in the sidebar.
2. Monitor key real-time metrics:
   - **Total Rooms**, **Occupied Rooms**, **Available Rooms**.
   - **Today's Bookings**, **Today's Revenue**, **Total Revenue**.
3. Switch the revenue chart period between **Today**, **This Week**, **This Month**, and **This Year** to analyze sales trends.
4. Check the **Recent Arrivals** table for incoming guests scheduled for check-in today.

---

### Step 3: Managing Room Inventory & Status
1. Navigate to **Rooms** in the sidebar.
2. To create a new room: click **Add Room**, select the Room Type, enter the Room Number, select the Floor, and assign amenities.
3. To update physical room status:
   - Click the **Status** badge on any room row.
   - Choose from: `Available`, `Occupied`, `Cleaning`, `Maintenance`, or `Out of Service`.
   - Add a staff note (e.g., *"Deep cleaning completed by housekeeping"*).
   - Click **Save**.

---

### Step 4: Managing Room Types & Amenities
1. Click **Room Types** in the sidebar to configure room categories, base prices per night, guest capacities, and dimensions.
2. Click **Amenities** to manage amenities. Provide both English and Khmer titles (`name_kh`) and descriptions to support the customer website localization.

---

### Step 5: Managing Guest Records
1. Navigate to **Guests** in the sidebar.
2. Use the search bar to locate guests by name, email, or phone number.
3. Inspect guest nationality, national ID or passport numbers, and stay history.
4. Click **Add Guest** to manually register walk-in guests who do not have an online account.

---

### Step 6: Reservation Management & Walk-Ins
1. Navigate to **Bookings** in the sidebar.
2. Search reservations by Booking Code (e.g., `BK-20260908-...`) or filter by status (`Pending`, `Confirmed`, `In House`, `Completed`, `Cancelled`).
3. To record a phone or walk-in reservation:
   - Click **Create Booking**.
   - Select an existing guest or create a quick guest profile.
   - Select check-in/out dates, guest counts, and assign available rooms.
   - Select the source (`walk_in` or `phone`).
   - Click **Create Reservation**.

---

### Step 7: Executing Guest Check-In
1. On the day of guest arrival, locate the booking with status `Confirmed`.
2. Click the booking row to open the **Booking Detail Drawer**.
3. Verify the guest's physical passport or national ID card against the record.
4. Click **Check In**.
5. The system atomically transitions the booking status to `In House` and logs the event to the audit history.

---

### Step 8: Recording Payments & Balance Settlement
1. Open the booking details drawer and scroll to the **Payments** section.
2. Review the **Total Amount**, **Paid Amount**, and **Remaining Balance**.
3. Click **Record Payment**.
4. Enter the amount to collect (cannot exceed the remaining balance).
5. Select the payment method:
   - If `Cash`: Payment is instantly marked as `Paid`.
   - If `Bank Transfer`, `Card`, `ABA`, `Wing`, or `ACLEDA`: Enter or verify the transaction ID.
6. Click **Save Payment**. The remaining balance updates automatically.

---

### Step 9: Executing Guest Check-Out & Completion
1. When the guest departs, verify that the remaining balance is `$0.00`.
2. Settle any unpaid balance using the **Record Payment** modal if necessary.
3. In the booking drawer, click **Complete Stay**.
4. The booking status transitions to `Completed`.
5. Navigate to **Rooms** and update the departed room's status to `Cleaning` for housekeeping.

---

### Step 10: Handling Cancellation Requests & Refunds
1. Filter the Bookings table by status `Cancellation Requested`.
2. Click on the reservation to inspect the request:
   - The system displays the cancellation note and whether it satisfies the 48-hour refund policy.
3. **To Approve**: Click **Approve Cancellation**. The status updates to `Cancelled`, and any settled deposit is automatically marked as `Refunded`.
4. **To Reject**: Click **Reject Cancellation**. The booking is restored to its previous state (`Confirmed` or `Pending`).

---

### Step 11: Managing Staff Accounts & Roles (Managers & Admins)
1. Navigate to **Staff** in the sidebar (accessible only to `admin` and `manager` roles).
2. Click **Add Staff Member**.
3. Fill in the employee's name, email, phone, employee ID, position, and hire date.
4. Select the system role:
   - `Staff`: Front-desk access (view bookings, check-in, record payments).
   - `Manager`: Operations management (manage rooms, coupons, reviews, staff).
   - `Admin`: Full system authority.
5. Upload an employee badge photo and click **Create Staff**.

---

### Step 12: Moderating Reviews & Exporting Reports
- **Review Moderation**: Open **Reviews**, inspect customer feedback, and click **Approve** to publish or **Reject** to hide.
- **CSV Data Exports**: Managers and Admins can export data streams directly from the API:
  - Bookings report: `GET /api/v1/exports/bookings`
  - Guests report: `GET /api/v1/exports/guests`
  - Payments report: `GET /api/v1/exports/payments`
