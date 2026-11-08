@props(['url'])
@php($business = \App\Models\Setting::get('business_name'))
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('icon-192.png') }}" class="logo" width="56" height="56" alt="{{ $business }} logo">
<span class="brand-name">{{ $business }}</span>
</a>
</td>
</tr>
