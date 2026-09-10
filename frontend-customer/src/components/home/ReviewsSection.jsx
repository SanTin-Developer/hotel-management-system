import { useState } from "react";
import { Link } from "react-router-dom";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Star, Quote, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { SectionHeading } from "@/components/common/SectionHeading";
import { Loading } from "@/components/common/Loading";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useI18n } from "@/i18n";
import { useAuth } from "@/hooks/useAuth";
import {
  fetchApprovedReviews,
  createReview,
} from "@/services/api/reviews";
import { fetchMyBookings } from "@/services/api/bookings";
import { firstError, extractApiMessage } from "@/utils/validation";
import { formatDate, formatDateRange } from "@/utils/formatDate";
import { IMAGES } from "@/lib/images";
import { ROUTES } from "@/constants/routes";

function Stars({ rating, className = "size-4" }) {
  const { t } = useI18n();
  return (
    <div className="flex gap-0.5" aria-label={t("reviews.starsOutOf", { rating })}>
      {[1, 2, 3, 4, 5].map((value) => (
        <Star
          key={value}
          className={`${className} ${
            value <= rating
              ? "fill-gold-400 text-gold-400"
              : "fill-neutral-200 text-neutral-200"
          }`}
        />
      ))}
    </div>
  );
}

const SAMPLE_REVIEWS = [
  {
    id: "sample-1",
    rating: 5,
    comment:
      "Amazing stay! The rooftop pool at sunset is unforgettable and the staff remembered our names by the second morning. We will absolutely be back.",
    created_at: "2025-12-18T00:00:00.000Z",
    guest: { full_name: "Sovannara Chen", email: "sovannara.chen@gmail.com" },
    _avatar: IMAGES.reviewsAvatars.a,
  },
  {
    id: "sample-2",
    rating: 5,
    comment:
      "The suites are beautiful and the breakfast buffet rivals anything in town. Booking online was effortless and the free cancellation gave us peace of mind.",
    created_at: "2025-11-05T00:00:00.000Z",
    guest: { full_name: "Maria Gonzalez", email: "maria.gonzalez@gmail.com" },
    _avatar: IMAGES.reviewsAvatars.b,
  },
  {
    id: "sample-3",
    rating: 4,
    comment:
      "Great location right by the river, spotless rooms and a very helpful front desk team. Perfect base for exploring Phnom Penh.",
    created_at: "2025-10-12T00:00:00.000Z",
    guest: { full_name: "James Okafor", email: "james.okafor@gmail.com" },
    _avatar: IMAGES.reviewsAvatars.c,
  },
];

