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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService
    ) {}

    public function index(
        IndexCouponRequest $request
    ): AnonymousResourceCollection {
        return CouponResource::collection(
            $this->couponService->getAll(
                $request->validated()
            )
        );
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
