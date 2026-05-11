<?php

namespace App\Services\Amazon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches and parses Amazon buyer customization data.
 *
 * Amazon provides customization info as a ZIP file at a signed URL
 * found in order item's BuyerCustomizedInfo.CustomizedURL field.
 *
 * The ZIP typically contains:
 *   - customization.json  (structured label/value pairs)
 *   - customization.xml   (older format, same data)
 *   - preview images      (.png/.jpg of the customized product)
 */
class CustomizationService
{
    /**
     * Given a CustomizedURL, download the ZIP, extract customization
     * fields, and return them as a flat key→value array.
     *
     * Returns null if the URL is empty or parsing fails.
     */
    public function fetchAndParse(string $customizedUrl): ?array
    {
        try {
            // 1. Download the ZIP into a temp file
            $zipPath = $this->downloadZip($customizedUrl);
            if (!$zipPath) {
                return null;
            }

            // 2. Extract and parse inside the ZIP
            $data = $this->extractCustomizationData($zipPath);

            // 3. Cleanup temp file
            @unlink($zipPath);

            return $data;

        } catch (\Exception $e) {
            Log::warning('Customization parse failed: ' . $e->getMessage(), [
                'url' => $customizedUrl,
            ]);
            return null;
        }
    }

    // ─── Download ZIP ─────────────────────────────────────────────

    private function downloadZip(string $url): ?string
    {
        $response = Http::timeout(15)->get($url);

        if ($response->failed()) {
            Log::warning('Could not download customization ZIP', ['url' => $url]);
            return null;
        }

        $tmpPath = sys_get_temp_dir() . '/amz_custom_' . uniqid() . '.zip';
        file_put_contents($tmpPath, $response->body());

        return $tmpPath;
    }

    // ─── Extract and parse ZIP contents ──────────────────────────

    private function extractCustomizationData(string $zipPath): ?array
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Could not open ZIP file');
        }

        $result = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $contents = $zip->getFromIndex($i);

            // Prefer JSON format
            if (str_ends_with(strtolower($filename), '.json')) {
                $result = $this->parseJson($contents);
                break;
            }

            // Fall back to XML
            if (str_ends_with(strtolower($filename), '.xml')) {
                $result = $this->parseXml($contents);
                // Don't break — keep looking for a JSON file
            }
        }

        $zip->close();

        return $result;
    }

    // ─── Parse JSON customization format ─────────────────────────

    /**
     * Amazon JSON format example:
     * {
     *   "customizationList": [
     *     { "label": "Name on product", "value": "John" },
     *     { "label": "Font",            "value": "Script" }
     *   ]
     * }
     */
    private function parseJson(string $contents): ?array
    {
        $decoded = json_decode($contents, true);
        if (!$decoded) {
            return null;
        }

        // Standard Amazon format: customizationList array
        if (!empty($decoded['customizationList'])) {
            $result = [];
            foreach ($decoded['customizationList'] as $item) {
                $label = $item['label'] ?? $item['name'] ?? "Field";
                $value = $item['value'] ?? $item['selectedValue'] ?? '';
                $result[] = [
                    'label'    => $label,
                    'value'    => $value,
                    'type'     => $item['type'] ?? 'text',
                    'sequence' => $item['sequenceNumber'] ?? null,
                ];
            }
            // Sort by sequence number if present
            usort($result, fn($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));
            return $result;
        }

        // Return raw decoded data if structure is different
        return $decoded;
    }

    // ─── Parse XML customization format ──────────────────────────

    /**
     * Amazon XML format example:
     * <customizations>
     *   <customization>
     *     <label>Name on product</label>
     *     <value>John</value>
     *   </customization>
     * </customizations>
     */
    private function parseXml(string $contents): ?array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents);

        if ($xml === false) {
            return null;
        }

        $result = [];

        // Handle <customizations><customization>... format
        foreach ($xml->customization ?? [] as $item) {
            $result[] = [
                'label' => (string)($item->label ?? $item->Label ?? ''),
                'value' => (string)($item->value ?? $item->Value ?? ''),
                'type'  => 'text',
            ];
        }

        // Handle <CustomTextCustomizations><CustomTextCustomization>... format
        foreach ($xml->CustomTextCustomization ?? [] as $item) {
            $result[] = [
                'label' => (string)($item->Label ?? ''),
                'value' => (string)($item->Value ?? ''),
                'type'  => 'text',
            ];
        }

        return !empty($result) ? $result : null;
    }
}
