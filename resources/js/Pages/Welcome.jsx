import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Trang chủ" />
            <div style={{ minHeight: '100vh', background: 'linear-gradient(135deg, #f4f7fb, #eaf2ff)', padding: '24px' }}>
                <div style={{ maxWidth: 900, margin: '0 auto', background: '#fff', borderRadius: 16, border: '1px solid #dbe2ea', padding: 28 }}>
                    <h1 style={{ marginTop: 0 }}>DocManager - Inertia + React</h1>
                    <p>Backend vẫn do Laravel xử lý, frontend render bằng Inertia React theo kiểu SPA.</p>

                    <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                        <Link href="/admin" style={buttonStyle('#0f5ea6')}>Vào trang quản trị</Link>
                        <Link href="/dashboard/borrowings/pending-count-check" style={buttonStyle('#334155')}>Kiểm tra API mẫu</Link>
                    </div>

                    <div style={{ marginTop: 20, padding: 16, background: '#f8fafc', borderRadius: 12 }}>
                        <strong>Đăng nhập:</strong> {auth?.user ? auth.user.name : 'Chưa đăng nhập'}
                    </div>
                </div>
            </div>
        </>
    );
}

function buttonStyle(color) {
    return {
        display: 'inline-block',
        background: color,
        color: '#fff',
        padding: '10px 16px',
        borderRadius: 10,
        textDecoration: 'none',
        fontWeight: 600,
    };
}
