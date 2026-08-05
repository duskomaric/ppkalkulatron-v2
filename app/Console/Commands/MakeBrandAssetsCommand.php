<?php

namespace App\Console\Commands;

use App\Support\Brand;
use GdImage;
use Illuminate\Console\Command;

/**
 * Ikona, favicon i splash ekrani za upakovanu aplikaciju.
 *
 * NativePHP traži `public/icon.png` (1024×1024) i `public/splash.png` /
 * `public/splash-dark.png` (najmanje 1080×1920), pa ih sam skalira za sve gustine
 * ekrana; pregledač uz to traži favicon i apple-touch-icon. Umjesto da te slike
 * stoje kao neobjašnjivi binarni fajlovi, crtaju se odavde.
 *
 * Izgled: podloga sa mekim prelazom u četiri boje palete, preko nje bijelo tijelo
 * digitrona sa tipkama u bojama palete. Sve boje se izvode iz one izabrane u
 * Podešavanja → Izgled i navigacija, ili iz one zadate uz `--color`.
 */
class MakeBrandAssetsCommand extends Command
{
    protected $signature = 'app:brand-assets
        {--path=public : Direktorij u koji se slike upisuju}
        {--color= : Boja u obliku #RRGGBB; podrazumijevano ona izabrana u aplikaciji}';

    protected $description = 'Nacrtaj ikonu, favicon i splash ekrane iz boje identiteta';

    private const SUPERSAMPLE = 4;

    /**
     * Udio znaka u ikoni koju sistem maskira.
     *
     * Android od `icon.png` pravi i „adaptive" ikonu, gdje pokretač isječe sve izvan
     * kruga prečnika 2/3 ivice. Znak veći od ovoga bi na takvim pokretačima bio odsječen.
     */
    private const GLYPH_MASKED = 0.64;

    /** Favicon i apple-touch-icon niko ne maskira, pa znak može biti krupniji. */
    private const GLYPH_PLAIN = 0.78;

    private const CORNER = 0.22;

    private const WHITE = [255, 255, 255];

    /** @var array{mesh: list<string>, keys: list<string>, display: string} */
    private array $colours;

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->components->error('GD ekstenzija nije učitana.');

            return self::FAILURE;
        }

        $colour = $this->colourOption() ?? Brand::hex();
        $this->colours = Brand::iconColours($colour);

        $this->components->twoColumnDetail('boja identiteta', $colour);
        $this->components->twoColumnDetail('podloga', implode(' ', $this->colours['mesh']));

        $this->write('icon.png', $this->icon(1024));
        $this->write('apple-touch-icon.png', $this->icon(180, self::GLYPH_PLAIN));
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
        $image = $this->icon(32, self::GLYPH_PLAIN);

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();

