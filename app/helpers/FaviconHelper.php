<?php
/**
 * @file FaviconHelper.php
 * @brief Helper for fetching and saving favicons from websites.
 *
 * Provides functionality to automatically download favicons from project URLs.
 *
 * @author Stephane H.
 * @date 2025-01-17
 */

/**
 * Class FaviconHelper
 * Handles favicon fetching and saving operations.
 *
 * @package App\Helpers
 */
class FaviconHelper {
    /**
     * Fetch favicon from a website URL and save it locally.
     *
     * @param string $url The website URL to fetch favicon from
     * @return string|null The relative path to the saved favicon, or null if failed
     * 
     * @input string $url - The website URL (must be valid http:// or https://, FTP URLs are not supported)
     * @output string|null - Relative path to saved favicon (e.g., '/favicon/abc123.png') or null on failure
     * @date 2025-01-17
     * @creator Stephane H.
     */
    public static function fetchFavicon($url) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            return null;
        }

        // Don't try to fetch favicon for FTP links
        if (isset($parsedUrl['scheme']) && strtolower($parsedUrl['scheme']) === 'ftp') {
            return null;
        }

        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $baseUrl .= ':' . $parsedUrl['port'];
        }

        // List of possible favicon locations to try
        $faviconUrls = [
            $baseUrl . '/favicon.ico',
            $baseUrl . '/favicon.png',
            $url . '/favicon.ico',
        ];

        // Try to get favicon from HTML meta tags
        $htmlFavicon = self::extractFaviconFromHtml($url);
        if ($htmlFavicon) {
            // If relative URL, make it absolute
            if (strpos($htmlFavicon, 'http') !== 0) {
                if (strpos($htmlFavicon, '/') === 0) {
                    $htmlFavicon = $baseUrl . $htmlFavicon;
                } else {
                    $htmlFavicon = $baseUrl . '/' . $htmlFavicon;
                }
            }
            array_unshift($faviconUrls, $htmlFavicon);
        }

        // Try each favicon URL
        foreach ($faviconUrls as $faviconUrl) {
            $faviconData = self::downloadFavicon($faviconUrl);
            if ($faviconData) {
                $savedPath = self::saveFavicon($faviconData, $faviconUrl);
                if ($savedPath) {
                    return $savedPath;
                }
            }
        }

        return null;
    }

    /**
     * Extract favicon URL from HTML page.
     *
     * @param string $url The website URL
     * @return string|null The favicon URL or null if not found
     * 
     * @input string $url - The website URL
     * @output string|null - Favicon URL from HTML or null
     * @date 2025-01-17
     * @creator Stephane H.
     */
    private static function extractFaviconFromHtml($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'follow_location' => 1,
                'max_redirects' => 3
            ]
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            return null;
        }

        // Look for favicon in various link tags
        $patterns = [
            '/<link[^>]+rel=["\'](?:icon|shortcut icon|apple-touch-icon)["\'][^>]+href=["\']([^"\']+)["\']/i',
            '/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:icon|shortcut icon|apple-touch-icon)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Download favicon from URL.
     *
     * @param string $url The favicon URL
     * @return string|null The favicon binary data or null if failed
     * 
     * @input string $url - The favicon URL to download
     * @output string|null - Binary data of favicon or null on failure
     * @date 2025-01-17
     * @creator Stephane H.
     */
    private static function downloadFavicon($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'follow_location' => 1,
                'max_redirects' => 3
            ]
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false || strlen($data) === 0) {
            return null;
        }

        // Verify it's an image
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/x-icon',
            'image/svg+xml',
            'image/vnd.microsoft.icon',
            'image/webp'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return null;
        }

        return $data;
    }

    /**
     * Save favicon to local storage.
     *
     * @param string $data The favicon binary data
     * @param string $sourceUrl The source URL (for determining file extension)
     * @return string|null The relative path to saved file or null if failed
     * 
     * @input string $data - Binary data of favicon
     * @input string $sourceUrl - Source URL for extension detection
     * @output string|null - Relative path to saved favicon or null on failure
     * @date 2025-01-17
     * @creator Stephane H.
     */
    private static function saveFavicon($data, $sourceUrl) {
        $uploadFileDir = __DIR__ . '/../../public/favicon/';
        if (!is_dir($uploadFileDir)) {
            if (!mkdir($uploadFileDir, 0755, true)) {
                return null;
            }
        }

        // Determine file extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        $extension = 'png'; // default
        $mimeToExt = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp'
        ];

        if (isset($mimeToExt[$mimeType])) {
            $extension = $mimeToExt[$mimeType];
        } else {
            // Try to get extension from URL
            $parsedUrl = parse_url($sourceUrl);
            if (isset($parsedUrl['path'])) {
                $pathParts = pathinfo($parsedUrl['path']);
                if (isset($pathParts['extension'])) {
                    $ext = strtolower($pathParts['extension']);
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'webp'])) {
                        $extension = $ext;
                    }
                }
            }
        }

        $newFileName = md5(time() . $sourceUrl . uniqid()) . '.' . $extension;
        $dest_path = $uploadFileDir . $newFileName;

        if (file_put_contents($dest_path, $data) !== false) {
            return '/favicon/' . $newFileName;
        }

        return null;
    }
}

