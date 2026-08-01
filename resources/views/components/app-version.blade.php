@props(['releaseVersion', 'buildCode', 'assetBuildHash' => null])

<p {{ $attributes->class('text-[8px] font-black text-[var(--color-text-dim)] uppercase tracking-[0.3em]') }}>
    ppKalkulatron v{{ $releaseVersion }} · build {{ $buildCode }}
    @if ($assetBuildHash)
        · {{ $assetBuildHash }}
    @endif
</p>
