<?php

use App\Enums\TopUpStatus;
use App\Models\Setting;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Wallet')] class extends Component {
    use WithPagination;

    public string $amount = '';

    public string $paymentReference = '';

    public string $note = '';

    public function requestTopUp(): void
    {
        $validated = $this->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:20000'],
            'paymentReference' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['paymentReference' => 'payment reference']);

        $user = Auth::user();

        if ($user->topUpRequests()->where('status', TopUpStatus::Pending)->count() >= 3) {
            $this->addError('amount', 'You already have 3 top-ups waiting. Please wait for them to be checked.');

            return;
        }

        $user->topUpRequests()->create([
            'amount_cents' => Money::toCents($validated['amount']),
            'payment_reference' => $validated['paymentReference'],
            'note' => $validated['note'] ?: null,
            'status' => TopUpStatus::Pending,
        ]);

        $this->reset('amount', 'paymentReference', 'note');

        Flux::toast(variant: 'success', text: 'Thanks! We will add the money once we see your payment.');
    }

    #[Computed]
    public function transactions()
    {
        return Auth::user()->walletTransactions()->latest('id')->paginate(10);
    }

    #[Computed]
    public function topUps()
    {
        return Auth::user()->topUpRequests()->latest()->limit(5)->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Wallet</flux:heading>
        <flux:text>Tickets are paid from your wallet. Add money by bank transfer, then tell us about it here.</flux:text>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="flex flex-col gap-4 lg:col-span-2">
            <flux:card>
                <flux:text>Balance</flux:text>
                <div class="mt-1 text-4xl font-bold" data-test="balance">{{ auth()->user()->balance() }}</div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Top up</flux:heading>
                <div class="mt-3 whitespace-pre-line rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">{{ Setting::get('top_up_instructions') }}</div>
                <form wire:submit="requestTopUp" class="mt-4 flex flex-col gap-4">
                    <flux:input wire:model="amount" label="Amount sent (USD)" type="number" step="0.01" min="10" icon="currency-dollar" />
                    <flux:input wire:model="paymentReference" label="Payment reference" description="The reference or transaction ID from your bank." />
                    <flux:textarea wire:model="note" label="Note (optional)" rows="2" />
                    <flux:button type="submit" variant="primary">Send top-up request</flux:button>
                </form>
            </flux:card>
        </div>

        <div class="flex flex-col gap-4 lg:col-span-3">
            @if ($this->topUps->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg">Recent top-up requests</flux:heading>
                    <div class="mt-3 grid gap-2">
                        @foreach ($this->topUps as $topUp)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span>{{ $topUp->created_at->format('j M Y') }} · {{ Money::format($topUp->amount_cents) }} · {{ $topUp->payment_reference }}</span>
                                <flux:badge size="sm" :color="$topUp->status->color()">{{ $topUp->status->label() }}</flux:badge>
                            </div>
                            @if ($topUp->admin_note)
                                <flux:text size="sm" class="-mt-1">{{ $topUp->admin_note }}</flux:text>
                            @endif
                        @endforeach
                    </div>
                </flux:card>
            @endif

            <flux:card>
                <flux:heading size="lg">Statement</flux:heading>
                <flux:table :paginate="$this->transactions" class="mt-2">
                    <flux:table.columns>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Details</flux:table.column>
                        <flux:table.column align="end">Amount</flux:table.column>
                        <flux:table.column align="end">Balance</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->transactions as $transaction)
                            <flux:table.row :key="$transaction->id">
                                <flux:table.cell>{{ $transaction->created_at->format('j M Y') }}</flux:table.cell>
                                <flux:table.cell class="whitespace-normal">
                                    <flux:badge size="sm">{{ $transaction->type->label() }}</flux:badge>
                                    {{ $transaction->description }}
                                </flux:table.cell>
                                <flux:table.cell align="end" @class(['font-semibold', 'text-green-600' => $transaction->amount_cents > 0])>{{ ($transaction->amount_cents > 0 ? '+' : '').Money::format($transaction->amount_cents) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ Money::format($transaction->balance_after_cents) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">No transactions yet.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    </div>
</div>
