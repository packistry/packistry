<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Sources\DestroySource;
use App\Actions\Sources\Inputs\StoreSourceInput;
use App\Actions\Sources\Inputs\UpdateSourceInput;
use App\Actions\Sources\StoreSource;
use App\Actions\Sources\UpdateSource;
use App\Enums\Permission;
use App\Exceptions\FailedToParseUrlException;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

readonly class SourceController extends Controller
{
    public function __construct(
        private StoreSource $storeSource,
        private DestroySource $destroySource,
        private UpdateSource $updateSource,
    ) {
        //
    }

    public function index(): JsonResponse
    {
        $this->authorize(Permission::SOURCE_READ);

        $sources = Source::query()->get();

        return response()->json(
            SourceResource::collection($sources)
        );
    }

    /**
     * @throws ValidationException
     */
    public function store(StoreSourceInput $input): JsonResponse
    {
        $this->authorize(Permission::SOURCE_CREATE);

        try {
            $source = $this->storeSource->handle($input);
        } catch (FailedToParseUrlException) {
            throw ValidationException::withMessages([
                'url' => ['URL must be a valid URL'],
            ]);
        }

        return response()->json(
            new SourceResource($source),
            201,
        );
    }

    /**
     * @throws ValidationException
     */
    public function update(UpdateSourceInput $input, string $sourceId): JsonResponse
    {
        $this->authorize(Permission::SOURCE_UPDATE);

        /** @var Source $source */
        $source = Source::query()->findOrFail($sourceId);

        try {
            $source = $this->updateSource->handle($source, $input);
        } catch (FailedToParseUrlException) {
            throw ValidationException::withMessages([
                'url' => ['URL must be a valid URL'],
            ]);
        }

        return response()->json(
            new SourceResource($source)
        );
    }

    public function destroy(string $sourceId): JsonResponse
    {
        $this->authorize(Permission::SOURCE_DELETE);

        $source = $this->destroySource->handle(
            source: Source::query()->findOrFail($sourceId),
        );

        return response()->json(
            new SourceResource($source)
        );
    }

    public function projects(Request $request, string $sourceId): JsonResponse
    {
        $this->authorize(Permission::SOURCE_READ);

        /** @var Source $source */
        $source = Source::query()->findOrFail($sourceId);

        $projects = $source->client()
            ->projects($request->input('search'));

        return response()->json(
            $projects
        );
    }
}
