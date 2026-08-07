<?php

use App\Settings\MenuSettings;
use App\Support\Brand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/** Boja palete najbliža pikselu — podloga je prelaz, pa nema tačnog poklapanja. */
function nearestPaletteColour(GdImage $image, int $x, int $y): string
{
    $pixel = imagecolorsforindex($image, imagecolorat($image, $x, $y));
    $distances = [];

    foreach (array_keys(Brand::palette()) as $hex) {
        $distances[$hex] = ($pixel['red'] - hexdec(substr($hex, 1, 2))) ** 2
            + ($pixel['green'] - hexdec(substr($hex, 3, 2))) ** 2
            + ($pixel['blue'] - hexdec(substr($hex, 5, 2))) ** 2;
    }

    return (string) array_search(min($distances), $distances, true);
}

/** Boja iz podešavanja se primijeni na cijelu aplikaciju. */
function chooseColour(string $hex): void
{
    $settings = app(MenuSettings::class);
    $settings->primary_color = $hex;
    $settings->save();
}

it('čita glavnu boju iz podešavanja', function (): void {
    chooseColour('#2563EB');

    expect(Brand::hex())->toBe('#2563EB')
        ->and(Brand::rgb())->toBe([37, 99, 235])
        ->and(Brand::css())->toBe('37, 99, 235');
});

it('odbacuje boju izvan palete i vraća podrazumijevanu', function (): void {
    chooseColour('#123456');

    expect(Brand::hex())->toBe(Brand::DEFAULT_COLOR);
});

it('ubacuje odabranu boju u stranicu, bez ponovnog build-a CSS-a', function (): void {
    chooseColour('#2563EB');

    $html = $this->get(route('invoices.index'))->assertSuccessful()->getContent();

    expect($html)->toContain('--primary-base: 37, 99, 235;')
        ->and($html)->toContain('rel="icon"')
        ->and($html)->toContain('apple-touch-icon.png')
        ->and($html)->toContain('name="theme-color"');
});

it('nudi paletu i pamti odabranu boju', function (): void {
    $this->put(route('settings.menu.update'), [
        'menu_modules' => ['invoices'],
        'drawer_modules' => ['clients'],
        'max_menu_items' => 4,
        'primary_color' => '#10B981',
    ])->assertRedirect(route('settings.menu.edit'))
        ->assertSessionHas('status', 'Boja i raspored su sačuvani.');

    expect(app(MenuSettings::class)->primary_color)->toBe('#10B981');

    $html = $this->get(route('settings.menu.edit'))->assertSuccessful()->getContent();

    expect($html)->toContain('Boja aplikacije')
        ->and($html)->toContain('value="#10B981" class="peer sr-only"')
        ->and(substr_count($html, 'name="primary_color"'))->toBe(count(Brand::palette()));
});

it('ne prima boju izvan palete', function (): void {
    $this->put(route('settings.menu.update'), [
        'menu_modules' => ['invoices'],
        'max_menu_items' => 4,
        'primary_color' => '#000000',
    ])->assertSessionHasErrors('primary_color');
});

it('crta ikonu, favicon i splash ekrane u odabranoj boji', function (): void {
    chooseColour('#14B8A6');
    $directory = sys_get_temp_dir().'/brand-'.uniqid();

    Artisan::call('app:brand-assets', ['--path' => $directory]);

    foreach (['icon.png', 'apple-touch-icon.png', 'splash.png', 'splash-dark.png', 'favicon.ico'] as $file) {
        expect(File::exists($directory.'/'.$file))->toBeTrue();
    }

    // Podloga je prelaz: gornji lijevi ugao nosi izabranu boju, ostali uglovi njene parnjake.
    $icon = imagecreatefrompng($directory.'/icon.png');
    $mesh = Brand::iconColours('#14B8A6')['mesh'];

    expect(nearestPaletteColour($icon, 120, 120))->toBe($mesh[0])
        ->and(nearestPaletteColour($icon, 900, 120))->toBe($mesh[1])
        ->and(nearestPaletteColour($icon, 120, 900))->toBe($mesh[2]);

    // ICO zaglavlje: jedan zapis, 32×32.
    expect(bin2hex(substr((string) File::get($directory.'/favicon.ico'), 0, 6)))->toBe('000001000100');

    File::deleteDirectory($directory);
});

it('prihvata zadanu boju umjesto one iz aplikacije', function (): void {
    chooseColour('#F59E0B');
    $directory = sys_get_temp_dir().'/brand-'.uniqid();

    Artisan::call('app:brand-assets', ['--path' => $directory, '--color' => '#2563EB']);

    $icon = imagecreatefrompng($directory.'/icon.png');

    expect(nearestPaletteColour($icon, 120, 120))->toBe('#2563EB');

    File::deleteDirectory($directory);
});

it('izvodi boje ikone iz izabrane boje', function (): void {
    // Izabrana boja je prva u podlozi, ostale idu u pravilnom razmaku po paleti.
    expect(Brand::iconColours('#F59E0B'))->toBe([
        'mesh' => ['#F59E0B', '#EC4899', '#0EA5E9', '#8B5CF6'],
        'keys' => ['#F59E0B', '#F97316', '#EF4444', '#EC4899', '#8B5CF6', '#2563EB', '#0EA5E9', '#14B8A6'],
        'display' => '#0EA5E9',
    ]);

    $teal = Brand::iconColours('#14B8A6');

    expect($teal['mesh'][0])->toBe('#14B8A6')
        ->and($teal['keys'])->toHaveCount(8)
        // Neutralna siva ostaje van ikone.
        ->and($teal['mesh'])->not->toContain('#64748B');
});

it('koristi isti znak kalkulatora na ikoni i u aplikaciji', function (): void {
    $svg = $this->get(route('invoices.index'))->assertSuccessful()->getContent();

    // Znak na ikoni se crta iz ovih koordinata, pa oblik mora ostati isti i u aplikaciji.
    expect($svg)->toContain('<rect width="16" height="20" x="4" y="2" rx="2"/>')
        ->and($svg)->toContain('<line x1="16" x2="16" y1="14" y2="18"/>');
});
