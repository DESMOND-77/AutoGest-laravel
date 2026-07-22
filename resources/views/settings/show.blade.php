<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Paramètres de l'établissement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('settings.update') }}" class="grid grid-cols-2 gap-4">
                    @csrf
                    @method('PATCH')
                    <div class="col-span-2">
                        <x-input-label for="display_name" value="Nom affiché" />
                        <x-text-input id="display_name" name="display_name" class="block mt-1 w-full" value="{{ $setting->display_name }}" />
                    </div>
                    <div class="col-span-2">
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
                    <div class="col-span-2">
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
