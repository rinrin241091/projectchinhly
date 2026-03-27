import React, { useState, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';

export default function ReportSummary({ organizations, reportRows, reportTotals, appliedFilters, cached }) {
    const [filters, setFilters] = useState({
        date_from: appliedFilters.date_from || '',
        date_to: appliedFilters.date_to || '',
        org_id: appliedFilters.org_id || '',
    });
    const [loading, setLoading] = useState(false);

    const handleFilterChange = (e) => {
        const { name, value } = e.target;
        setFilters(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        
        const queryParams = {};
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                queryParams[key] = filters[key];
            }
        });
        
        router.get(route('report.summary.index'), queryParams, {
            preserveScroll: true,
            onFinish: () => setLoading(false),
        });
    };

    const handleExportExcel = useCallback(() => {
        const queryParams = new URLSearchParams();
        if (filters.date_from) queryParams.append('date_from', filters.date_from);
        if (filters.date_to) queryParams.append('date_to', filters.date_to);
        if (filters.org_id) queryParams.append('org_id', filters.org_id);
        
        window.open(route('report.summary.excel', Object.fromEntries(queryParams)));
    }, [filters]);

    const handleExportPdf = useCallback(() => {
        const queryParams = new URLSearchParams();
        if (filters.date_from) queryParams.append('date_from', filters.date_from);
        if (filters.date_to) queryParams.append('date_to', filters.date_to);
        if (filters.org_id) queryParams.append('org_id', filters.org_id);
        
        window.open(route('report.summary.pdf', Object.fromEntries(queryParams)));
    }, [filters]);

    const formatNumber = useCallback((num) => {
        return new Intl.NumberFormat('vi-VN').format(num);
    }, []);

    const formatDate = useCallback((dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('vi-VN');
    }, []);

    return (
        <>
            <Head title="Báo cáo tổng hợp khối lượng chỉnh lý" />
            
            <div style={{ minHeight: '100vh', background: '#f1f5f9', padding: 24 }}>
                <div style={{ maxWidth: 1200, margin: '0 auto' }}>
                    
                    {/* Criteria Card */}
                    <div style={{
                        background: '#fff',
                        border: '1px solid #e2e8f0',
                        borderRadius: 12,
                        padding: 24,
                        marginBottom: 24,
                        boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
                    }}>
                        <div style={{ marginBottom: 16 }}>
                            <h2 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 600, color: '#1e293b' }}>
                                Tiêu chí báo cáo
                            </h2>
                            <p style={{ margin: 0, fontSize: 14, color: '#64748b' }}>
                                Chọn phạm vi thời gian và phông để tổng hợp số liệu chỉnh lý.
                            </p>
                            {cached && (
                                <p style={{ margin: '8px 0 0 0', fontSize: 12, color: '#10b981', fontWeight: 500 }}>
                                    ✓ Dữ liệu được lưu trong bộ nhớ tạm 
                                </p>
                            )}
                        </div>

                        <form onSubmit={handleSubmit}>
                            <div style={{
                                display: 'grid',
                                gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
                                gap: 16,
                                marginBottom: 16
                            }}>
                                {/* Date From */}
                                <div>
                                    <label style={{ display: 'block', fontSize: 14, fontWeight: 500, marginBottom: 4, color: '#334155' }}>
                                        Từ ngày chỉnh lý
                                    </label>
                                    <input
                                        type="date"
                                        name="date_from"
                                        value={filters.date_from}
                                        onChange={handleFilterChange}
                                        style={{
                                            width: '100%',
                                            padding: '8px 12px',
                                            borderRadius: 6,
                                            border: '1px solid #e2e8f0',
                                            fontSize: 14,
                                            boxSizing: 'border-box',
                                            fontFamily: 'inherit'
                                        }}
                                    />
                                </div>

                                {/* Date To */}
                                <div>
                                    <label style={{ display: 'block', fontSize: 14, fontWeight: 500, marginBottom: 4, color: '#334155' }}>
                                        Đến ngày chỉnh lý
                                    </label>
                                    <input
                                        type="date"
                                        name="date_to"
                                        value={filters.date_to}
                                        onChange={handleFilterChange}
                                        style={{
                                            width: '100%',
                                            padding: '8px 12px',
                                            borderRadius: 6,
                                            border: '1px solid #e2e8f0',
                                            fontSize: 14,
                                            boxSizing: 'border-box',
                                            fontFamily: 'inherit'
                                        }}
                                    />
                                </div>

                                {/* Organization Select */}
                                <div>
                                    <label style={{ display: 'block', fontSize: 14, fontWeight: 500, marginBottom: 4, color: '#334155' }}>
                                        Phông lưu trữ
                                    </label>
                                    <select
                                        name="org_id"
                                        value={filters.org_id}
                                        onChange={handleFilterChange}
                                        style={{
                                            width: '100%',
                                            padding: '8px 12px',
                                            borderRadius: 6,
                                            border: '1px solid #e2e8f0',
                                            fontSize: 14,
                                            boxSizing: 'border-box',
                                            fontFamily: 'inherit',
                                            background: '#fff'
                                        }}
                                    >
                                        <option value="">Tất cả phông</option>
                                        {organizations.map(org => (
                                            <option key={org.id} value={org.id}>
                                                {org.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div style={{
                                display: 'flex',
                                gap: 8,
                                flexWrap: 'wrap'
                            }}>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    style={{
                                        padding: '8px 16px',
                                        background: loading ? '#cbd5e1' : '#3b82f6',
                                        color: '#fff',
                                        border: 'none',
                                        borderRadius: 6,
                                        fontSize: 14,
                                        fontWeight: 500,
                                        cursor: loading ? 'not-allowed' : 'pointer',
                                        transition: 'background 0.2s',
                                        opacity: loading ? 0.7 : 1
                                    }}
                                    onMouseEnter={(e) => !loading && (e.target.style.background = '#2563eb')}
                                    onMouseLeave={(e) => !loading && (e.target.style.background = '#3b82f6')}
                                >
                                    {loading ? 'Đang tải...' : 'Xem báo cáo'}
                                </button>

                                {reportRows && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={handleExportExcel}
                                            style={{
                                                padding: '8px 16px',
                                                background: '#10b981',
                                                color: '#fff',
                                                border: 'none',
                                                borderRadius: 6,
                                                fontSize: 14,
                                                fontWeight: 500,
                                                cursor: 'pointer',
                                                transition: 'background 0.2s'
                                            }}
                                            onMouseEnter={(e) => e.target.style.background = '#059669'}
                                            onMouseLeave={(e) => e.target.style.background = '#10b981'}
                                        >
                                            Xuất Excel
                                        </button>

                                        <button
                                            type="button"
                                            onClick={handleExportPdf}
                                            style={{
                                                padding: '8px 16px',
                                                background: '#6b7280',
                                                color: '#fff',
                                                border: 'none',
                                                borderRadius: 6,
                                                fontSize: 14,
                                                fontWeight: 500,
                                                cursor: 'pointer',
                                                transition: 'background 0.2s'
                                            }}
                                            onMouseEnter={(e) => e.target.style.background = '#4b5563'}
                                            onMouseLeave={(e) => e.target.style.background = '#6b7280'}
                                        >
                                            In báo cáo (PDF)
                                        </button>
                                    </>
                                )}
                            </div>
                        </form>
                    </div>

                    {/* Report Table */}
                    {reportRows !== null && (
                        <div style={{
                            background: '#fff',
                            border: '1px solid #e2e8f0',
                            borderRadius: 12,
                            overflow: 'hidden',
                            boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
                        }}>
                            {/* Header */}
                            <div style={{
                                padding: '16px 24px',
                                borderBottom: '1px solid #e2e8f0',
                                background: '#f8fafc'
                            }}>
                                <h2 style={{
                                    margin: '0 0 8px 0',
                                    fontSize: 16,
                                    fontWeight: 600,
                                    color: '#1e293b',
                                    textTransform: 'uppercase'
                                }}>
                                    BÁO CÁO TỔNG HỢP KHỐI LƯỢNG CHỈNH LÝ HỒ SƠ
                                </h2>
                                {(filters.date_from || filters.date_to) && (
                                    <p style={{ margin: 0, fontSize: 13, color: '#64748b' }}>
                                        Giai đoạn:
                                        {filters.date_from && ` Từ ${formatDate(filters.date_from)}`}
                                        {filters.date_to && ` đến ${formatDate(filters.date_to)}`}
                                    </p>
                                )}
                                {!filters.date_from && !filters.date_to && (
                                    <p style={{ margin: 0, fontSize: 13, color: '#cbd5e1' }}>
                                        Tổng hợp toàn bộ thời gian
                                    </p>
                                )}
                            </div>

                            {/* Table */}
                            <div style={{ overflowX: 'auto' }}>
                                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                                    <thead>
                                        <tr style={{
                                            background: '#f1f5f9',
                                            borderBottom: '1px solid #e2e8f0'
                                        }}>
                                            <th style={{ padding: 12, textAlign: 'center', fontWeight: 600, color: '#475569', width: 50 }}>STT</th>
                                            <th style={{ padding: 12, textAlign: 'left', fontWeight: 600, color: '#475569' }}>Tên phông lưu trữ</th>
                                            <th style={{ padding: 12, textAlign: 'center', fontWeight: 600, color: '#475569', whiteSpace: 'nowrap' }}>Số hồ sơ<br/>chỉnh lý</th>
                                            <th style={{ padding: 12, textAlign: 'center', fontWeight: 600, color: '#475569', whiteSpace: 'nowrap' }}>Số văn bản<br/>/tài liệu</th>
                                            <th style={{ padding: 12, textAlign: 'center', fontWeight: 600, color: '#475569' }}>Số hộp</th>
                                            <th style={{ padding: 12, textAlign: 'center', fontWeight: 600, color: '#475569', whiteSpace: 'nowrap' }}>Tổng số trang</th>
                                            <th style={{ padding: 12, textAlign: 'center', fontWeight: 600, color: '#475569', whiteSpace: 'nowrap' }}>Mét giá (m)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {reportRows.length > 0 ? (
                                            reportRows.map((row, idx) => (
                                                <tr
                                                    key={idx}
                                                    style={{
                                                        borderBottom: '1px solid #f1f5f9',
                                                        transition: 'background 0.15s',
                                                        background: idx % 2 === 0 ? '#fff' : '#f8fafc'
                                                    }}
                                                    onMouseEnter={(e) => e.currentTarget.style.background = '#f0f9ff'}
                                                    onMouseLeave={(e) => e.currentTarget.style.background = idx % 2 === 0 ? '#fff' : '#f8fafc'}
                                                >
                                                    <td style={{ padding: 12, textAlign: 'center', color: '#64748b' }}>{row.stt}</td>
                                                    <td style={{ padding: 12, color: '#1e293b', fontWeight: 500 }}>{row.name}</td>
                                                    <td style={{ padding: 12, textAlign: 'center', color: '#475569' }}>
                                                        {formatNumber(row.records_count)}
                                                    </td>
                                                    <td style={{ padding: 12, textAlign: 'center', color: '#475569' }}>
                                                        {formatNumber(row.documents_count)}
                                                    </td>
                                                    <td style={{ padding: 12, textAlign: 'center', color: '#475569' }}>
                                                        {formatNumber(row.boxes_count)}
                                                    </td>
                                                    <td style={{ padding: 12, textAlign: 'center', color: '#475569' }}>
                                                        {formatNumber(row.total_pages)}
                                                    </td>
                                                    <td style={{ padding: 12, textAlign: 'center', color: '#475569' }}>
                                                        {formatNumber(Math.round(row.met_gia * 1000) / 1000)}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="7" style={{ padding: 40, textAlign: 'center', color: '#cbd5e1' }}>
                                                    Không có dữ liệu phù hợp với tiêu chí đã chọn.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                    {reportRows.length > 0 && (
                                        <tfoot>
                                            <tr style={{
                                                borderTop: '2px solid #fbbf24',
                                                background: 'rgba(251, 191, 36, 0.1)',
                                                fontWeight: 600,
                                                color: '#1e293b'
                                            }}>
                                                <td colSpan="2" style={{ padding: 12, textAlign: 'right' }}>
                                                    TỔNG CỘNG
                                                </td>
                                                <td style={{ padding: 12, textAlign: 'center' }}>
                                                    {formatNumber(reportTotals.records_count)}
                                                </td>
                                                <td style={{ padding: 12, textAlign: 'center' }}>
                                                    {formatNumber(reportTotals.documents_count)}
                                                </td>
                                                <td style={{ padding: 12, textAlign: 'center' }}>
                                                    {formatNumber(reportTotals.boxes_count)}
                                                </td>
                                                <td style={{ padding: 12, textAlign: 'center' }}>
                                                    {formatNumber(reportTotals.total_pages)}
                                                </td>
                                                <td style={{ padding: 12, textAlign: 'center' }}>
                                                    {formatNumber(Math.round(reportTotals.met_gia * 1000) / 1000)}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    )}
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