        // ICO zaglavlje: jedan zapis koji pokazuje na PNG odmah iza njega.
        $ico = pack('vvv', 0, 1, 1)
            .pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22)
            .$png;

        file_put_contents($this->target('favicon.ico'), $ico);

        $this->components->twoColumnDetail('favicon.ico', number_format(strlen($ico) / 1024, 0).' KB');
    }

    private function write(string $file, GdImage $image): void
    {
        $path = $this->target($file);

        imagepng($image, $path, 9);

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

    /**
     * Ikona: zaobljena podloga sa prelazom, pa znak digitrona preko nje.
     *
     * @param  float  $glyph  udio znaka u širini ikone
     */
    private function icon(int $size, float $glyph = self::GLYPH_MASKED): GdImage
    {
        $icon = $this->roundCorners(
            $this->mesh($size, $this->colours['mesh']),
            $size,
            (int) round($size * self::CORNER),
        );

        $layer = $this->canvas($size * self::SUPERSAMPLE, $size * self::SUPERSAMPLE);
        $this->calculator(
            $layer,
            $size * self::SUPERSAMPLE / 2,
            $size * self::SUPERSAMPLE / 2,
            $size * self::SUPERSAMPLE * $glyph,
        );

        imagecopy($icon, $this->downsample($layer, $size, $size), 0, 0, 0, 0, $size, $size);

        return $icon;
    }

    /**
     * Splash: podloga u boji aplikacije i ista ikona po sredini.
     *
     * @param  array{0: int, 1: int, 2: int}  $background
     */
    private function splash(int $width, int $height, array $background): GdImage
    {
        $canvas = $this->canvas($width, $height);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $this->colour($canvas, $background));

        $tile = (int) ($width * 0.26);
        imagecopy(
            $canvas, $this->icon($tile),
            (int) (($width - $tile) / 2), (int) (($height - $tile) / 2),
            0, 0, $tile, $tile,
        );

        return $canvas;
    }

    /**
     * Meki prelaz između tačaka boje: svaki piksel je prosjek svih, gdje bliža boja
     * nosi više. Računa se na malom platnu pa razvlači — otud razliven prelaz.
     *
     * @param  list<string>  $hexes  boje u uglovima, redom
     */
    private function mesh(int $size, array $hexes): GdImage
    {
        $work = 160;
        $layer = imagecreatetruecolor($work, $work);
        $points = [];

        foreach ($hexes as $index => $hex) {
            $points[] = [$index % 2, intdiv($index, 2), $this->channels($hex)];
        }

        for ($y = 0; $y < $work; $y++) {
            for ($x = 0; $x < $work; $x++) {
                $sum = [0.0, 0.0, 0.0];
                $total = 0.0;

                foreach ($points as [$px, $py, $rgb]) {
                    $dx = $x / ($work - 1) - $px;
                    $dy = $y / ($work - 1) - $py;
                    $weight = 1 / (($dx * $dx + $dy * $dy) ** 1.35 + 0.0025);

                    $total += $weight;
                    $sum[0] += $rgb[0] * $weight;
                    $sum[1] += $rgb[1] * $weight;
                    $sum[2] += $rgb[2] * $weight;
                }

                imagesetpixel($layer, $x, $y, imagecolorallocate(
                    $layer,
                    (int) round($sum[0] / $total),
                    (int) round($sum[1] / $total),
                    (int) round($sum[2] / $total),
                ));
            }
        }

        $target = imagecreatetruecolor($size, $size);
        imagecopyresampled($target, $layer, 0, 0, 0, 0, $size, $size, $work, $work);

        return $target;
    }

    /** Podlozi se sijeku uglovi; maska se crta uvećano pa smanjuje da ivica bude glatka. */
    private function roundCorners(GdImage $flat, int $size, int $radius): GdImage
    {
        $mask = $this->canvas($size * self::SUPERSAMPLE, $size * self::SUPERSAMPLE);
        $this->roundedRect(
            $mask, 0, 0, $size * self::SUPERSAMPLE, $size * self::SUPERSAMPLE,
            $radius * self::SUPERSAMPLE, self::WHITE,
        );
        $mask = $this->downsample($mask, $size, $size);

        $target = $this->canvas($size, $size);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $alpha = (imagecolorat($mask, $x, $y) >> 24) & 0x7F;

                if ($alpha < 127) {
                    imagesetpixel($target, $x, $y, ($alpha << 24) | (imagecolorat($flat, $x, $y) & 0xFFFFFF));
                }
            }
        }

        return $target;
    }

    /**
     * Znak digitrona: bijelo tijelo, traka displeja i osam punih tipki u bojama palete.
     * Raspored prati lucide „calculator" iz zaglavlja aplikacije, u polju 24×24.
     */
    private function calculator(GdImage $image, float $centreX, float $centreY, float $glyph): void
    {
        $unit = $glyph / 24;
        $stroke = max(1, (int) round(2 * $unit));
        $x = fn (float $value): int => (int) round($centreX + ($value - 12) * $unit);
        $y = fn (float $value): int => (int) round($centreY + ($value - 12) * $unit);

        // <rect width="16" height="20" x="4" y="2" rx="2"/> — puno tijelo
        $this->roundedRect(
            $image, $x(4), $y(2),
            (int) round(16 * $unit), (int) round(20 * $unit),
            (int) round(2 * $unit), self::WHITE,
        );

        // <line x1="8" x2="16" y1="6" y2="6"/> — displej
        $this->capsule($image, $x(8), $y(6), $x(16), $y(6), (int) round(2.4 * $unit), $this->channels($this->colours['display']));

        $keys = $this->colours['keys'];
        $side = 3.0;

        foreach ([[8, 10], [12, 10], [16, 10], [8, 14], [12, 14], [8, 18], [12, 18]] as $index => [$keyX, $keyY]) {
            $this->roundedRect(
                $image, $x($keyX - $side / 2), $y($keyY - $side / 2),
                (int) round($side * $unit), (int) round($side * $unit),
                (int) round(1.1 * $unit), $this->channels($keys[$index]),
            );
        }

        // <line x1="16" x2="16" y1="14" y2="18"/> — uspravna tipka desno
        $this->roundedRect(
            $image, $x(16 - $side / 2), $y(14 - $side / 2),
            (int) round($side * $unit), (int) round(($side + 4) * $unit),
            (int) round(1.1 * $unit), $this->channels($keys[7]),
        );
    }

    /**
     * Poteg sa okruglim krajevima, kakav SVG crta uz `stroke-linecap="round"`.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function capsule(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $thickness, array $rgb): void
    {
        $colour = $this->colour($image, $rgb);
        $radius = (int) round($thickness / 2);

        imagefilledrectangle($image, min($x1, $x2), $y1 - $radius, max($x1, $x2), $y2 + $radius, $colour);
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
        imagealphablending($target, false);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        imagealphablending($target, true);

        return $target;
    }

    /** @param array{0: int, 1: int, 2: int} $rgb */
    private function colour(GdImage $image, array $rgb): int
    {
        return imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function channels(string $hex): array
    {
        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ];
    }

    /** @param array{0: int, 1: int, 2: int} $rgb */
    private function roundedRect(GdImage $image, int $x, int $y, int $width, int $height, int $radius, array $rgb): void
    {
        $colour = $this->colour($image, $rgb);
        $right = $x + $width - 1;
        $bottom = $y + $height - 1;
        $radius = max(0, min($radius, (int) floor(min($width, $height) / 2)));

        imagefilledrectangle($image, $x + $radius, $y, $right - $radius, $bottom, $colour);
        imagefilledrectangle($image, $x, $y + $radius, $right, $bottom - $radius, $colour);

        foreach ([[$x + $radius, $y + $radius], [$right - $radius, $y + $radius],
            [$x + $radius, $bottom - $radius], [$right - $radius, $bottom - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $colour);
        }
    }
}
