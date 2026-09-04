<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Guest\CreateGuestRequest;
use App\Http\Requests\Api\V1\Guest\IndexGuestRequest;
use App\Http\Requests\Api\V1\Guest\UpdateGuestRequest;
use App\Http\Resources\Api\V1\GuestResource;
use App\Models\Guest;
use App\Services\Guest\GuestService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuestController extends Controller
{
    public function __construct(
        private readonly GuestService $guestService
    ) {}

    public function index(
        IndexGuestRequest $request
    ): AnonymousResourceCollection {
        return GuestResource::collection(
            $this->guestService->getAll(
                $request->validated()
            )
        );
    }

    public function store(CreateGuestRequest $request): JsonResponse
    {
        $guest = $this->guestService->create(
            $request->validated()
        );

        return (new GuestResource($guest->loadCount('bookings')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Guest $guest): GuestResource
    {
        $guest = $this->guestService->getById($guest);

        return new GuestResource($guest);
    }

    public function update(
        UpdateGuestRequest $request,
        Guest $guest
    ): GuestResource {
        $guest = $this->guestService->update(
            $guest,
            $request->validated()
        );

        return new GuestResource($guest);
    }

    public function destroy(Guest $guest): JsonResponse
    {
        try {
            $this->guestService->delete($guest);

            return response()->json([
                'message' => 'Guest deleted successfully.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'This guest cannot be deleted because they have existing booking records.',
            ], 409);
        }
    }
}
