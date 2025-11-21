<?php

namespace Dotlogics\Grapesjs\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Dotlogics\Grapesjs\App\Repositories\AssetRepository;

class AssetController extends Controller
{
    use ValidatesRequests;

    public function index(AssetRepository $assetRepository): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $assetRepository->getAllMediaLinks()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, AssetRepository $assetRepository): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'file' => 'required|array',
            'file.*' => 'required|file'
        ]);

        return response()->json([
            'data' => $assetRepository->uploadFilesFromRequest('file')
        ]);
    }

    public function proxy(Request $request): void
    {
        try {
            $validated = $request->validate([
                'file' => 'required|string|url'
            ]);

            $file = $validated['file'];

            [$url, $isLocal] = $this->replaceLocalUrlToFilePath($file);

            if (!$isLocal) {
                $headers = get_headers($url, true);
            }

            if ($isLocal) {
                $headers = [
                    'Content-Type' => mime_content_type($url),
                    'Content-Length' => filesize($url),
                ];
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . (isset($headers['Content-Type']) ? $headers['Content-Type'] : 'application/octet-stream'));
            header('Content-Disposition: inline; filename="' . basename($url) . '"');
            header('Cache-Control: ' . (isset($headers['Cache-Control']) ? $headers['Cache-Control'] : 'must-revalidate'));
            header('Pragma: public');
            header('Access-Control-Allow-Origin: ' . request()->getSchemeAndHttpHost());
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header("Access-Control-Allow-Headers: X-Requested-With");
            if (isset($headers['Content-Length'])) {
                header('Content-Length: ' . $headers['Content-Length']);
            }
            readfile($url);
            exit;

        } catch (\Exception $ex) {
            abort(404,$ex->getMessage());
        }
    }

    private function replaceLocalUrlToFilePath(string $url): array
    {
        $urlParts = parse_url($url);
        if ($urlParts['host'] === 'localhost' && isset($urlParts['path'])) {
            $path = $urlParts['path'];

            // Prevent directory traversal attacks
            $realPath = realpath(public_path($path));
            $publicPath = realpath(public_path());

            // Ensure the file is within the public directory
            if ($realPath && str_starts_with($realPath, $publicPath)) {
                return [$realPath, true];
            }
        }

        return [$url, false];
    }
}