function ReviewForm() {
  const { t } = useI18n();
  const { isAuthenticated, guest } = useAuth();
  const queryClient = useQueryClient();
  const [bookingId, setBookingId] = useState("");
  const [rating, setRating] = useState(0);
  const [hover, setHover] = useState(0);
  const [comment, setComment] = useState("");
  const [error, setError] = useState(null);
  const [sending, setSending] = useState(false);

  const { data: stays = [], isPending: staysPending } = useQuery({
    queryKey: ["my-bookings", "review", guest?.id],
    queryFn: async () => {
      const { items } = await fetchMyBookings({
        guestId: guest.id,
        params: { per_page: 50, sort: "newest" },
      });
      return items;
    },
    enabled: Boolean(isAuthenticated && guest?.id),
  });

  if (!isAuthenticated) {
    return (
      <div className="mt-16 rounded-2xl border border-dashed border-brand-300 bg-brand-50/60 p-8 text-center">
        <Star className="mx-auto size-8 text-gold-400" />
        <p className="mt-3 text-lg font-semibold text-brand-950">
          {t("reviews.leaveTitle")}
        </p>
        <p className="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
          {t("reviews.leaveSubtitle")}
        </p>
        <Link to={ROUTES.login}>
          <Button className="mt-5" variant="dark" size="pill">
            {t("reviews.signIn")}
          </Button>
        </Link>
      </div>
    );
  }

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!bookingId || rating < 1) {
      setError(t("reviews.required"));
      return;
    }
    setSending(true);
    setError(null);
    try {
      await createReview({
        booking_id: Number(bookingId),
        guest_id: guest.id,
        rating,
        comment: comment.trim() || null,
      });
      toast.success(t("reviews.submitted"));
      setBookingId("");
      setRating(0);
      setComment("");
      queryClient.invalidateQueries({ queryKey: ["home-reviews"] });
    } catch (err) {
      setError(firstError(err) ?? extractApiMessage(err));
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="mt-16 overflow-hidden rounded-3xl border border-border bg-cream/60 p-6 sm:p-8">
      <h3 className="text-xl font-semibold tracking-tight text-brand-950">
        {t("reviews.leaveTitle")}
      </h3>
      <p className="mt-1 text-sm text-muted-foreground">
        {t("reviews.leaveSubtitle")}
      </p>

      <form onSubmit={handleSubmit} className="mt-6 space-y-5">
        {error && (
          <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        )}

        <div className="space-y-1.5">
          <Label>{t("reviews.chooseStay")}</Label>
          {staysPending ? (
            <p className="text-sm text-muted-foreground">
              {t("common.loading")}
            </p>
          ) : stays.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("reviews.noStays")}
            </p>
          ) : (
            <Select value={bookingId} onValueChange={setBookingId}>
              <SelectTrigger className="h-11">
                <SelectValue placeholder={t("reviews.chooseStay")} />
              </SelectTrigger>
              <SelectContent>
                {stays.map((booking) => (
                  <SelectItem key={booking.id} value={String(booking.id)}>
                    {booking.booking_code} ·{" "}
                    {formatDateRange(booking.check_in, booking.check_out)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        </div>

        <div className="space-y-1.5">
          <Label>{t("reviews.rateHint")}</Label>
          <div
            className="flex gap-1"
            onMouseLeave={() => setHover(0)}
            role="radiogroup"
            aria-label={t("reviews.rateHint")}
          >
            {[1, 2, 3, 4, 5].map((value) => (
              <button
                key={value}
                type="button"
                onClick={() => setRating(value)}
                onMouseEnter={() => setHover(value)}
                aria-label={t("reviews.starAria", { value })}
                className="p-0.5 transition-transform hover:scale-110"
              >
                <Star
                  className={`size-7 ${
                    value <= (hover || rating)
                      ? "fill-gold-400 text-gold-400"
                      : "fill-neutral-200 text-neutral-200"
                  }`}
                />
              </button>
            ))}
          </div>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="review-comment">{t("reviews.comment")}</Label>
          <Textarea
            id="review-comment"
            rows={4}
            maxLength={5000}
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            placeholder={t("reviews.commentPlaceholder")}
            className="h-auto bg-white"
          />
        </div>

        <Button
          type="submit"
          size="pill-lg"
          variant="gold"
          className="w-full sm:w-auto"
          disabled={sending}
        >
          {sending && <Loader2 className="size-4 animate-spin" />}
          {t("reviews.submit")}
        </Button>
      </form>
    </div>
  );
}

export function ReviewsSection({ limit = 3 }) {
  const { t, isKh } = useI18n();

  const { data: reviews = [], isPending } = useQuery({
    queryKey: ["home-reviews"],
    queryFn: fetchApprovedReviews,
    staleTime: 10 * 60_000,
  });

  const display = reviews.length > 0 ? reviews : SAMPLE_REVIEWS;
  const visible = display.slice(0, limit);

  return (
    <section className="bg-white py-20 sm:py-24">
      <div className="mx-auto max-w-7xl px-6">
        <SectionHeading
          eyebrow={t("reviews.eyebrow")}
          title={t("reviews.title")}
          subtitle={t("reviews.subtitle")}
        />

        {isPending ? (
          <Loading label={t("common.loading")} />
        ) : (
          <div className="mt-14 grid gap-6 md:grid-cols-3">
            {visible.map((review) => (
              <figure
                key={review.id}
                className="flex flex-col rounded-2xl border border-border bg-cream/60 p-7 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-950/5"
              >
                <Quote className="size-7 text-gold-400" />
                <blockquote className="mt-4 flex-1 text-sm leading-relaxed text-foreground/85">
                  “{isKh && review.comment_kh ? review.comment_kh : review.comment}”
                </blockquote>
                <figcaption className="mt-6 flex items-center gap-3 border-t border-border pt-5">
                  <img
                    src={review._avatar ?? null}
                    onError={(event) => {
                      event.currentTarget.style.visibility = "hidden";
                    }}
                    alt=""
                    className="hidden size-10 rounded-full object-cover"
                  />
                  <span className="grid size-10 shrink-0 place-items-center rounded-full bg-brand-900 text-sm font-semibold text-white">
                    {(review.guest?.full_name ?? "G")
                      .split(" ")
                      .slice(0, 2)
                      .map((part) => part.charAt(0))
                      .join("")
                      .toUpperCase()}
                  </span>
                  <div>
                    <p className="text-sm font-semibold">
                      {review.guest?.full_name}
                    </p>
                    <div className="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                      <Stars rating={review.rating} />
                      <span className="ml-1">
                        • {t("reviews.verified")}
                      </span>
                    </div>
                  </div>
                </figcaption>
                <p className="mt-3 text-xs text-muted-foreground">
                  {review.created_at ? formatDate(review.created_at) : ""}
                </p>
              </figure>
            ))}
          </div>
        )}

        <ReviewForm />
      </div>
    </section>
  );
}