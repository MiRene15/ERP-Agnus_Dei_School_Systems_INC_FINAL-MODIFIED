<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorage
{
    protected string $baseUrl;
    protected string $serviceKey;
    protected string $bucket;

    public function __construct()
    {
        $this->baseUrl = config('services.supabase.url', env('SUPABASE_URL'));
        $this->serviceKey = config('services.supabase.service_key', env('SUPABASE_SERVICE_KEY'));
        $this->bucket = config('services.supabase.storage_bucket', env('SUPABASE_STORAGE_BUCKET', 'school-documents'));
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
        ]);
        if (config('app.env') === 'local') {
            $http = $http->withoutVerifying();
        }
        return $http;
    }

    public function upload(string $path, $content): bool
    {
        $url = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];
        $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

        $response = $this->http()
            ->withHeaders(['Content-Type' => $mimeType])
            ->withBody($content, $mimeType)
            ->put($url);

        if ($response->successful()) {
            return true;
        }

        Log::error('Supabase Storage upload failed', [
            'path' => $path,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    public function delete(string $path): bool
    {
        $url = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";

        $response = $this->http()->delete($url);

        if ($response->successful()) {
            return true;
        }

        Log::error('Supabase Storage delete failed', [
            'path' => $path,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    public function getSignedUrl(string $path, int $expiresInSeconds = 3600): ?string
    {
        $url = "{$this->baseUrl}/storage/v1/object/sign/{$this->bucket}/{$path}";

        $response = $this->http()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, ['expiresIn' => $expiresInSeconds]);

        if ($response->successful()) {
            $data = $response->json();
            Log::info('Supabase sign response', $data);
            $signedUrl = $data['signedURL'] ?? $data['signedUrl'] ?? $data['signed_url'] ?? null;
            if ($signedUrl && !str_starts_with($signedUrl, 'http')) {
                $signedUrl = $this->baseUrl . $signedUrl;
            }
            return $signedUrl;
        }

        Log::error('Supabase Storage sign failed', [
            'path' => $path,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }
}
