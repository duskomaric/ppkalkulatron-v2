import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { nativephpMobile, nativephpHotFile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig({
    build: {
        // Jump može još kratko držati stranicu sa prethodnim hashom. Zadržavamo
        // ranije assete da automatski reload ne ostane bez JavaScripta usred rada.
        emptyOutDir: false,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Aplikacija se iscrtava u webviewu, pa se sredstva serviraju sa
            // http://127.0.0.1 na uređaju, a ne sa localhosta razvojne mašine.
            // `nativephpHotFile()` razdvaja hot fajl po platformi (public/android-hot,
            // public/ios-hot) da dva `native:watch`-a ne prepisuju jedan drugom fajl.
            hotFile: nativephpHotFile(),
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 700, 800, 900],
                }),
            ],
        }),
        tailwindcss(),
        // Postavlja APP_URL, HMR host i CORS na vrijednosti koje uređaj priznaje, i
        // poslije builda briše zaostale hot fajlove — bez toga zapakovana aplikacija
        // pokazuje na mrtvi Vite server i ostane bez CSS-a i JS-a.
        nativephpMobile(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
