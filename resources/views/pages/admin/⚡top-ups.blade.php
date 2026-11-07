<?php

use App\Actions\ReviewTopUp;
use App\Enums\TopUpStatus;
use App\Models\TopUpRequest;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Top-ups')] class extends Component {
    use WithPagination;

    #[Url(except: 'pending')]
    public string $status = 'pending';

    /** @var array<int, string> */
    public array $notes = [];

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return TopUpRequest::query()
            ->with(['user', 'reviewer'])
            ->when(TopUpStatus::tryFrom($this->status), fn ($query, TopUpStatus $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }

    public function approve(int $id, ReviewTopUp $review): void
    {
        $this->review($id, TopUpStatus::Approved, $review);
    }

    public function reject(int $id, ReviewTopUp $review): void
    {
        $this->review($id, TopUpStatus::Rejected, $review);
    }

    /**
     * Bank transfers only: card payments are approved by Paystack's confirmation, never by hand.
     */
    private function review(int $id, TopUpStatus $decision, ReviewTopUp $review): void
    {
        abort_if(TopUpRequest::query()->findOrFail($id)->isCard(), 403);

        $done = $review->handle($id, $decision, Auth::user(), trim($this->notes[$id] ?? ''));

        unset($this->notes[$id]);

        Flux::toast(
            variant: $done ? 'success' : 'warning',
            text: $done ? ($decision === TopUpStatus::Approved ? Money::format($done->amount_cents).' added to the wallet.' : 'Top-up rejected.') : 'This top-up was already checked.',
        );
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Top-ups</flux:heading>
        <flux:text>Check that the money arrived in your bank, then approve it to add it to the customer's wallet.</flux:text>
    </div>

    <div class="w-full sm:w-48">
        <flux:select wire:model.live="status">
            <flux:select.option value="pending">Waiting</flux:select.option>
            <flux:select.option value="approved">Approved</flux:select.option>
            <flux:select.option value="rejected">Rejected</flux:select.option>
            <flux:select.option value="all">All</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->requests">
        <flux:table.columns>
            <flux:table.column>Requested</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Payment reference</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->requests as $request)
                <flux:table.row :key="$request->id">
                    <flux:table.cell>{{ $request->created_at->format('j M Y, H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $request->user->name }}<div class="text-xs text-zinc-500">{{ $request->user->email }}</div></flux:table.cell>
                    <flux:table.cell class="font-semibold">{{ Money::format($request->amount_cents) }}</flux:table.cell>
                    <flux:table.cell class="whitespace-normal"><flux:badge size="sm" :icon="$request->isCard() ? 'credit-card' : 'building-library'">{{ $request->isCard() ? 'Card' : 'Bank' }}</flux:badge> {{ $request->payment_reference }}@if ($request->note)<div class="text-xs text-zinc-500">{{ $request->note }}</div>@endif</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$request->status->color()">{{ $request->status->label() }}</flux:badge>
                        @if ($request->reviewer)
                            <div class="text-xs text-zinc-500">{{ $request->reviewer->name }}, {{ $request->reviewed_at?->format('j M') }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($request->status === TopUpStatus::Pending && $request->isCard())
                            <span class="text-xs text-zinc-500">Waiting for the card payment</span>
                        @elseif ($request->status === TopUpStatus::Pending)
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-40"><flux:input size="sm" wire:model="notes.{{ $request->id }}" placeholder="Note (optional)" /></div>
                                <flux:button size="sm" variant="primary" wire:click="approve({{ $request->id }})" wire:confirm="Add {{ Money::format($request->amount_cents) }} to {{ $request->user->name }}'s wallet?">Approve</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="reject({{ $request->id }})">Reject</flux:button>
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="6">Nothing here.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
