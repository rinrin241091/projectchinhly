@php
    $user = filament()->auth()->user();
@endphp

<x-filament::dropdown
    placement="bottom-end"
    teleport
    class="fi-user-menu"
>
    <x-slot name="trigger">
        <button
            aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
            type="button"
            class="fi-user-menu-trigger group flex w-full items-center justify-center gap-x-3 rounded-lg p-2 text-sm font-medium text-gray-950 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:text-white dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
        >
            <x-filament-panels::avatar.user :user="$user" />

            <span
                @if (filament()->isSidebarCollapsibleOnDesktop())
                    x-show="$store.sidebar.isOpen"
                @endif
                class="truncate"
            >
                {{ $user->name }} ({{ $user->email }})
            </span>
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @if ($user instanceof \Filament\Models\Contracts\HasAvatar)
            <x-filament::dropdown.header
                :color="$user->getFilamentAvatarColor()"
                :icon="$user->getFilamentAvatarIcon()"
                :image="$user->getFilamentAvatarUrl()"
                :label="$user->getFilamentName()"
            />
        @else
            <x-filament::dropdown.header
                color="gray"
                icon="heroicon-m-user-circle"
                :label="$user->name"
            />
        @endif

        @foreach (filament()->getUserMenuItems() as $item)
            <x-filament::dropdown.list.item
                :color="$item->getColor()"
                :href="$item->getUrl()"
                :icon="$item->getIcon()"
                :tag="$item->getUrl() ? 'a' : 'button'"
            >
                {{ $item->getLabel() }}
            </x-filament::dropdown.list.item>
        @endforeach

        <x-filament::dropdown.list.item
            color="gray"
            :action="filament()->getLogoutUrl()"
            icon="heroicon-m-arrow-left-on-rectangle"
            method="post"
            tag="form"
        >
            {{ __('filament-panels::layout.actions.logout.label') }}
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>