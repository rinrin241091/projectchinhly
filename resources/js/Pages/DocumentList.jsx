import React, { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';

export default function DocumentList({ archiveRecord, documents, links }) {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const pageSize = 25;

    const filteredDocuments = useMemo(() => {
        const keyword = search.trim().toLowerCase();
        if (!keyword) {
            return documents;
        }

        return documents.filter((item) => {
            return [
                item.document_code,
                item.document_number,
                item.document_symbol,
                item.description,
                item.author,
                item.signer,
                item.note,
            ]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(keyword));
        });
    }, [documents, search]);

    const totalPages = Math.max(1, Math.ceil(filteredDocuments.length / pageSize));
    const currentPage = Math.min(page, totalPages);

    const currentDocuments = useMemo(() => {
        const start = (currentPage - 1) * pageSize;
        return filteredDocuments.slice(start, start + pageSize);
    }, [filteredDocuments, currentPage]);

    const isPartyOrganization = archiveRecord.organization_type === 'Đảng';

    return (
        <>
            <Head title={`Danh sách tài liệu - ${archiveRecord.title ?? ''}`} />
            <div style={{ padding: 20, background: '#f8f9fa', minHeight: '100vh' }}>
                <h3 style={{ textAlign: 'center', marginBottom: 6 }}>MỤC LỤC VĂN BẢN, TÀI LIỆU</h3>
                <p style={{ textAlign: 'center', marginTop: 0, fontWeight: 700 }}>{archiveRecord.title}</p>
                <p style={{ textAlign: 'center' }}>Hộp số: {archiveRecord.box_code ?? ''}</p>

                <div style={{ display: 'flex', justifyContent: 'center', gap: 10, flexWrap: 'wrap', marginBottom: 14 }}>
                    <button onClick={() => window.print()} style={buttonStyle('#0f62a8')}>In mục lục tài liệu</button>
                    <a href={links.export_excel} target="_blank" rel="noreferrer" style={buttonStyle('#218a46')}>Xuất Excel</a>
                </div>

                <div style={{ maxWidth: 420, margin: '0 auto 14px auto' }}>
                    <input
                        value={search}
                        onChange={(event) => {
                            setSearch(event.target.value);
                            setPage(1);
                        }}
                        placeholder="Lọc tài liệu theo mã, tên, ghi chú..."
                        style={{ width: '100%', padding: 10, border: '1px solid #d1d5db', borderRadius: 8 }}
                    />
                </div>

                {filteredDocuments.length > 0 ? (
                    <>
                        <div style={{ overflowX: 'auto' }}>
                            <table style={{ width: '100%', borderCollapse: 'collapse', background: '#fff' }}>
                                <thead>
                                    {isPartyOrganization ? (
                                        <tr>{partyHeaders.map((header) => <Th key={header}>{header}</Th>)}</tr>
                                    ) : (
                                        <tr>{normalHeaders.map((header) => <Th key={header}>{header}</Th>)}</tr>
                                    )}
                                </thead>
                                <tbody>
                                    {currentDocuments.map((document, index) => (
                                        isPartyOrganization ? (
                                            <tr key={document.id}>{partyCells(document, (currentPage - 1) * pageSize + index + 1).map((cell, idx) => <Td key={idx}>{cell}</Td>)}</tr>
                                        ) : (
                                            <tr key={document.id}>{normalCells(document, (currentPage - 1) * pageSize + index + 1).map((cell, idx) => <Td key={idx}>{cell}</Td>)}</tr>
                                        )
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div style={{ marginTop: 12, display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                            <div>Tổng số tài liệu sau lọc: {filteredDocuments.length}</div>
                            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                                <button disabled={currentPage <= 1} onClick={() => setPage((value) => Math.max(1, value - 1))} style={buttonStyle('#475569')}>Trang trước</button>
                                <span>Trang {currentPage}/{totalPages}</span>
                                <button disabled={currentPage >= totalPages} onClick={() => setPage((value) => Math.min(totalPages, value + 1))} style={buttonStyle('#475569')}>Trang sau</button>
                            </div>
                        </div>
                    </>
                ) : (
                    <div style={{ textAlign: 'center', padding: 20, color: '#666' }}>
                        Không có tài liệu nào trong hồ sơ này.
                    </div>
                )}
            </div>
        </>
    );
}

const partyHeaders = [
    'Số TT', 'Số của văn bản', 'Ký hiệu của văn bản', 'Ngày, tháng, năm văn bản', 'Tên cơ quan ban hành', 'Tên loại văn bản',
    'Trích yếu nội dung', 'Người ký', 'Độ mật', 'Loại bản', 'Trang số', 'Số trang', 'Số lượng tệp', 'Tên tệp', 'Thời gian tài liệu',
    'Chế độ sử dụng', 'Từ khóa', 'Ghi chú', 'Ngôn ngữ', 'Bút tích', 'Chuyên đề', 'Ký hiệu thông tin', 'Mức độ tin cậy', 'Tình trạng vật lý',
];

const normalHeaders = [
    'Số thứ tự', 'Số, Ký hiệu', 'Ngày tháng văn bản', 'Trích yếu nội dung văn bản', 'Tác giả văn bản', 'Tờ số', 'Ghi chú',
];

function partyCells(document, index) {
    return [
        index,
        document.document_number ?? document.document_code ?? '',
        document.document_symbol ?? document.document_code ?? '',
        formatDate(document.document_date),
        document.issuing_agency ?? '',
        document.doc_type_name ?? '',
        document.description ?? '',
        document.signer ?? document.author ?? '',
        document.security_level ?? '',
        document.copy_type ?? '',
        document.page_number ?? '',
        document.total_pages ?? '',
        document.file_count ?? '',
        document.file_name ?? '',
        document.document_duration ?? '',
        document.usage_mode ?? '',
        document.keywords ?? '',
        document.note ?? '',
        document.language ?? '',
        document.handwritten ?? '',
        document.topic ?? '',
        document.information_code ?? '',
        document.reliability_level ?? '',
        document.physical_condition ?? '',
    ];
}

function normalCells(document, index) {
    return [
        index,
        document.document_code ?? '',
        formatDate(document.document_date),
        document.description ?? '',
        document.author ?? document.signer ?? '',
        document.page_number ?? '',
        document.note ?? '',
    ];
}

function formatDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('vi-VN');
}

function Th({ children }) {
    return <th style={{ border: '1px solid #333', padding: 8, background: '#e9ecef', textAlign: 'left' }}>{children}</th>;
}

function Td({ children }) {
    return <td style={{ border: '1px solid #333', padding: 8, textAlign: 'left' }}>{children}</td>;
}

function buttonStyle(color) {
    return {
        border: 'none',
        borderRadius: 8,
        background: color,
        color: '#fff',
        padding: '10px 14px',
        cursor: 'pointer',
        textDecoration: 'none',
        fontSize: 14,
    };
}
