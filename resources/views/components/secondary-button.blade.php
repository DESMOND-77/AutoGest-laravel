<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-surface rounded-ui-md font-medium text-sm text-content-secondary shadow-soft-sm hover:shadow-soft hover:text-content active:shadow-inset focus:outline-none focus-visible:shadow-inset-focus disabled:opacity-50 disabled:pointer-events-none transition']) }}>
    {{ $slot }}
</button>
