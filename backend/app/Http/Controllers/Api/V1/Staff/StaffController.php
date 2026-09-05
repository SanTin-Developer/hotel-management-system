<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Staff\IndexStaffRequest;
use App\Http\Requests\Api\V1\Staff\StoreStaffRequest;
use App\Http\Requests\Api\V1\Staff\UpdateStaffRequest;
use App\Http\Requests\Api\V1\Staff\UploadStaffPhotoRequest;
use App\Http\Resources\Api\V1\StaffResource;
use App\Models\Staff;
use App\Services\Staff\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $staffService
    ) {}

    public function index(
        IndexStaffRequest $request
    ): AnonymousResourceCollection {
        return StaffResource::collection(
            $this->staffService->getAll(
                $request->validated()
            )
        );
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = $this->staffService->create(
            $request->validated()
        );

        return (new StaffResource($staff))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Staff $staff): StaffResource
    {
        $staff = $this->staffService->getById($staff);

        return new StaffResource($staff);
    }

    public function update(
        UpdateStaffRequest $request,
        Staff $staff
    ): StaffResource {
        $staff = $this->staffService->update(
            $staff,
            $request->validated()
        );

        return new StaffResource($staff);
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $this->staffService->delete($staff);

        return response()->json([
            'message' => 'Staff member deleted successfully.',
        ]);
    }

    public function uploadPhoto(
        UploadStaffPhotoRequest $request,
        Staff $staff
    ): StaffResource {
        $staff = $this->staffService->uploadPhoto(
            $staff,
            $request->file('photo')
        );

        return new StaffResource($staff);
    }

    public function removePhoto(Staff $staff): StaffResource
    {
        $staff = $this->staffService->removePhoto($staff);

        return new StaffResource($staff);
    }
}
