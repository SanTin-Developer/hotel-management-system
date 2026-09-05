<?php

namespace App\Services\Media;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        $config = new Configuration([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => config('services.cloudinary.secure', true),
            ],
        ]);

        $this->cloudinary = new Cloudinary($config);
    }

    /**
     * Upload a file to Cloudinary.
     *
     * @return array{public_id: string, url: string, secure_url: string, format: string}
     */
    public function upload(
        UploadedFile|string $file,
        string $folder,
        array $options = []
    ): array {
        $source = $file instanceof UploadedFile
            ? $file->getRealPath() ?: $file->getPathname()
            : $file;

        $upload = $this->cloudinary->uploadApi()->upload(
            $source,
            array_merge([
                'folder' => $folder,
                'resource_type' => 'image',
                'overwrite' => false,
            ], $options)
        );

        return $this->normalize($upload);
    }

    /**
     * Delete an asset from Cloudinary by its public id.
     */
    public function destroy(string $publicId, bool $invalidate = true): bool
    {
        $response = $this->cloudinary->uploadApi()->destroy(
            $publicId,
            ['invalidate' => $invalidate]
        );

        return ($response['result'] ?? null) === 'ok';
    }

    /**
     * Extract a public id from a Cloudinary URL (used to clean up dangling assets).
     */
    public function publicIdFromUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        if (! str_contains($url, 'res.cloudinary.com')) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! $path) {
            return null;
        }

        // Removes leading "/<cloud>/image/" or "/<cloud>/video/" prefixes
        // and the trailing file extension.
        $segments = explode('/', trim($path, '/'));

        // Drop: [cloud_name, image|video, ...rest]
        array_shift($segments);

        $versionIdx = null;

        foreach ($segments as $idx => $segment) {
            if (preg_match('/^v\d+$/', $segment)) {
                $versionIdx = $idx;
                break;
            }
        }

        if ($versionIdx !== null) {
            $segments = array_slice($segments, $versionIdx + 1);
        }

        if ($segments === []) {
            return null;
        }

        $last = $segments[count($segments) - 1];

        // Strip file extension
        $segments[count($segments) - 1] = preg_replace('/\.[a-zA-Z0-9]+$/', '', $last);

        return implode('/', $segments) ?: null;
    }

    /**
     * @return array{public_id: string, url: string, secure_url: string, format: string}
     */
    private function normalize(ApiResponse $response): array
    {
        $publicId = $response['public_id'] ?? null;

        if (! $publicId) {
            throw new RuntimeException(
                'Cloudinary upload did not return a public_id.'
            );
        }

        return [
            'public_id' => (string) $publicId,
            'url' => (string) ($response['url'] ?? ''),
            'secure_url' => (string) ($response['secure_url'] ?? ''),
            'format' => (string) ($response['format'] ?? ''),
        ];
    }
}
