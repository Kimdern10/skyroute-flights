@php($site = \App\Models\Setting::values())
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $site['business_name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
Need help? Email [{{ $site['support_email'] }}](mailto:{{ $site['support_email'] }}) or call {{ $site['support_phone'] }}.

© {{ date('Y') }} {{ $site['business_name'] }}. Book flights worldwide in minutes.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
