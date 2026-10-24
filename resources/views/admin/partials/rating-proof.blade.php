@if ($rating->attachments->isNotEmpty())
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0;">
        @foreach ($rating->attachments as $proof)
            @if ($proof->isVideo())
                <video src="{{ $proof->url }}" controls preload="metadata" style="width:220px;max-width:100%;border-radius:8px;background:#000;"></video>
            @else
                <a href="{{ $proof->url }}" target="_blank" rel="noopener">
                    <img src="{{ $proof->url }}" alt="Proof photo" style="width:96px;height:96px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">
                </a>
            @endif
        @endforeach
    </div>
@endif
