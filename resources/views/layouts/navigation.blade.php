<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @can('viewAny', \App\Domain\Students\Models\Student::class)
                        <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">
                            Élèves
                        </x-nav-link>
                    @endcan
                    @can('viewAny', \App\Domain\Finance\Models\Invoice::class)
                        <x-nav-link :href="route('finance.invoices.index')" :active="request()->routeIs('finance.invoices.*')">
                            Factures
                        </x-nav-link>
                        <x-nav-link :href="route('finance.packages.index')" :active="request()->routeIs('finance.packages.*')">
                            Forfaits
                        </x-nav-link>
                        <x-nav-link :href="route('finance.ledger.index')" :active="request()->routeIs('finance.ledger.*')">
                            Journal
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()?->hasRole('admin'))
                        <x-nav-link :href="route('scheduling.index')" :active="request()->routeIs('scheduling.index')">
                            Planning
                        </x-nav-link>
                        <x-nav-link :href="route('training.skills.index')" :active="request()->routeIs('training.skills.*')">
                            Compétences
                        </x-nav-link>
                        <x-nav-link :href="route('training.exams.index')" :active="request()->routeIs('training.exams.*')">
                            Examens
                        </x-nav-link>
                        <x-nav-link :href="route('fleet.index')" :active="request()->routeIs('fleet.*')">
                            Flotte
                        </x-nav-link>
                        <x-nav-link :href="route('store.products.index')" :active="request()->routeIs('store.*')">
                            Boutique
                        </x-nav-link>
                        <x-nav-link :href="route('crm.leads.index')" :active="request()->routeIs('crm.*')">
                            Prospects
                        </x-nav-link>
                    @endif
                    @if (Auth::user()?->hasAnyRole(['admin', 'superadmin']))
                        <x-nav-link :href="route('audit.index')" :active="request()->routeIs('audit.*')">
                            Audit
                        </x-nav-link>
                    @endif
                    @if (Auth::user()?->hasRole('superadmin'))
                        <x-nav-link :href="route('superadmin.structures.index')" :active="request()->routeIs('superadmin.*')">
                            Établissements
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2" x-data="{
                    open: false,
                    unread: 0,
                    items: [],
                    async load() {
                        const res = await fetch('{{ route('notifications.index') }}');
                        const data = await res.json();
                        this.unread = data.unread_count;
                        this.items = data.notifications;
                    },
                    async markRead() {
                        await fetch('{{ route('notifications.read') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        });
                        this.unread = 0;
                        this.items.forEach(i => i.read = true);
                    },
                 }" x-init="load(); setInterval(load, 60000)">
                <div class="relative">
                    <button @click="open = !open; if (open) markRead();" class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span x-show="unread > 0" x-text="unread" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"></span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 max-h-96 overflow-y-auto">
                        <template x-if="items.length === 0">
                            <div class="px-4 py-3 text-sm text-gray-500">Aucune notification.</div>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <a :href="item.link || '#'" class="block px-4 py-2 text-sm border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <div class="font-medium text-gray-900 dark:text-gray-100" x-text="item.title"></div>
                                <div class="text-gray-500" x-text="item.message"></div>
                                <div class="text-xs text-gray-400" x-text="item.created_at"></div>
                            </a>
                        </template>
                    </div>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
