@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="\App\Models\Setting::get('business_name')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-site-logo class="size-8" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="\App\Models\Setting::get('business_name')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-site-logo class="size-8" />
        </x-slot>
    </flux:brand>
@endif
