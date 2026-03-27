import React from 'react';
import { Head } from '@inertiajs/react';

export default function QrPreview({ qrText, qrUrl }) {
    return (
        <>
            <Head title="QR tài liệu" />
            <div style={{ minHeight: '100vh', background: '#f3f4f6', padding: 24 }}>
                <div style={{ maxWidth: 720, margin: '0 auto', background: '#fff', borderRadius: 16, border: '1px solid #e5e7eb', padding: 24, textAlign: 'center' }}>
                    <h1 style={{ marginTop: 0 }}>QR tài liệu</h1>
                    <p>Mở màn hình này để quét QR lớn, ổn định hơn so với QR nhỏ trong bảng.</p>
                    <img src={qrUrl} alt="QR tài liệu" style={{ width: 320, height: 320, border: '1px solid #d1d5db', borderRadius: 12, padding: 12, background: '#fff' }} />
                    <pre style={{ textAlign: 'left', marginTop: 20, background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: 12, padding: 16, whiteSpace: 'pre-wrap' }}>
                        {qrText}
                    </pre>
                </div>
            </div>
        </>
    );
}
