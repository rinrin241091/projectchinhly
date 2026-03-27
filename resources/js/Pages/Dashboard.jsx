import React from 'react';
import { Head } from '@inertiajs/react';

export default function Dashboard({ totalRecords }) {
    return (
        <>
            <Head title="Dashboard" />
            <div style={{ minHeight: '100vh', background: '#f1f5f9', padding: 24 }}>
                <div style={{ maxWidth: 760, margin: '0 auto', background: '#fff', border: '1px solid #cbd5e1', borderRadius: 12, padding: 20 }}>
                    <h1 style={{ marginTop: 0 }}>Dashboard</h1>
                    <p>Tổng số hồ sơ lưu trữ hiện tại:</p>
                    <div style={{ fontSize: 32, fontWeight: 700 }}>{totalRecords}</div>
                </div>
            </div>
        </>
    );
}
