<x-app-layout>
    <x-slot name="header">Paramètres de l'établissement</x-slot>

    <div class="py-6 max-w-2xl mx-auto">
        @if (session('status'))
            <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <form method="POST" action="{{ route('settings.update') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                @method('PATCH')
                <div class="sm:col-span-2">
                    <x-input-label for="display_name" value="Nom affiché" />
                    <x-text-input id="display_name" name="display_name" class="block mt-1 w-full" value="{{ $setting->display_name }}" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="address" value="Adresse" />
                    <x-text-input id="address" name="address" class="block mt-1 w-full" value="{{ $setting->address }}" />
                </div>
                <div>
                    <x-input-label for="phone" value="Téléphone" />
                    <x-text-input id="phone" name="phone" class="block mt-1 w-full" value="{{ $setting->phone }}" />
                </div>
                <div>
                    <x-input-label for="support_email" value="E-mail" />
                    <x-text-input id="support_email" type="email" name="support_email" class="block mt-1 w-full" value="{{ $setting->support_email }}" />
                </div>
                <div>
                    <x-input-label for="timezone" value="Fuseau horaire" />
                    <x-text-input id="timezone" name="timezone" class="block mt-1 w-full" value="{{ $setting->timezone }}" />
                </div>
                <div>
                    <x-input-label for="currency" value="Devise" />
                    <x-text-input id="currency" name="currency" class="block mt-1 w-full" value="{{ $setting->currency }}" />
                </div>
                <div class="sm:col-span-2">
                    <x-primary-button>Enregistrer</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
