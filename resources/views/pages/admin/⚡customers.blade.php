<?php

use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Services\Wallet;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public ?int $adjustingId = null;

    public string $amount = '';

    public string $reason = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        $search = trim($this->search);

        return User::query()
            ->withCount('bookings')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(25);
    }

    public function startAdjust(int $id): void
    {
        $this->adjustingId = $id;
        $this->reset('amount', 'reason');
        $this->resetValidation();

        Flux::modal('adjust-wallet')->show();
    }

    public function adjust(Wallet $wallet): void
    {
        $this->validate([
            'amount' => ['required', 'numeric', 'not_in:0', 'min:-20000', 'max:20000'],
            'reason' => ['required', 'string', 'max:150'],
        ], [], ['amount' => 'amount']);

        $customer = User::query()->findOrFail($this->adjustingId);
        $cents = Money::toCents($this->amount);

        if ($customer->balance_cents + $cents < 0) {
            $this->addError('amount', 'This would take the balance below zero. The balance is '.$customer->balance().'.');

            return;
        }

        $wallet->change($customer, $cents, WalletTransactionType::Adjustment, $this->reason, by: Auth::user());

        Flux::modal('adjust-wallet')->close();
        Flux::toast(variant: 'success', text: "{$customer->name}'s balance is now {$customer->balance()}.");
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Customers</flux:heading>
        <flux:text>Everyone with an account, their wallet balance and bookings.</flux:text>
    </div>

    <div class="w-full sm:w-72"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name or email" /></div>

    <flux:table :paginate="$this->customers">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Bookings</flux:table.column>
            <flux:table.column>Balance</flux:table.column>
            <flux:table.column>Joined</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->customers as $customer)
                <flux:table.row :key="$customer->id">
                    <flux:table.cell class="font-semibold">{{ $customer->name }} @if ($customer->is_admin)<flux:badge size="sm">Admin</flux:badge>@endif</flux:table.cell>
                    <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->bookings_count }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->balance() }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->created_at->format('j M Y') }}</flux:table.cell>
                    <flux:table.cell><flux:button size="sm" wire:click="startAdjust({{ $customer->id }})">Adjust balance</flux:button></flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="adjust-wallet" class="md:w-96">
        <form wire:submit="adjust" class="flex flex-col gap-5">
            <flux:heading size="lg">Adjust wallet balance</flux:heading>
            <flux:input wire:model="amount" type="number" step="0.01" label="Amount (USD)" description="Use a minus sign to take money off, e.g. -20." icon="currency-dollar" />
            <flux:input wire:model="reason" label="Reason" description="The customer sees this on their statement." />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
