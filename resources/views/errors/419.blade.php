<x-error-page
    code="419"
    title="Session expirée"
    message="Votre session a expiré, probablement parce que vous êtes resté inactif trop longtemps. Veuillez réessayer."
>
    <x-slot name="actions">
        <a href="{{ url()->previous() === url()->current() ? '/' : url()->previous() }}" class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
            Réessayer
        </a>
    </x-slot>
</x-error-page>
