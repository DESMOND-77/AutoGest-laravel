@php
    $themes = \App\Support\Ui\Theme::cases();
    $accents = [
        'indigo' => ['label' => 'Indigo'],
        'violet' => ['label' => 'Violet'],
        'rose' => ['label' => 'Rose'],
        'orange' => ['label' => 'Orange'],
        'amber' => ['label' => 'Ambre'],
        'emerald' => ['label' => 'Émeraude'],
        'teal' => ['label' => 'Sarcelle'],
        'sky' => ['label' => 'Ciel'],
    ];
@endphp

<div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" class="relative">
    <button
        type="button"
        x-ref="appearanceTrigger"
        class="grid size-10 place-items-center rounded-sm text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
        x-on:click="open = !open"
        :aria-expanded="open.toString()"
        aria-haspopup="dialog"
        aria-label="Apparence : thème, accent, densité"
    >
        <x-ui.icon name="palette" />
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.duration.200ms
        x-anchor.bottom-end.offset.8="$refs.appearanceTrigger"
        class="material-solid z-50 w-[min(94vw,20rem)] rounded-lg p-4"
        role="dialog"
        aria-label="Préférences d’apparence"
    >
        <p class="text-eyebrow">Apparence</p>
        <p class="mt-1 text-[12px] text-ink-3">Enregistré sur cet appareil, et sur votre compte si vous êtes connecté.</p>

        <fieldset class="mt-4">
            <legend class="text-[12.5px] font-semibold text-ink">Thème</legend>
            <div class="mt-2 grid grid-cols-3 gap-1.5">
                <button
                    type="button"
                    class="flex flex-col items-center gap-1 rounded-sm border border-line p-2 text-[11.5px] hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                    :class="$store.prefs.theme === 'auto' && 'border-accent text-accent'"
                    x-on:click="$store.prefs.set('theme', 'auto')"
                >
                    <x-ui.icon name="monitor" class="w-4 h-4" />
                    Auto
                </button>
                @foreach ($themes as $theme)
                    <button
                        type="button"
                        class="flex flex-col items-center gap-1 rounded-sm border border-line p-2 text-[11.5px] hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                        :class="$store.prefs.theme === @js($theme->value) && 'border-accent text-accent'"
                        x-on:click="$store.prefs.set('theme', @js($theme->value))"
                        title="{{ $theme->description() }}"
                    >
                        <span class="size-4 rounded-full theme-swatch theme-swatch-{{ $theme->value }}"></span>
                        {{ $theme->label() }}
                    </button>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-4">
            <legend class="text-[12.5px] font-semibold text-ink">Couleur d’accent</legend>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <button type="button" class="grid size-8 place-items-center rounded-full border border-line text-[10px] font-semibold text-ink-2 hover:bg-hover" x-on:click="$store.prefs.set('accent', null)" title="Accent du thème">Auto</button>
                @foreach ($accents as $key => $accent)
                    <button
                        type="button"
                        class="grid size-8 place-items-center rounded-full border border-line hover:scale-105 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                        x-on:click="$store.prefs.set('accent', @js($key))"
                        :class="$store.prefs.accent === @js($key) && 'ring-2 ring-offset-2 ring-offset-[var(--surface-solid)]'"
                        aria-label="Accent {{ $accent['label'] }}"
                        title="{{ $accent['label'] }}"
                    >
                        <span class="size-5 rounded-full accent-swatch-{{ $key }}" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-4">
            <legend class="text-[12.5px] font-semibold text-ink">Densité</legend>
            <div class="mt-2 inline-flex rounded-pill bg-hover p-0.5">
                <button type="button" class="rounded-pill px-3 py-1 text-[12.5px] font-medium" :class="$store.prefs.density === 'comfortable' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('density', 'comfortable')">Confort</button>
                <button type="button" class="rounded-pill px-3 py-1 text-[12.5px] font-medium" :class="$store.prefs.density === 'compact' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('density', 'compact')">Compact</button>
            </div>
        </fieldset>

        <fieldset class="mt-4">
            <legend class="text-[12.5px] font-semibold text-ink">Performance</legend>
            <div class="mt-2 inline-flex rounded-pill bg-hover p-0.5">
                @foreach (['auto' => 'Auto', 'full' => 'Complet', 'lite' => 'Léger'] as $perfKey => $perfLabel)
                    <button type="button" class="rounded-pill px-3 py-1 text-[12.5px] font-medium" :class="$store.prefs.perf === @js($perfKey) ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('perf', @js($perfKey))">{{ $perfLabel }}</button>
                @endforeach
            </div>
            <p class="mt-1 text-[11.5px] text-ink-3">« Léger » désactive les flous et animations. Auto s’adapte aux appareils modestes.</p>
        </fieldset>

        <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex text-[12.5px] font-medium text-accent hover:underline">Gérer mes préférences</a>
    </div>
</div>
