<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RoomType\IndexRoomTypeRequest;
use App\Http\Requests\Api\V1\RoomType\StoreRoomTypeRequest;
use App\Http\Requests\Api\V1\RoomType\UpdateRoomTypeRequest;
use App\Http\Requests\Api\V1\RoomType\UploadRoomTypeImageRequest;
use App\Http\Resources\Api\V1\RoomTypeResource;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Services\RoomTypeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomTypeController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRoomTypeRequest $request): AnonymousResourceCollection
    {
        return RoomTypeResource::collection(
            $this->roomTypeService->getAll(
                $request->validated()
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        //
        $roomType = $this->roomTypeService->create(
            $request->validated()
        );

        return (new RoomTypeResource(
            $roomType->loadCount('rooms')
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RoomType $roomType): RoomTypeResource
    {
        //
        $roomType = $this->roomTypeService->getById($roomType);

        return new RoomTypeResource($roomType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateRoomTypeRequest $request,
        RoomType $roomType
    ): RoomTypeResource {
        //
        $roomType = $this->roomTypeService->update(
            $roomType,
            $request->validated()
        );

        return new RoomTypeResource($roomType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomType $roomType): JsonResponse
    {
        //
        try {
            $this->roomTypeService->delete($roomType);

            return response()->json([
                'message' => 'Room type deleted successfully.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'This room type cannot be deleted because it is still assigned to one or more rooms.',
            ], 409);
        }
    }

    public function uploadImages(
        UploadRoomTypeImageRequest $request,
        RoomType $roomType
    ): RoomTypeResource {
        $roomType = $this->roomTypeService->uploadImages(
            $roomType,
            $request->file('images')
        );

        return new RoomTypeResource($roomType);
    }

    public function removeImage(
        RoomType $roomType,
        RoomTypeImage $image
    ): RoomTypeResource {
        $roomType = $this->roomTypeService->removeImage($roomType, $image);

        return new RoomTypeResource($roomType);
    }

    public function reorderImages(
        RoomType $roomType
    ): RoomTypeResource {
        $roomType = $this->roomTypeService->reorderImages(
            $roomType,
            request()->input('image_ids', [])
        );

        return new RoomTypeResource($roomType);
    }
}
