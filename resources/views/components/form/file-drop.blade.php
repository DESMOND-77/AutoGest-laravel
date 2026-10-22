@props(['name', 'id' => null, 'accept' => null, 'hint' => null, 'capture' => null, 'multiple' => false])

@php
    $fieldId = $id ?? $name;
    $hasError = $errors->has($name) || $errors->has($name.'.*');
@endphp

<div
    x-data="{
        dragging: false,
        files: [],
        setFiles(input) {
            this.files = Array.from(input.files || []).map((f) => ({ name: f.name, size: (f.size / 1024 / 1024).toFixed(2) }));
        }
    }"
    {{ $attributes->class(['space-y-2']) }}
>
    <label
        for="{{ $fieldId }}"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="dragging = false; $refs.input.files = $event.dataTransfer.files; setFiles($refs.input)"
        :class="dragging ? 'border-accent bg-accent-soft' : 'border-control bg-inset'"
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border border-dashed px-4 py-8 text-center transition-colors focus-within:shadow-[var(--focus-ring)]"
    >
        <x-ui.icon name="upload" class="w-6 h-6 text-ink-3" />
        <span class="text-sm font-medium text-ink">Déposer un fichier ou cliquer pour choisir</span>
        <span class="text-[12.5px] text-ink-3">
            {{ $hint ?? ($accept ? 'Formats acceptés : '.$accept : 'PDF, JPG ou PNG') }}
        </span>
        <input
            id="{{ $fieldId }}"
            x-ref="input"
            type="file"
            name="{{ $name }}"
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($capture) capture="{{ $capture }}" @endif
            @if ($multiple) multiple @endif
            @change="setFiles($event.target)"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            class="sr-only"
        >
    </label>

    <ul class="space-y-1" x-show="files.length" x-cloak>
        <template x-for="file in files" :key="file.name">
            <li class="flex items-center justify-between gap-2 rounded-sm bg-hover px-2.5 py-1.5 text-[13px] text-ink">
                <span class="truncate" x-text="file.name"></span>
                <span class="shrink-0 text-ink-3" x-text="file.size + ' Mo'"></span>
            </li>
        </template>
    </ul>
</div>
