<x-error-page
    code="403"
    title="Accès refusé"
    message="Vous n'avez pas la permission d'accéder à cette page. Si vous pensez qu'il s'agit d'une erreur, contactez votre administrateur."
>
    <x-slot name="actions">
        <a href="{{ url()->previous() === url()->current() ? '/' : url()->previous() }}" class="rounded-ui-md bg-surface-inset px-4 py-2.5 text-sm font-medium text-content-secondary hover:text-content shadow-soft-sm hover:shadow-soft transition">
            Retour
        </a>
        <a href="/" class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
            Accueil
        </a>
    </x-slot>
</x-error-page>
