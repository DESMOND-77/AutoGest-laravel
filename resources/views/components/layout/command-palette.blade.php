@php
    $paletteItems = collect($navGroups ?? [])
        ->flatMap(fn ($group) => collect($group['items'])->map(fn ($item) => [
            'label' => $item['label'],
            'group' => $group['label'],
            'icon' => $item['icon'] ?? 'circle',
            'url' => route($item['route']),
        ]))
        ->values();

    $quickCreate = collect($createActions ?? [])
        ->map(fn ($action) => [
            'label' => $action['label'],
            'group' => 'Créer',
            'icon' => $action['icon'] ?? 'plus',
            'url' => route($action['route']),
        ])
        ->values();
@endphp

<div
    x-data="commandPalette({ items: @js($paletteItems->concat($quickCreate)->values()->all()) })"
    x-on:palette:open.window="show()"
    x-on:keydown.escape.window="close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[85]"
    role="dialog"
    aria-modal="true"
    aria-label="Recherche et navigation rapide"
    x-trap.noscroll="open"
>
    <div class="absolute inset-0 bg-[var(--scrim)]" x-on:click="close()" aria-hidden="true"></div>

    <div class="material-overlay absolute inset-x-3 top-[10vh] mx-auto max-w-xl overflow-hidden rounded-xl">
        <div class="flex items-center gap-2 border-b border-line px-4">
            <x-ui.icon name="search" class="w-4 h-4 text-ink-3" />
            <input
                x-ref="input"
                x-model="query"
                x-on:input="index = 0; search()"
                x-on:keydown.down.prevent="move(1)"
                x-on:keydown.up.prevent="move(-1)"
                x-on:keydown.enter.prevent="run(items[index])"
                type="search"
                placeholder="Aller à une page, créer…"
                aria-label="Rechercher une page"
                class="h-12 w-full bg-transparent text-[15px] text-ink placeholder:text-ink-3 focus:outline-none"
            >
            <x-ui.kbd>Échap</x-ui.kbd>
        </div>

        <ul x-ref="list" class="max-h-[60vh] overflow-y-auto p-1.5" role="listbox" aria-label="Résultats">
            <template x-for="(item, i) in items" :key="item.url + item.label">
                <li
                    role="option"
                    :aria-selected="(i === index).toString()"
                    :data-active="(i === index).toString()"
                    x-on:mouseenter="index = i"
                >
                    <a
                        :href="item.url"
                        x-on:click="close()"
                        :class="i === index ? 'bg-hover text-ink' : 'text-ink-2'"
                        class="flex items-center gap-2.5 rounded-sm px-2.5 py-2 text-[13.5px] focus-visible:outline-none"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0 text-ink-3" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" />
                        </svg>
                        <span class="min-w-0 flex-1 truncate" x-text="item.label"></span>
                        <span class="shrink-0 text-[11.5px] text-ink-3" x-text="item.group || ''"></span>
                    </a>
                </li>
            </template>
            <li x-show="!hasResults" class="px-3 py-6 text-center text-[13px] text-ink-3">
                Aucune page ne correspond. Essayez « élèves », « factures », « planning ».
            </li>
        </ul>

        <p class="border-t border-line px-4 py-2 text-[11.5px] text-ink-3">
            ↑ ↓ pour naviguer · Entrée pour ouvrir · Échap pour fermer
        </p>
    </div>
</div>
