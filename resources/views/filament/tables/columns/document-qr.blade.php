@php
    $qrText = $getRecord()->getQrTextPayload();
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . rawurlencode($qrText);
    $previewUrl = route('documents.qr-preview', ['document' => $getRecord()->id]);
@endphp

<div style="display:flex; flex-direction:column; align-items:center; gap:6px; min-width:92px;">
    <img
        src="{{ $qrUrl }}"
        alt="QR tài liệu"
        style="width:82px; height:82px; border:1px solid #e5e7eb; border-radius:6px; padding:4px; background:#fff;"
    >
    <a href="{{ $previewUrl }}" target="_blank" style="font-size:12px; font-weight:600; color:#0f5ea6; text-align:center; text-decoration:none;">
        Mở lớn
    </a>
</div>
