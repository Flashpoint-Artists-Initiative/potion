<table class="helptext" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
@isset($slot)
<p>
{{ Illuminate\Mail\Markdown::parse($slot) }}
</p>
@endisset
<p>
Got website questions? Something not working? Email <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a> for help.
</p>
</td>
</tr>
</table>
