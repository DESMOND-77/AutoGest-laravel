<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-danger rounded-ui-md font-semibold text-sm text-white shadow-soft-sm hover:shadow-soft active:shadow-inset focus:outline-none focus-visible:shadow-inset-focus disabled:opacity-50 disabled:pointer-events-none transition']) }}>
    {{ $slot }}
</button>
