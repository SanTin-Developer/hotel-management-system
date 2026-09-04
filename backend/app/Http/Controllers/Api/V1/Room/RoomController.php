<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Room\IndexRoomRequest;
use App\Http\Requests\Api\V1\Room\StoreRoomRequest;
use App\Http\Requests\Api\V1\Room\SyncRoomAmenitiesRequest;
use App\Http\Requests\Api\V1\Room\UpdateRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Services\Room\RoomService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    /**
     * Display all rooms.
     */
    public function index(
        IndexRoomRequest $request
    ): AnonymousResourceCollection {
        $rooms = $this->roomService->getAll(
            $request->validated()
        );

        return RoomResource::collection($rooms);
    }

    /**
     * Store a new room.
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create(
            $request->validated()
        );

        return (new RoomResource($room))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display one room.
     */
    public function show(Room $room): RoomResource
    {
        $room = $this->roomService->getById($room);

        return new RoomResource($room);
    }

    /**
     * Update an existing room.
     */
    public function update(
        UpdateRoomRequest $request,
        Room $room
    ): RoomResource {
        $room = $this->roomService->update(
            $room,
            $request->validated()
        );

        return new RoomResource($room);
    }

    /**
     * Delete a room.
     */
    public function destroy(Room $room): JsonResponse
    {
        try {
            $this->roomService->delete($room);

            return response()->json([
                'message' => 'Room deleted successfully.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'This room cannot be deleted because it is still referenced by existing booking records.',
            ], 409);
        }
    }

    public function syncAmenities(
        SyncRoomAmenitiesRequest $request,
        Room $room
    ): RoomResource {
        $room = $this->roomService->syncAmenities(
            $room,
            $request->validated('amenity_ids')
        );

        return new RoomResource($room);
    }

    public function removeAmenity(
        Room $room,
        int $amenity
    ): RoomResource {
        $room = $this->roomService->removeAmenity(
            $room,
            $amenity
        );

        return new RoomResource($room);
    }
}
