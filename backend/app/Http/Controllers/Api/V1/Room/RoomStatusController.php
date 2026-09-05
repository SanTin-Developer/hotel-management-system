<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Room\ChangeRoomStatusRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Services\Room\RoomService;

class RoomStatusController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function __invoke(
        ChangeRoomStatusRequest $request,
        Room $room
    ): RoomResource {
        $room = $this->roomService->changeStatus(
            $room,
            $request->validated('status'),
            $request->user()->id,
            $request->validated('note') ?? null
        );

        return new RoomResource($room);
    }
}
