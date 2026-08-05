<?php

namespace App\Console\Commands;

use App\Support\Brand;
use GdImage;
use Illuminate\Console\Command;

/**
 * Ikona, favicon i splash ekrani za upakovanu aplikaciju.
 *
 * NativePHP traži `public/icon.png` (1024×1024) i `public/splash.png` /
 * `public/splash-dark.png` (najmanje 1080×1920), pa ih sam skalira za sve
 * gustine ekrana; pregledač uz to traži favicon i apple-touch-icon. Umjesto da
 * te slike stoje kao neobjašnjivi binarni fajlovi, crtaju se odavde — iz boje koja je
 * izabrana u Podešavanja → Izgled i navigacija, ili iz one zadate uz `--color`.
 *
 * Crta se na četiri puta većem platnu pa se smanjuje: GD ne izglađuje ivice
 * popunjenih oblika, a smanjivanje to riješi.
 */
class MakeBrandAssetsCommand extends Command
{
    protected $signature = 'app:brand-assets
        {--path=public : Direktorij u koji se slike upisuju}
        {--color= : Boja u obliku #RRGGBB; podrazumijevano ona izabrana u aplikaciji}';

    protected $description = 'Nacrtaj ikonu, favicon i splash ekrane iz boje identiteta';

    private const SUPERSAMPLE = 4;

    /** @var array{0: int, 1: int, 2: int} */
    private array $primary;

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->components->error('GD ekstenzija nije učitana.');

            return self::FAILURE;
        }

        $colour = $this->colourOption() ?? Brand::hex();
        $this->primary = [
            (int) hexdec(substr($colour, 1, 2)),
            (int) hexdec(substr($colour, 3, 2)),
            (int) hexdec(substr($colour, 5, 2)),
        ];
        $this->components->twoColumnDetail('boja identiteta', $colour);

        $this->write('icon.png', $this->icon(1024));
        $this->write('apple-touch-icon.png', $this->icon(180));
        $this->write('splash.png', $this->splash(1080, 1920, Brand::backgroundRgb('light')));
        $this->write('splash-dark.png', $this->splash(1080, 1920, Brand::backgroundRgb('dark')));
        $this->favicon();

