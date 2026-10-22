<x-public-layout :title="'Erreur interne'">
    <div class="mx-auto flex w-full max-w-2xl flex-col items-center px-4 py-16 text-center sm:px-6">
        <span class="grid size-16 place-items-center rounded-full bg-accent-soft text-accent">
            <x-ui.icon name="wrench" size="xl" />
        </span>

        <p class="mt-6 text-eyebrow">Erreur 500</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-ink sm:text-4xl">Erreur interne</h1>
        <p class="mt-3 max-w-xl text-[15px] font-medium text-ink-2">Une erreur inattendue est survenue de notre côté.</p>
        <p class="mt-2 max-w-xl text-[14px] leading-relaxed text-ink-3">L’incident a été enregistré. Réessayez dans quelques instants ; si le problème persiste, signalez-le avec l’heure de l’erreur.</p>

        <div class="mt-7 flex flex-wrap items-center justify-center gap-2">
            <x-ui.button :href="url()->current()" size="lg" icon-right="arrow-right">Réessayer</x-ui.button>
            <x-ui.button :href="route('dashboard')" variant="secondary" size="lg">Tableau de bord</x-ui.button>
        </div>

        <div class="mt-10 w-full border-t border-line pt-6">
            <p class="text-[12.5px] text-ink-3">
                Besoin d’aide ? Écrivez-nous à
                <a href="mailto:contact@auto-gestboard.com" class="font-medium text-accent hover:underline">contact@auto-gestboard.com</a>
                en précisant la page concernée et l’heure de l’erreur.
            </p>
        </div>
    </div>
</x-public-layout>
