<?php

namespace PicShare\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

class QRCodeService
{
    public static function generate(string $url, string $filename, array $options = []): string
    {
        $dir = STORAGE_PATH . '/qrcodes';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $destPath = $dir . '/' . $filename . '.png';

        $qrOptions = new QROptions([
            'outputType'         => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'           => QRCode::ECC_H,
            'scale'              => 10,
            'imageBase64'        => false,
            'moduleValues'       => [
                1536 => $options['color'] ?? '#1a1a2e', // dark modules
                6    => '#ffffff',                       // light modules
            ],
            'addQuietzone'       => true,
            'quietzoneSize'      => 4,
        ]);

        $qrCode = new QRCode($qrOptions);

        // Generate QR code image
        $qrImage = $qrCode->render($url, $destPath);

        // Add logo if provided
        $logoPath = null;
        if (!empty($options['logo']) && file_exists(UPLOAD_PATH . '/logos/' . $options['logo'])) {
            $logoPath = UPLOAD_PATH . '/logos/' . $options['logo'];
        } elseif (setting('site_logo') && file_exists(UPLOAD_PATH . '/logos/' . setting('site_logo'))) {
            $logoPath = UPLOAD_PATH . '/logos/' . setting('site_logo');
        }

        if ($logoPath) {
            self::embedLogo($destPath, $logoPath);
        }

        return $destPath;
    }

    private static function embedLogo(string $qrPath, string $logoPath): void
    {
        $qr = imagecreatefrompng($qrPath);
        if (!$qr) return;

        $qrW = imagesx($qr);
        $qrH = imagesy($qr);

        $logoInfo = @getimagesize($logoPath);
        if (!$logoInfo) { imagedestroy($qr); return; }

        $logo = match($logoInfo[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($logoPath),
            IMAGETYPE_PNG  => imagecreatefrompng($logoPath),
            IMAGETYPE_WEBP => imagecreatefromwebp($logoPath),
            default        => null,
        };
        if (!$logo) { imagedestroy($qr); return; }

        // Logo takes up ~20% of QR code
        $logoSize = (int) ($qrW * 0.20);
        $logoX    = (int) (($qrW - $logoSize) / 2);
        $logoY    = (int) (($qrH - $logoSize) / 2);

        // White background behind logo
        $white = imagecolorallocate($qr, 255, 255, 255);
        $padding = 8;
        imagefilledrectangle($qr, $logoX - $padding, $logoY - $padding, $logoX + $logoSize + $padding, $logoY + $logoSize + $padding, $white);

        // Scaled logo
        $scaled = imagecreatetruecolor($logoSize, $logoSize);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefill($scaled, 0, 0, $transparent);
        imagecopyresampled($scaled, $logo, 0, 0, 0, 0, $logoSize, $logoSize, imagesx($logo), imagesy($logo));

        imagecopy($qr, $scaled, $logoX, $logoY, 0, 0, $logoSize, $logoSize);

        imagepng($qr, $qrPath);
        imagedestroy($qr);
        imagedestroy($logo);
        imagedestroy($scaled);
    }

    public static function getUrl(string $filename): string
    {
        return BASE_URL . '/qr/' . $filename;
    }
}
