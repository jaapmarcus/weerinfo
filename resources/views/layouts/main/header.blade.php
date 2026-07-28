<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-mist">
        <flux:header container class="border-b border-hairline bg-mist/90 backdrop-blur-sm">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('home') }}" wire:navigate />

            <form method="GET" action="{{ route('search') }}" class="ms-4 max-lg:hidden">
                <flux:input
                    name="q"
                    type="search"
                    icon="magnifying-glass"
                    size="sm"
                    placeholder="{{ __('Zoek een plaats...') }}"
                    class="w-56"
                />
            </form>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:navbar.item class="h-10 max-lg:hidden" :href="route('blog.index')" :current="request()->routeIs('blog.*')" wire:navigate>
                    {{ __('Blog') }}
                </flux:navbar.item>
            </flux:navbar>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-hairline bg-mist">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <form method="GET" action="{{ route('search') }}" class="px-4 pt-4">
                <flux:input
                    name="q"
                    type="search"
                    icon="magnifying-glass"
                    size="sm"
                    placeholder="{{ __('Zoek een plaats...') }}"
                />
            </form>

            <flux:sidebar.nav>
                <flux:sidebar.group>
                    <flux:sidebar.item icon="newspaper" :href="route('blog.index')" :current="request()->routeIs('blog.*')" wire:navigate>
                        {{ __('Blog') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>

            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
