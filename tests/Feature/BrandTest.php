<?php

use App\Settings\MenuSettings;
use App\Support\Brand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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
    ])->assertRedirect(route('settings.menu.edit'));

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

    // Sredina ikone je znak, pa se boja podloge čita blizu ivice.
    $icon = imagecreatefrompng($directory.'/icon.png');
    $colour = imagecolorsforindex($icon, imagecolorat($icon, 512, 60));
    imagedestroy($icon);

    expect([$colour['red'], $colour['green'], $colour['blue']])->toBe([20, 184, 166]);

    // ICO zaglavlje: jedan zapis, 32×32.
    expect(bin2hex(substr((string) File::get($directory.'/favicon.ico'), 0, 6)))->toBe('000001000100');

    File::deleteDirectory($directory);
});

it('prihvata zadanu boju umjesto one iz aplikacije', function (): void {
    chooseColour('#F59E0B');
    $directory = sys_get_temp_dir().'/brand-'.uniqid();

    Artisan::call('app:brand-assets', ['--path' => $directory, '--color' => '#2563EB']);

    $icon = imagecreatefrompng($directory.'/icon.png');
    $colour = imagecolorsforindex($icon, imagecolorat($icon, 512, 60));
    imagedestroy($icon);

    expect([$colour['red'], $colour['green'], $colour['blue']])->toBe([37, 99, 235]);

    File::deleteDirectory($directory);
});

it('koristi isti znak kalkulatora na ikoni i u aplikaciji', function (): void {
    $svg = $this->get(route('invoices.index'))->assertSuccessful()->getContent();

    // Znak na ikoni se crta iz ovih koordinata, pa oblik mora ostati isti i u aplikaciji.
    expect($svg)->toContain('<rect width="16" height="20" x="4" y="2" rx="2"/>')
        ->and($svg)->toContain('<line x1="16" x2="16" y1="14" y2="18"/>');
});
