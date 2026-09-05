import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Check, X, Star } from "lucide-react";
import { toast } from "sonner";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import StatusBadge from "@/components/StatusBadge";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import { LoadingState, ErrorState, EmptyState } from "@/components/States";
import { fetchReviews, approveReview, rejectReview } from "@/services/api/reviews";
import { getErrorMessage, formatRelative, initialsOf } from "@/lib/format";

const STATUSES = ["pending", "approved", "rejected"];

function RatingStars({ value }) {
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((n) => (
        <Star
          key={n}
          className={`h-4 w-4 ${n <= value ? "fill-[#D9A441] text-[#D9A441]" : "text-[#DCE3D5]"}`}
          strokeWidth={1.5}
        />
      ))}
    </div>
  );
}

export default function ReviewsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [rating, setRating] = useState("");
  const [actionTarget, setActionTarget] = useState(null);

  const query = useQuery({
    queryKey: ["reviews", page, search, status, rating],
    queryFn: () =>
      fetchReviews({
        page,
        search: search || undefined,
        status: status || undefined,
        rating: rating || undefined,
      }),
  });

  const mutation = useMutation({
    mutationFn: ({ type, id }) => (type === "approve" ? approveReview(id) : rejectReview(id)),
    onSuccess: (_data, vars) => {
      toast.success(`Review ${vars.type === "approve" ? "approved" : "rejected"}.`);
      queryClient.invalidateQueries({ queryKey: ["reviews"] });
      setActionTarget(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not update review.")),
  });

  const columns = [
    {
      header: "Reviewer",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#7FA35C]/12 text-sm font-semibold text-[#4F7A3B]">
            {initialsOf(r.guest?.full_name)}
          </span>
          <div>
            <p className="font-medium text-[#1E2B22]">{r.guest?.full_name ?? "—"}</p>
            <p className="text-xs text-[#7A8677]">
              {r.booking?.booking_code} · {formatRelative(r.created_at)}
            </p>
          </div>
        </div>
      ),
    },
    {
      header: "Rating",
      cell: (r) => <RatingStars value={r.rating} />,
    },
    {
      header: "Comment",
      cell: (r) => (
        <p className="max-w-md truncate text-[#5E6B5A]">
          {r.comment || <span className="text-[#A8B39F]">No comment</span>}
        </p>
      ),
    },
    {
      header: "Status",
      cell: (r) => <StatusBadge status={r.status} />,
    },
    {
      header: "",
      className: "text-right",
      cell: (r) =>
        r.status === "pending" ? (
          <div className="flex items-center justify-end gap-1.5">
            <button
              type="button"
              onClick={() => setActionTarget({ type: "approve", review: r })}
              className="flex h-8 items-center gap-1.5 rounded-lg bg-[#16261F] px-3 text-xs font-medium text-[#F2F5EC] transition-colors hover:bg-[#20372C]"
            >
              <Check className="h-3.5 w-3.5" strokeWidth={1.5} />
              Approve
            </button>
            <button
              type="button"
              onClick={() => setActionTarget({ type: "reject", review: r })}
              className="flex h-8 items-center gap-1.5 rounded-lg border border-[#C25B50]/30 px-3 text-xs font-medium text-[#B3453A] transition-colors hover:bg-[#C25B50]/10"
            >
              <X className="h-3.5 w-3.5" strokeWidth={1.5} />
              Reject
            </button>
          </div>
        ) : (
          <div className="flex justify-end">
            <StatusBadge status={r.status} />
          </div>
        ),
    },
  ];

  if (query.isError) {
    return <ErrorState message="Could not load reviews." onRetry={query.refetch} />;
  }

  const { items = [], meta = {} } = query.data ?? {};

  return (
    <div>
      <PageHeader
        title="Reviews"
        description="Moderate guest feedback that appears on your property listing."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search reviewers…"
        />
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value);
            setPage(1);
          }}
          className="h-10 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm text-[#5E6B5A] outline-none focus:border-[#7FA35C]"
        >
          <option value="">All statuses</option>
          {STATUSES.map((s) => (
            <option key={s} value={s}>
              {s.charAt(0).toUpperCase() + s.slice(1)}
            </option>
          ))}
        </select>
        <select
          value={rating}
          onChange={(e) => {
            setRating(e.target.value);
            setPage(1);
          }}
          className="h-10 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm text-[#5E6B5A] outline-none focus:border-[#7FA35C]"
        >
          <option value="">All ratings</option>
          {[5, 4, 3, 2, 1].map((n) => (
            <option key={n} value={n}>
              {n} stars
            </option>
          ))}
        </select>
      </div>

      {query.isLoading ? (
        <LoadingState label="Loading reviews…" />
      ) : items.length === 0 ? (
        <EmptyState
          title="No reviews found"
          description="Try adjusting your filters."
        />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={query.isLoading}
            emptyTitle="No reviews found"
          />
          <div className="mt-3 rounded-xl border border-[#DCE3D5] bg-white">
            <Pagination
              current={page}
              total={meta.total ?? 0}
              perPage={meta.per_page ?? 15}
              onPageChange={setPage}
            />
          </div>
        </>
      )}

      <ConfirmDialog
        open={Boolean(actionTarget)}
        onOpenChange={(open) => !open && setActionTarget(null)}
        variant={actionTarget?.type === "reject" ? "danger" : "default"}
        title={actionTarget?.type === "approve" ? "Approve this review?" : "Reject this review?"}
        description={
          actionTarget?.type === "approve"
            ? "The review will become visible on your public listing."
            : "The review will be hidden and the guest will not be notified of the reason."
        }
        confirmLabel={actionTarget?.type === "approve" ? "Approve" : "Reject"}
        loading={mutation.isPending}
        onConfirm={() =>
          actionTarget && mutation.mutate({ type: actionTarget.type, id: actionTarget.review.id })
        }
      />
    </div>
  );
}