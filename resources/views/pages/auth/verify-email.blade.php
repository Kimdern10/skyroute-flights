<x-layouts::auth :title="__('Confirm your email')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Check your email')"
            :description="__('We sent a 6-digit code to :email. Enter it below to confirm your email address.', ['email' => auth()->user()->email])"
        />

        <livewire:verify-email-code />

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <flux:button variant="ghost" size="sm" type="submit" class="cursor-pointer" data-test="logout-button">
                {{ __('Use a different account') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
