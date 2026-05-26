<?php

namespace App\Services\Amazon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downloads and parses Amazon buyer customization ZIPs.
 *
 * The ZIP typically contains:
 *   - customization.json  → structured label/value pairs (text fields)
 *   - customization.xml   → older format fallback
 *   - *.jpg / *.png       → images uploaded by the buyer (photo keyring etc.)
 *
 * Text fields and image storage paths are returned together as a flat
 * array of { label, value, type } objects stored in order_items.customization_data.
 */
class CustomizationService
{
    // Where to store extracted images: storage/app/public/customizations/
    private const IMAGE_DISK   = 'public';
    private const IMAGE_FOLDER = 'customizations';

    /**
     * Fetch the ZIP from Amazon, extract all content, return parsed fields.
     * Returns null if the URL is empty or parsing fails entirely.
     */
    public function fetchAndParse(string $customizedUrl, ?string $orderItemId = null): ?array
    {
        try {
            $zipPath = $this->downloadZip($customizedUrl);
            if (!$zipPath) {
                return null;
            }

            $data = $this->extractAll($zipPath, $orderItemId);

            @unlink($zipPath);

            return !empty($data) ? $data : null;

        } catch (\Exception $e) {
            Log::warning('Customization parse failed: ' . $e->getMessage(), [
                'url'           => $customizedUrl,
                'order_item_id' => $orderItemId,
            ]);
            return null;
        }
    }

    // ─── Download ZIP ─────────────────────────────────────────────

    private function downloadZip(string $url): ?string
    {
        $response = Http::timeout(20)->get($url);

        if ($response->failed()) {
            Log::warning('Could not download customization ZIP', ['url' => $url, 'status' => $response->status()]);
            return null;
        }

        $tmpPath = sys_get_temp_dir() . '/amz_custom_' . uniqid() . '.zip';
        file_put_contents($tmpPath, $response->body());

        return $tmpPath;
    }

    // ─── Extract everything from the ZIP ─────────────────────────

    private function extractAll(string $zipPath, ?string $orderItemId): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Could not open customization ZIP');
        }

        $textFields = [];
        $imageFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename  = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $contents  = $zip->getFromIndex($i);

            // ── Text / structured data ──────────────────────────
            if ($extension === 'json') {
                $parsed = $this->parseJson($contents);
                if ($parsed) {
                    $textFields = array_merge($textFields, $parsed);
                }
                continue;
            }

            if ($extension === 'xml') {
                // Only use XML if no JSON was found yet
                if (empty($textFields)) {
                    $parsed = $this->parseXml($contents);
                    if ($parsed) {
                        $textFields = $parsed;
                    }
                }
                continue;
            }

            // ── Images ─────────────────────────────────────────
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $storedPath = $this->storeImage($contents, $filename, $orderItemId, $extension);
                if ($storedPath) {
                    $imageFiles[] = [
                        'label' => $this->labelFromFilename($filename),
                        'value' => $storedPath,           // relative storage path
                        'url'   => Storage::disk(self::IMAGE_DISK)->url($storedPath),
                        'type'  => 'image',
                    ];
                }
            }
        }

        $zip->close();

        // Text fields first, images after
        return array_merge($textFields, $imageFiles);
    }

    // ─── Store image to disk ──────────────────────────────────────

    private function storeImage(
        string $contents,
        string $originalFilename,
        ?string $orderItemId,
        string $extension
    ): ?string {
        try {
            // Subfolder per order item to avoid collisions
            $folder   = self::IMAGE_FOLDER . '/' . ($orderItemId ?? Str::random(8));
            $filename = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME))
                      . '_' . uniqid()
                      . '.' . $extension;

            $path = $folder . '/' . $filename;

            Storage::disk(self::IMAGE_DISK)->put($path, $contents);

            return $path; // e.g. customizations/62040955019962/uploaded_photo_abc123.jpg

        } catch (\Exception $e) {
            Log::warning('Could not store customization image: ' . $e->getMessage());
            return null;
        }
    }

    // ─── Parse JSON format ────────────────────────────────────────

    /**
     * Amazon JSON structure:
     * { "customizationList": [
     *     { "label": "Back engraving", "value": "Anto Melanie 22.08.", "type": "text" },
     *     { "label": "Uploaded Photo",  "value": "...", "type": "image" }
     *   ]
     * }
     */
    private function parseJson(string $contents): ?array
    {
        $decoded = json_decode($contents, true);
        if (!$decoded) {
            return null;
        }

        $result = [];

        $list = $decoded['customizationList']
            ?? $decoded['customizations']
            ?? $decoded['surfaces'][0]['colorMaps'][0]['customizations']  // nested format
            ?? [];

        // Handle nested surfaces format
        if (empty($list) && !empty($decoded['surfaces'])) {
            foreach ($decoded['surfaces'] as $surface) {
                foreach ($surface['colorMaps'] ?? [] as $colorMap) {
                    foreach ($colorMap['customizations'] ?? [] as $custom) {
                        $list[] = $custom;
                    }
                }
            }
        }

        foreach ($list as $item) {
            $label = $item['label']
                  ?? $item['name']
                  ?? $item['customizationType']
                  ?? 'Customization';

            $value = $item['value']
                  ?? $item['selectedValue']
                  ?? $item['customizationValue']
                  ?? '';

            $type = $item['type'] ?? 'text';

            // Skip if value is empty or is just a URL (images handled separately via ZIP files)
            if (empty($value) || filter_var($value, FILTER_VALIDATE_URL)) {
                continue;
            }

            $result[] = [
                'label'    => $label,
                'value'    => $value,
                'type'     => $type,
                'sequence' => $item['sequenceNumber'] ?? $item['sequence'] ?? null,
            ];
        }

        // Sort by sequence
        usort($result, fn($a, $b) => ($a['sequence'] ?? 999) <=> ($b['sequence'] ?? 999));

        return !empty($result) ? $result : null;
    }

    // ─── Parse XML format ─────────────────────────────────────────

    private function parseXml(string $contents): ?array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents);
        if ($xml === false) {
            return null;
        }

        $result = [];

        foreach ($xml->customization ?? $xml->Customization ?? [] as $item) {
            $label = (string)($item->label ?? $item->Label ?? $item->name ?? '');
            $value = (string)($item->value ?? $item->Value ?? '');

            if ($label && $value) {
                $result[] = ['label' => $label, 'value' => $value, 'type' => 'text'];
            }
        }

        // Amazon Custom XML variant
        foreach ($xml->CustomTextCustomization ?? [] as $item) {
            $label = (string)($item->Label ?? '');
            $value = (string)($item->Value ?? '');
            if ($label && $value) {
                $result[] = ['label' => $label, 'value' => $value, 'type' => 'text'];
            }
        }

        return !empty($result) ? $result : null;
    }

    // ─── Human-readable label from filename ───────────────────────

    private function labelFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        // Common Amazon file names
        return match (strtolower($base)) {
            'front', 'front_image'             => 'Front Image',
            'back',  'back_image'              => 'Back Image',
            'uploaded_image', 'buyer_image'    => 'Uploaded Photo',
            'preview', 'product_preview'       => 'Preview Image',
            default => ucwords(str_replace(['_', '-'], ' ', $base)),
        };
    }
}