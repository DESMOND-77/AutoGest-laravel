<x-app-layout>
    <x-slot name="header">Ma progression</x-slot>

    <div class="py-6 max-w-2xl mx-auto space-y-5">
        @if ($skillsByCategory->isEmpty())
            <x-card>
                <p class="text-sm text-content-secondary">Aucune compétence définie pour votre établissement.</p>
            </x-card>
        @else
            <x-card>
                <div class="flex items-center justify-between text-xs text-content-muted mb-1">
                    <span>Progression globale</span>
                    <span>{{ $overallPercent }}%</span>
                </div>
                <div class="bg-surface-inset rounded-full h-2 overflow-hidden">
                    <div class="bg-primary h-2 rounded-full" style="width: {{ max(2, $overallPercent) }}%"></div>
                </div>
            </x-card>

            @php $levelPercent = ['not_started' => 0, 'in_progress' => 50, 'acquired' => 100]; @endphp
            @foreach ($skillsByCategory as $category => $skills)
                @php $acquiredCount = $skills->filter(fn ($skill) => ($progress->get($skill->id)?->level?->value ?? 'not_started') === 'acquired')->count(); @endphp
                <x-card :padded="false">
                    <div class="px-4 py-3 flex items-center justify-between border-b border-border/60">
                        <h2 class="text-sm font-semibold text-content">{{ $category ?: 'Sans catégorie' }}</h2>
                        <span class="text-xs font-medium text-content-secondary">{{ $acquiredCount }}/{{ $skills->count() }} acquises</span>
                    </div>
                    <div class="divide-y divide-border/60">
                        @foreach ($skills as $skill)
                            @php $current = $progress->get($skill->id)?->level?->value ?? 'not_started'; @endphp
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-4 mb-2">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-content">{{ $skill->label }}</div>
                                        @if ($current === 'acquired' && $progress->get($skill->id)?->validated_at)
                                            <div class="text-xs text-success mt-0.5">Validé le {{ $progress->get($skill->id)->validated_at->format('d/m/Y') }}</div>
                                        @endif
                                    </div>
                                    <span @class([
                                        'text-xs font-semibold px-2 py-0.5 rounded-full shrink-0',
                                        'bg-surface-inset text-content-muted' => $current === 'not_started',
                                        'bg-warning/10 text-warning' => $current === 'in_progress',
                                        'bg-success/10 text-success' => $current === 'acquired',
                                    ])>{{ \App\Domain\Training\Enums\SkillLevel::from($current)->label() }}</span>
                                </div>
                                <div class="bg-surface-inset rounded-full h-1.5 overflow-hidden">
                                    <div @class([
                                        'h-1.5 rounded-full',
                                        'bg-content-muted' => $current === 'not_started',
                                        'bg-warning' => $current === 'in_progress',
                                        'bg-success' => $current === 'acquired',
                                    ]) style="width: {{ max(2, $levelPercent[$current]) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        @endif
    </div>
</x-app-layout>
