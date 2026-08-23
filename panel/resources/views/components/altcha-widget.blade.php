@props(['challengeUrl' => null])

@if (filled($challengeUrl))
    <div {{ $attributes->class(['altcha-wrap']) }}>
        <altcha-widget
            challenge="{{ $challengeUrl }}"
            type="switch"
            theme="default"
            auto="onsubmit"
        ></altcha-widget>
        @error('altcha')
            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>
@endif
