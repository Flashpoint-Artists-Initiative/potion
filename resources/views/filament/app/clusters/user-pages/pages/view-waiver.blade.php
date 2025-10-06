<p>
    {!! str($waiver->content)->sanitizeHtml() !!}
</p>
<p>
    Signed: <span class="italic">{{ $completedWaiver->form_data['signature'] }}</span>
</p>
<p>
    Date: {{ $completedWaiver->created_at->format('F j, Y, g:i a') }}
</p>