        return self::SUCCESS;
    }

    /** Boja se može zadati i ručno, npr. kad se ikona priprema za drugačiji build. */
    private function colourOption(): ?string
    {
        $value = (string) $this->option('color');

        if ($value === '') {
            return null;
        }

        $value = ltrim(trim($value), '#');

        if (strlen($value) !== 6 || ! ctype_xdigit($value)) {
            $this->components->warn('Boja mora biti u obliku #RRGGBB; koristi se boja iz aplikacije.');

            return null;
        }

        return '#'.strtoupper($value);
    }

    /** Favicon mora ostati `.ico` jer ga pregledači traže po toj putanji i bez `<link>`. */
    private function favicon(): void
    {
        $image = $this->icon(32);

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        // ICO zaglavlje: jedan zapis koji pokazuje na PNG odmah iza njega.
        $ico = pack('vvv', 0, 1, 1)
            .pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22)
            .$png;

        $path = $this->target('favicon.ico');
        file_put_contents($path, $ico);

        $this->components->twoColumnDetail('favicon.ico', number_format(strlen($ico) / 1024, 0).' KB');
    }

    private function write(string $file, GdImage $image): void
    {
        $path = $this->target($file);

        imagepng($image, $path, 9);
        imagedestroy($image);

        $this->components->twoColumnDetail($file, number_format(filesize($path) / 1024, 0).' KB');
    }

    private function target(string $file): string
    {
        $directory = (string) $this->option('path');

        if (! str_starts_with($directory, '/')) {
            $directory = base_path($directory);
        }

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.'/'.$file;
    }

    /** Puna amber podloga sa bijelim znakom kalkulatora — isto kao logo u zaglavlju. */
    private function icon(int $size): GdImage
    {
        $scale = self::SUPERSAMPLE;
        $canvas = $this->canvas($size * $scale, $size * $scale);

        $this->roundedRect(
            $canvas, 0, 0, $size * $scale, $size * $scale,
            (int) ($size * $scale * 0.22), $this->primary,
        );

        $this->calculator(
            $canvas, $size * $scale / 2, $size * $scale / 2,
            $size * $scale * 0.52, $this->primary,
        );

        return $this->downsample($canvas, $size, $size);
    }

    /** @param array{0: int, 1: int, 2: int} $background */
    private function splash(int $width, int $height, array $background): GdImage
    {
        $scale = 2;
        $canvas = $this->canvas($width * $scale, $height * $scale);

        imagefilledrectangle($canvas, 0, 0, $width * $scale, $height * $scale, $this->colour($canvas, $background));

        // Amber pločica sa znakom, po sredini — isto što se vidi na ekranu za PIN.
        $tile = (int) ($width * $scale * 0.26);
        $left = (int) (($width * $scale - $tile) / 2);
        $top = (int) (($height * $scale - $tile) / 2);

        $this->roundedRect($canvas, $left, $top, $tile, $tile, (int) ($tile * 0.28), $this->primary);
        $this->calculator($canvas, $left + $tile / 2, $top + $tile / 2, $tile * 0.52, $this->primary);

        return $this->downsample($canvas, $width, $height);
    }

    /**
     * Znak kalkulatora — ista lucide „calculator" ikona koja stoji u zaglavlju aplikacije.
     *
     * Crta se iz njenih koordinata u polju 24×24, pa znak na ikoni i znak na ekranu
     * ostaju isti oblik: obris, traka displeja, sedam tastera i uspravna tipka desno.
     *
     * @param  array{0: int, 1: int, 2: int}  $background  boja ispod znaka, za „šuplji" obris
     */
    private function calculator(GdImage $image, float $centreX, float $centreY, float $glyph, array $background): void
    {
        $unit = $glyph / 24;
        $stroke = max(1, (int) round(2 * $unit));
        $white = [255, 255, 255];

        $x = fn (float $value): int => (int) round($centreX + ($value - 12) * $unit);
        $y = fn (float $value): int => (int) round($centreY + ($value - 12) * $unit);

        // <rect width="16" height="20" x="4" y="2" rx="2"/> — obris, pa unutrašnjost nazad u podlogu
        $this->roundedRect($image, $x(4), $y(2), (int) round(16 * $unit), (int) round(20 * $unit), (int) round(2 * $unit), $white);
        $this->roundedRect(
            $image, $x(4) + $stroke, $y(2) + $stroke,
            (int) round(16 * $unit) - 2 * $stroke, (int) round(20 * $unit) - 2 * $stroke,
            max(1, (int) round(2 * $unit) - $stroke), $background,
        );

        // <line x1="8" x2="16" y1="6" y2="6"/> i <line x1="16" x2="16" y1="14" y2="18"/>
        $this->capsule($image, $x(8), $y(6), $x(16), $y(6), $stroke, $white);
        $this->capsule($image, $x(16), $y(14), $x(16), $y(18), $stroke, $white);

        // <path d="M16 10h.01"/> i ostali — tačke sa okruglim krajem, iste debljine kao poteg
        foreach ([[8, 10], [12, 10], [16, 10], [8, 14], [12, 14], [8, 18], [12, 18]] as [$dotX, $dotY]) {
            $this->capsule($image, $x($dotX), $y($dotY), $x($dotX), $y($dotY), $stroke, $white);
        }
    }

    /**
     * Poteg sa okruglim krajevima, kakav SVG crta uz `stroke-linecap="round"`.
     * Tačka je poteg dužine nula.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function capsule(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $thickness, array $rgb): void
    {
        $colour = $this->colour($image, $rgb);
        $radius = (int) round($thickness / 2);

        if ($x1 !== $x2 || $y1 !== $y2) {
            imagefilledrectangle(
                $image,
                min($x1, $x2) - ($y1 === $y2 ? 0 : $radius),
                min($y1, $y2) - ($x1 === $x2 ? 0 : $radius),
                max($x1, $x2) + ($y1 === $y2 ? 0 : $radius),
                max($y1, $y2) + ($x1 === $x2 ? 0 : $radius),
                $colour,
            );
        }

        imagefilledellipse($image, $x1, $y1, $thickness, $thickness, $colour);
        imagefilledellipse($image, $x2, $y2, $thickness, $thickness, $colour);
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
