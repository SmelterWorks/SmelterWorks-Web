@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    @class([
        'block rounded-lg px-3 py-2 no-underline transition',
        'bg-ember/15 text-ember-light' => $active,
        'text-zinc-400 hover:bg-zinc-800/60 hover:text-zinc-100' => ! $active,
    ])
>
    {{ $slot }}
</a>
