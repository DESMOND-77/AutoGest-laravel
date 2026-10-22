<x-error-page
    code="422"
    title="Requête invalide"
    message="La requête n'a pas pu être traitée en raison de données incorrectes ou incomplètes."
>
    <x-slot name="actions">
        <a href="{{ url()->previous() === url()->current() ? '/' : url()->previous() }}" class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
            Retour
        </a>
    </x-slot>
</x-error-page>
