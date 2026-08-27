<x-app-layout>
    <x-slot name="header">Évaluation - {{ $student->fullName() }}</x-slot>

    <div class="py-6 max-w-2xl mx-auto space-y-5">
        @if (session('status'))
            <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        @if ($skillsByCategory->isEmpty())
            <x-card>
                <p class="text-sm text-content-secondary">Aucune compétence définie pour cet établissement.</p>
            </x-card>
        @else
            @php $levelPercent = ['not_started' => 0, 'in_progress' => 50, 'acquired' => 100]; @endphp
            <form method="POST" action="{{ route('training.evaluation.store', $student) }}" class="space-y-5">
                @csrf
                @foreach ($skillsByCategory as $category => $skills)
                    @php $acquiredCount = $skills->filter(fn ($skill) => ($progress->get($skill->id)?->level?->value ?? 'not_started') === 'acquired')->count(); @endphp
                    <x-card :padded="false">
                        <div class="px-4 py-3 flex items-center justify-between border-b border-border/60">
                            <h2 class="text-sm font-semibold text-content">{{ $category }}</h2>
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
                                        <div class="flex gap-3 text-sm shrink-0">
                                            @foreach (\App\Domain\Training\Enums\SkillLevel::cases() as $level)
                                                <label class="flex items-center gap-1.5 text-content-secondary">
                                                    <input type="radio" name="levels[{{ $skill->id }}]" value="{{ $level->value }}" @checked($current === $level->value) class="text-primary focus:ring-primary">
                                                    {{ $level->label() }}
                                                </label>
                                            @endforeach
                                        </div>
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

                <div class="flex justify-end">
                    <x-primary-button>Enregistrer l'évaluation</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
