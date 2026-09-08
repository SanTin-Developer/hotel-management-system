<?php

namespace App\Http\Controllers\Api\V1\Coupon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupon\IndexCouponRequest;
use App\Http\Requests\Api\V1\Coupon\StoreCouponRequest;
use App\Http\Requests\Api\V1\Coupon\UpdateCouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService
    ) {}

    /**
     * Public endpoint: returns coupons currently active for the website.
     */
    public function active(): AnonymousResourceCollection
    {
        $now = now();

        return CouponResource::collection(
            Coupon::query()
                ->where('status', 'active')
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->orderBy('end_date')
                ->limit(12)
                ->get()
        );
    }

    public function index(
        IndexCouponRequest $request
    ): AnonymousResourceCollection {
        return CouponResource::collection(
            $this->couponService->getAll(
                $request->validated()
            )
        );
    }

    /**
     * Public endpoint: validates a coupon code for the website checkout.
     */
    public function validateCode(string $code, Request $request): JsonResponse
    {
        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->where('status', 'active')
            ->first();

        if (! $coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'This code is invalid or no longer available.',
                'coupon' => null,
            ]);
        }

        $now = now();

        if (
            $coupon->start_date && $now->lt($coupon->start_date)
            || $coupon->end_date && $now->gt($coupon->end_date)
        ) {
            return response()->json([
                'valid' => false,
                'message' => 'This offer has expired or is not active yet.',
                'coupon' => new CouponResource($coupon),
            ]);
        }

        $amount = (float) $request->float('amount', 0);

        if ($amount > 0 && $coupon->min_amount && $amount < (float) $coupon->min_amount) {
            return response()->json([
                'valid' => false,
                'message' => 'Minimum booking amount for this code is $'
                    . number_format((float) $coupon->min_amount, 2).'.',
                'coupon' => new CouponResource($coupon),
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Code applied successfully!',
            'coupon' => new CouponResource($coupon),
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponService->create(
            $request->validated()
        );

        return (new CouponResource($coupon))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Coupon $coupon): CouponResource
    {
        $coupon = $this->couponService->getById($coupon);

        return new CouponResource($coupon);
    }

    public function update(
        UpdateCouponRequest $request,
        Coupon $coupon
    ): CouponResource {
        $coupon = $this->couponService->update(
            $coupon,
            $request->validated()
        );

        return new CouponResource($coupon);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            $this->couponService->delete($coupon);

            return response()->json([
                'message' => 'Coupon deleted successfully.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'This coupon cannot be deleted because it is referenced by existing bookings.',
            ], 409);
        }
    }
}
