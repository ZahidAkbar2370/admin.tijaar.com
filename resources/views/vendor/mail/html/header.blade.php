@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel' || trim($slot) === config('app.name'))
<img src="{{ config('app.url') }}/images/tijaar-logo.png" class="logo" alt="Tijaar" style="max-height: 50px;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
