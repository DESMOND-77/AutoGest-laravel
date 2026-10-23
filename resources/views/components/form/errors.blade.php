@props(['title' => 'Veuillez corriger les erreurs suivantes'])

@if ($errors->any())
    <div
        {{ $attributes->class(['rounded-md border border-danger/40 bg-danger-soft p-4 text-sm text-danger']) }}
        role="alert"
        tabindex="-1"
        x-data
        x-init="$nextTick(() => $el.focus())"
    >
        <p class="flex items-center gap-2 font-semibold">
            <x-ui.icon name="circle-alert" class="w-4 h-4" />
            {{ $title }}
        </p>
        <ul class="mt-2 space-y-1 pl-6">
            @foreach ($errors->all() as $error)
                <li class="list-disc">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
