<?php

namespace App\Http\Controllers\Api\V1\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Review\IndexReviewRequest;
use App\Http\Requests\Api\V1\Review\StoreReviewRequest;
use App\Http\Requests\Api\V1\Review\UpdateReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    public function index(
        IndexReviewRequest $request
    ): AnonymousResourceCollection {
        return ReviewResource::collection(
            $this->reviewService->getAll(
                $request->validated()
            )
        );
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->create(
            $request->validated()
        );

        return (new ReviewResource($review))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Review $review): ReviewResource
    {
        $review = $this->reviewService->getById($review);

        return new ReviewResource($review);
    }

    public function update(
        UpdateReviewRequest $request,
        Review $review
    ): ReviewResource {
        $review = $this->reviewService->update(
            $review,
            $request->validated()
        );

        return new ReviewResource($review);
    }

    public function destroy(Review $review): JsonResponse
    {
        $this->reviewService->delete($review);

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }

    public function approve(Review $review): ReviewResource
    {
        $review = $this->reviewService->approve($review);

        return new ReviewResource($review);
    }

    public function reject(Review $review): ReviewResource
    {
        $review = $this->reviewService->reject($review);

        return new ReviewResource($review);
    }
}
