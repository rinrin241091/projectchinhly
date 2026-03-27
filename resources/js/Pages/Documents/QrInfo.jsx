import React from 'react';
import { Head } from '@inertiajs/react';

export default function QrInfo({ documentName, recordName, boxName, shelfName }) {
    return (
        <>
            <Head title="Thông tin tài liệu" />
            <div style={{ minHeight: '100vh', background: '#f7f7f7', padding: 24 }}>
                <div style={{ maxWidth: 720, margin: '0 auto', background: '#fff', borderRadius: 16, border: '1px solid #e5e7eb', overflow: 'hidden' }}>
                    <div style={{ background: 'linear-gradient(135deg, #0b74c7, #0f5ea6)', color: '#fff', padding: 24 }}>
                        <h1 style={{ margin: 0 }}>Thông tin tài liệu</h1>
                        <p style={{ marginBottom: 0 }}>Thông tin hiển thị khi quét QR tài liệu.</p>
                    </div>
                    <div style={{ padding: 24 }}>
                        <InfoRow label="Tên tài liệu" value={documentName} />
                        <InfoRow label="Tên hồ sơ" value={recordName} />
                        <InfoRow label="Tên hộp" value={boxName} />
                        <InfoRow label="Tên kệ" value={shelfName} noBorder />
                    </div>
                </div>
            </div>
        </>
    );
}

function InfoRow({ label, value, noBorder = false }) {
    return (
        <div style={{ padding: '14px 0', borderBottom: noBorder ? 'none' : '1px solid #e5e7eb' }}>
            <div style={{ fontSize: 13, color: '#6b7280', textTransform: 'uppercase' }}>{label}</div>
            <div style={{ fontSize: 18, fontWeight: 600 }}>{value}</div>
        </div>
    );
}
