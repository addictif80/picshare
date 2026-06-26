<?php

namespace PicShare\Services;

class ImageService
{
    public static function compress(string $sourcePath, string $destPath): bool
    {
        $info = @getimagesize($sourcePath);
        if (!$info) return false;

        [$width, $height, $type] = $info;
        $maxW = COMPRESSED_MAX_WIDTH;
        $maxH = COMPRESSED_MAX_HEIGHT;

        // Calculate new dimensions
        if ($width > $maxW || $height > $maxH) {
            $ratio = min($maxW / $width, $maxH / $height);
            $newW = (int) round($width * $ratio);
            $newH = (int) round($height * $ratio);
        } else {
            $newW = $width;
            $newH = $height;
        }

        $src = self::createFromFile($sourcePath, $type);
        if (!$src) return false;

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

        $result = match($type) {
            IMAGETYPE_JPEG => imagejpeg($dst, $destPath, COMPRESSED_QUALITY),
            IMAGETYPE_PNG  => imagepng($dst, $destPath, 7),
            IMAGETYPE_WEBP => imagewebp($dst, $destPath, COMPRESSED_QUALITY),
            default        => imagejpeg($dst, $destPath, COMPRESSED_QUALITY),
        };

        imagedestroy($src);
        imagedestroy($dst);
        return $result;
    }

    public static function applyWatermark(string $sourcePath, string $destPath): bool
    {
        $info = @getimagesize($sourcePath);
        if (!$info) return false;

        [$width, $height, $type] = $info;
        $img = self::createFromFile($sourcePath, $type);
        if (!$img) return false;

        $watermarkType = setting('watermark_type', 'text');
        $opacity = (int) setting('watermark_opacity', 40);

        if ($watermarkType === 'image' && setting('watermark_image')) {
            self::applyImageWatermark($img, $width, $height, $opacity);
        } else {
            self::applyTextWatermark($img, $width, $height, $opacity);
        }

        $result = match($type) {
            IMAGETYPE_JPEG => imagejpeg($img, $destPath, COMPRESSED_QUALITY),
            IMAGETYPE_PNG  => imagepng($img, $destPath, 7),
            IMAGETYPE_WEBP => imagewebp($img, $destPath, COMPRESSED_QUALITY),
            default        => imagejpeg($img, $destPath, COMPRESSED_QUALITY),
        };

        imagedestroy($img);
        return $result;
    }

    private static function applyTextWatermark($img, int $width, int $height, int $opacity): void
    {
        $text = setting('watermark_text', '© PicShare');
        $fontSize = (int) setting('watermark_size', 24);
        $fontFile = BASE_PATH . '/public/fonts/Inter-Bold.ttf';

        // Fallback: use GD built-in font if TTF not available
        if (!file_exists($fontFile)) {
            self::applyTextWatermarkBuiltin($img, $width, $height, $text, $opacity);
            return;
        }

        // Compute tile size
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textW = abs($bbox[4] - $bbox[0]) + 20;
        $textH = abs($bbox[5] - $bbox[1]) + 20;

        // Create watermark tile with transparency
        $tile = imagecreatetruecolor($textW * 2, $textH * 2);
        imagealphablending($tile, false);
        imagesavealpha($tile, true);
        $transparent = imagecolorallocatealpha($tile, 0, 0, 0, 127);
        imagefill($tile, 0, 0, $transparent);
        imagealphablending($tile, true);

        $alpha = (int) round(127 * (1 - $opacity / 100));
        $color = imagecolorallocatealpha($tile, 255, 255, 255, $alpha);
        imagettftext($tile, $fontSize, -30, 10, $textH, $color, $fontFile, $text);

        // Tile across image
        $tileW = imagesx($tile);
        $tileH = imagesy($tile);
        imagealphablending($img, true);
        for ($y = 0; $y < $height; $y += $tileH) {
            for ($x = 0; $x < $width; $x += $tileW) {
                imagecopy($img, $tile, $x, $y, 0, 0, $tileW, $tileH);
            }
        }
        imagedestroy($tile);
    }

    private static function applyTextWatermarkBuiltin($img, int $width, int $height, string $text, int $opacity): void
    {
        $font = 5;
        $charW = imagefontwidth($font);
        $charH = imagefontheight($font);
        $textW = strlen($text) * $charW;
        $textH = $charH;
        $stepX = $textW + 60;
        $stepY = $textH + 40;

        $alpha = (int) round(127 * (1 - $opacity / 100));
        $color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
        imagealphablending($img, true);

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                imagestring($img, $font, $x, $y, $text, $color);
            }
        }
    }

    private static function applyImageWatermark($img, int $width, int $height, int $opacity): void
    {
        $wmPath = UPLOAD_PATH . '/logos/' . setting('watermark_image');
        if (!file_exists($wmPath)) return;

        $wmInfo = @getimagesize($wmPath);
        if (!$wmInfo) return;

        $wm = self::createFromFile($wmPath, $wmInfo[2]);
        if (!$wm) return;

        $wmW = imagesx($wm);
        $wmH = imagesy($wm);

        // Scale watermark to ~15% of image width
        $targetW = (int) ($width * 0.15);
        $ratio = $targetW / $wmW;
        $scaledW = (int) ($wmW * $ratio);
        $scaledH = (int) ($wmH * $ratio);

        $scaledWm = imagecreatetruecolor($scaledW, $scaledH);
        imagealphablending($scaledWm, false);
        imagesavealpha($scaledWm, true);
        $transparent = imagecolorallocatealpha($scaledWm, 0, 0, 0, 127);
        imagefill($scaledWm, 0, 0, $transparent);
        imagecopyresampled($scaledWm, $wm, 0, 0, 0, 0, $scaledW, $scaledH, $wmW, $wmH);
        imagedestroy($wm);

        $stepX = $scaledW + 40;
        $stepY = $scaledH + 40;
        imagealphablending($img, true);

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                imagecopy($img, $scaledWm, $x, $y, 0, 0, $scaledW, $scaledH);
            }
        }
        imagedestroy($scaledWm);
    }

    private static function createFromFile(string $path, int $type): \GdImage|false
    {
        return match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            IMAGETYPE_GIF  => imagecreatefromgif($path),
            default        => false,
        };
    }

    public static function getMimeType(string $path): string
    {
        $info = @getimagesize($path);
        return $info['mime'] ?? 'image/jpeg';
    }

    public static function isValidImage(string $path): bool
    {
        $info = @getimagesize($path);
        if (!$info) return false;
        return in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF]);
    }
}
