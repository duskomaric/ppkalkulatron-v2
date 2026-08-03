@props(['releaseVersion', 'buildCode', 'assetBuildHash' => null])

<p {{ $attributes->class('text-[8px] font-black text-[var(--color-text-dim)] uppercase tracking-[0.3em]') }}>
    {{ config('app.name') }} v{{ $releaseVersion }} · build {{ $buildCode }}
    @if ($assetBuildHash)
        · {{ $assetBuildHash }}
    @endif
</p>
