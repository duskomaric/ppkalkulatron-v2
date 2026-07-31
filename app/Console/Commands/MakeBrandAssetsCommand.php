<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * Ikona i splash ekrani za upakovanu aplikaciju.
 *
 * NativePHP traži `public/icon.png` (1024×1024) i `public/splash.png` /
 * `public/splash-dark.png` (najmanje 1080×1920), pa ih sam skalira za sve
 * gustine ekrana. Umjesto da te slike stoje kao neobjašnjivi binarni fajlovi,
 * crtaju se odavde — iz istih boja koje aplikacija koristi u CSS-u.
 *
 * Crta se na četiri puta većem platnu pa se smanjuje: GD ne izglađuje ivice
 * popunjenih oblika, a smanjivanje to riješi.
 */
class MakeBrandAssetsCommand extends Command
{
    protected $signature = 'app:brand-assets';

    protected $description = 'Nacrtaj ikonu i splash ekrane iz boja aplikacije';

    /** Amber 500 — --primary-base iz resources/css/app.css. */
    private const PRIMARY = [245, 158, 11];

    private const DARK_BG = [11, 11, 15];

    private const LIGHT_BG = [248, 250, 252];

    private const SUPERSAMPLE = 4;

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->components->error('GD ekstenzija nije učitana.');

            return self::FAILURE;
        }

        $this->write('public/icon.png', $this->icon(1024));
        $this->write('public/splash.png', $this->splash(1080, 1920, self::LIGHT_BG));
        $this->write('public/splash-dark.png', $this->splash(1080, 1920, self::DARK_BG));

        return self::SUCCESS;
    }

    private function write(string $path, GdImage $image): void
    {
        imagepng($image, base_path($path), 9);
        imagedestroy($image);

        $this->components->twoColumnDetail($path, number_format(filesize(base_path($path)) / 1024, 0).' KB');
    }

    /** Puna amber podloga sa bijelim znakom kalkulatora — isto kao logo u zaglavlju. */
    private function icon(int $size): GdImage
    {
        $scale = self::SUPERSAMPLE;
        $canvas = $this->canvas($size * $scale, $size * $scale);

        $this->roundedRect(
            $canvas, 0, 0, $size * $scale, $size * $scale,
            (int) ($size * $scale * 0.22), self::PRIMARY,
        );

        $this->calculator($canvas, $size * $scale, (int) ($size * $scale * 0.52), null, self::PRIMARY);

        return $this->downsample($canvas, $size, $size);
    }

    private function splash(int $width, int $height, array $background): GdImage
    {
        $scale = 2;
        $canvas = $this->canvas($width * $scale, $height * $scale);

        imagefilledrectangle($canvas, 0, 0, $width * $scale, $height * $scale, $this->colour($canvas, $background));

        // Amber pločica sa znakom, po sredini — isto što se vidi na ekranu za PIN.
        $tile = (int) ($width * $scale * 0.26);
        $left = (int) (($width * $scale - $tile) / 2);
        $top = (int) (($height * $scale - $tile) / 2);

        $this->roundedRect($canvas, $left, $top, $tile, $tile, (int) ($tile * 0.28), self::PRIMARY);
        $this->calculator($canvas, $width * $scale, (int) ($tile * 0.52), $top + (int) ($tile / 2), self::PRIMARY);

        return $this->downsample($canvas, $width, $height);
    }

    /**
     * Znak kalkulatora: obris, traka displeja i dva reda tastera.
     * Prati lucide „calculator", isti koji se koristi u aplikaciji.
     */
    private function calculator(GdImage $image, int $canvasWidth, int $glyph, ?int $centreY, array $background): void
    {
        $white = $this->colour($image, [255, 255, 255]);

        $bodyWidth = (int) ($glyph * 0.72);
        $bodyHeight = $glyph;
        $left = (int) (($canvasWidth - $bodyWidth) / 2);
        $top = ($centreY ?? (int) ($canvasWidth / 2)) - (int) ($bodyHeight / 2);
        $stroke = max(2, (int) ($glyph * 0.055));

        $this->roundedRect($image, $left, $top, $bodyWidth, $bodyHeight, (int) ($glyph * 0.12), [255, 255, 255]);
        $this->roundedRect($image, $left + $stroke, $top + $stroke,
            $bodyWidth - 2 * $stroke, $bodyHeight - 2 * $stroke,
            max(1, (int) ($glyph * 0.12) - $stroke), $background);

        // Displej
        $inset = (int) ($glyph * 0.14);
        imagefilledrectangle(
            $image,
            $left + $inset,
            $top + (int) ($glyph * 0.17),
            $left + $bodyWidth - $inset,
            $top + (int) ($glyph * 0.17) + $stroke,
            $white,
        );

        // Tasteri: tri kolone, dva reda
        $button = (int) ($glyph * 0.09);
        $gapX = (int) (($bodyWidth - 2 * $inset - 3 * $button) / 2);
        $gapY = (int) ($glyph * 0.16);
        $firstRow = $top + (int) ($glyph * 0.42);

        foreach ([0, 1] as $row) {
            foreach ([0, 1, 2] as $column) {
                $x = $left + $inset + $column * ($button + $gapX);
                $y = $firstRow + $row * ($button + $gapY);

                imagefilledrectangle($image, $x, $y, $x + $button, $y + $stroke, $white);
            }
        }
    }

    private function canvas(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        return $image;
    }

    private function downsample(GdImage $source, int $width, int $height): GdImage
    {
        $target = $this->canvas($width, $height);
        imagecopyresampled(
            $target, $source, 0, 0, 0, 0,
            $width, $height, imagesx($source), imagesy($source),
        );
        imagedestroy($source);

        return $target;
    }

    private function colour(GdImage $image, array $rgb): int
    {
        return imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }

    private function roundedRect(GdImage $image, int $x, int $y, int $width, int $height, int $radius, array $rgb): void
    {
        $colour = $this->colour($image, $rgb);
        $right = $x + $width - 1;
        $bottom = $y + $height - 1;

        imagefilledrectangle($image, $x + $radius, $y, $right - $radius, $bottom, $colour);
        imagefilledrectangle($image, $x, $y + $radius, $right, $bottom - $radius, $colour);

        foreach ([[$x + $radius, $y + $radius], [$right - $radius, $y + $radius],
            [$x + $radius, $bottom - $radius], [$right - $radius, $bottom - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $colour);
        }
    }
}
