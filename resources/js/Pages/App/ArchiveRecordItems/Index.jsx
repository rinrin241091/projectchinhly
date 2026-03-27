import React, { useMemo, useState } from 'react';
import axios from 'axios';
import { Head, router } from '@inertiajs/react';

export default function Index({ filters, items, selectedOrganizationId, availableOrganizations }) {
    const [search, setSearch] = useState(filters?.q ?? '');
    const [perPage, setPerPage] = useState(String(filters?.per_page ?? 10));
    const [isSwitching, setIsSwitching] = useState(false);

    const canPrev = Boolean(items?.links?.prev);
    const canNext = Boolean(items?.links?.next);

    const paginationText = useMemo(() => {
        const meta = items?.meta;

        if (!meta || meta.total === 0) {
            return 'Khong co du lieu';
        }

        return `Hien thi tu ${meta.from} den ${meta.to} trong ${meta.total} ket qua`;
    }, [items?.meta]);

    function applyFilters(event) {
        event.preventDefault();

        router.get('/app/archive-record-items', {
            q: search || undefined,
            per_page: Number(perPage),
        }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['filters', 'items'],
        });
    }

    function goTo(url) {
        if (!url) {
            return;
        }

        router.visit(url, {
            preserveScroll: true,
            preserveState: true,
            only: ['filters', 'items'],
        });
    }

    async function changeOrganization(orgId) {
        if (!orgId) {
            return;
        }

        setIsSwitching(true);

        try {
            const { data } = await axios.post('/change-organization', {
                organization_id: Number(orgId),
            });

            if (data?.success) {
                router.reload({
                    only: ['items', 'selectedOrganizationId'],
                    preserveScroll: true,
                    preserveState: true,
                });
            }
        } catch (error) {
            // keep simple UX for now
            alert('Khong the chuyen don vi luu tru.');
        } finally {
            setIsSwitching(false);
        }
    }

    return (
        <>
            <Head title="Muc luc ho so" />

            <div style={{ minHeight: '100vh', background: '#f1f5f9', padding: 24 }}>
                <div style={{ maxWidth: 1300, margin: '0 auto' }}>
                    <h1 style={{ marginTop: 0 }}>Muc Luc Ho So (Inertia + Server Side)</h1>

                    <div style={{ background: '#fff', border: '1px solid #dbe2ea', borderRadius: 12, padding: 14, marginBottom: 14 }}>
                        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'end' }}>
                            <label style={{ minWidth: 260 }}>
                                <div style={labelStyle}>Don vi luu tru</div>
                                <select
                                    value={selectedOrganizationId ?? ''}
                                    onChange={(event) => changeOrganization(event.target.value)}
                                    disabled={isSwitching}
                                    style={inputStyle}
                                >
                                    <option value="">Chon don vi</option>
                                    {(availableOrganizations || []).map((org) => (
                                        <option key={org.id} value={org.id}>{org.name}</option>
                                    ))}
                                </select>
                            </label>

                            <form onSubmit={applyFilters} style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'end' }}>
                                <label>
                                    <div style={labelStyle}>Tim kiem</div>
                                    <input
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Ma muc luc, ten muc luc..."
                                        style={inputStyle}
                                    />
                                </label>

                                <label>
                                    <div style={labelStyle}>Moi trang</div>
                                    <select value={perPage} onChange={(event) => setPerPage(event.target.value)} style={inputStyle}>
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </label>

                                <button type="submit" style={btn('#0f62a8')}>Loc</button>
                            </form>
                        </div>
                    </div>

                    <div style={{ background: '#fff', border: '1px solid #dbe2ea', borderRadius: 12, overflow: 'hidden' }}>
                        <div style={{ overflowX: 'auto' }}>
                            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                <thead>
                                    <tr>
                                        <Th>ID</Th>
                                        <Th>Ma muc luc</Th>
                                        <Th>Ten muc luc</Th>
                                        <Th>Phong</Th>
                                        <Th>Nam ho so</Th>
                                        <Th>Ghi chu</Th>
                                        <Th>Thao tac</Th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(items?.data || []).length > 0 ? (items.data.map((item) => (
                                        <tr key={item.id}>
                                            <Td>{item.id}</Td>
                                            <Td>{item.archive_record_item_code}</Td>
                                            <Td>{item.title}</Td>
                                            <Td>{item.organization_name}</Td>
                                            <Td>{item.document_date}</Td>
                                            <Td>{item.description}</Td>
                                            <Td>
                                                <a href={item.edit_url} style={linkStyle('#d97706')}>Sua</a>
                                                <a href={item.view_url} style={{ ...linkStyle('#0f62a8'), marginLeft: 10 }} target="_blank" rel="noreferrer">Xem muc luc</a>
                                            </Td>
                                        </tr>
                                    ))) : (
                                        <tr>
                                            <Td colSpan={7}>Khong co du lieu phu hop.</Td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: 12, borderTop: '1px solid #e5e7eb', flexWrap: 'wrap', gap: 8 }}>
                            <span>{paginationText}</span>
                            <div style={{ display: 'flex', gap: 8 }}>
                                <button onClick={() => goTo(items?.links?.prev)} disabled={!canPrev} style={btn('#475569')}>Trang truoc</button>
                                <button onClick={() => goTo(items?.links?.next)} disabled={!canNext} style={btn('#475569')}>Trang sau</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function Th({ children, colSpan }) {
    return <th colSpan={colSpan} style={{ border: '1px solid #e5e7eb', background: '#f8fafc', padding: 10, textAlign: 'left' }}>{children}</th>;
}

function Td({ children, colSpan }) {
    return <td colSpan={colSpan} style={{ border: '1px solid #e5e7eb', padding: 10 }}>{children}</td>;
}

const inputStyle = {
    border: '1px solid #cbd5e1',
    borderRadius: 8,
    padding: '8px 10px',
    minWidth: 180,
};

const labelStyle = {
    marginBottom: 6,
    fontSize: 13,
    fontWeight: 600,
};

function btn(background) {
    return {
        background,
        color: '#fff',
        border: 'none',
        borderRadius: 8,
        padding: '8px 12px',
        cursor: 'pointer',
    };
}

function linkStyle(color) {
    return {
        color,
        textDecoration: 'none',
        fontWeight: 600,
    };
}
