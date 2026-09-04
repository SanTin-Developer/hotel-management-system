<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Room\IndexAmenityRequest;
use App\Http\Requests\Api\V1\Room\StoreAmenityRequest;
use App\Http\Requests\Api\V1\Room\UpdateAmenityRequest;
use App\Http\Resources\Api\V1\AmenityResource;
use App\Models\Amenity;
use App\Services\AmenityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AmenityController extends Controller
{
    public function __construct(
        private readonly AmenityService $amenityService
    ) {}

    public function index(
        IndexAmenityRequest $request
    ): AnonymousResourceCollection {
        return AmenityResource::collection(
            $this->amenityService->getAll(
                $request->validated()
            )
        );
    }

    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $amenity = $this->amenityService->create(
            $request->validated()
        );

        return (new AmenityResource(
            $amenity->loadCount('rooms')
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Amenity $amenity): AmenityResource
    {
        return new AmenityResource(
            $this->amenityService->getById($amenity)
        );
    }

    public function update(
        UpdateAmenityRequest $request,
        Amenity $amenity
    ): AmenityResource {
        return new AmenityResource(
            $this->amenityService->update(
                $amenity,
                $request->validated()
            )
        );
    }

    public function destroy(Amenity $amenity): JsonResponse
    {
        try {
            $this->amenityService->delete($amenity);

            return response()->json([
                'message' => 'Amenity deleted successfully.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'This amenity cannot be deleted because it is still assigned to one or more rooms.',
            ], 409);
        }
    }
}
