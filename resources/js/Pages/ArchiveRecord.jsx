import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function ArchiveRecord({
    archiveRecordItem,
    records,
    pageCount,
    fromBox,
    toBox,
    fromRecord,
    toRecord,
    boxCount,
    recordCountInWords,
    boxCountInWords,
    updatePageNumUrl,
}) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        page_num: archiveRecordItem.page_num ?? pageCount,
    });

    const isLimitedDuration = archiveRecordItem.description === 'Có thời hạn';

    function onSubmit(event) {
        event.preventDefault();
        post(updatePageNumUrl, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Chi tiết mục lục hồ sơ" />
            <div style={{ fontFamily: 'Arial, sans-serif', background: '#f8f9fa', padding: 10 }}>
                <div style={{ maxWidth: 1200, margin: '0 auto' }}>
                    <div style={{ textAlign: 'center', padding: 24, border: '1px solid #333', borderRadius: 8, background: '#fff' }}>
                        <h2 style={{ margin: 0 }}>{archiveRecordItem.organization.archival_name ?? 'Tên Cơ Quan Lưu Trữ'}</h2>
                        <h1 style={{ margin: '14px 0', fontSize: 42 }}>MỤC LỤC HỒ SƠ</h1>
                        <p><strong>PHÔNG LƯU TRỮ:</strong> {archiveRecordItem.organization.name ?? ''}</p>
                        <p>Từ hộp số {fromBox} đến hộp số {toBox} (Từ hồ sơ số {fromRecord} đến hồ sơ số {toRecord})</p>
                        <p style={{ textAlign: 'left' }}><strong>Phông số:</strong> {archiveRecordItem.organization.code ?? ''}</p>
                        <p style={{ textAlign: 'left' }}><strong>Mục lục số:</strong> {archiveRecordItem.archive_record_item_code ?? ''}</p>
                        <p style={{ textAlign: 'left' }}><strong>Số trang:</strong> {archiveRecordItem.page_num ?? pageCount}</p>
                        <p style={{ textAlign: 'right' }}><strong>Thời hạn bảo quản:</strong> {archiveRecordItem.description ?? ''}</p>
                        <p style={{ marginBottom: 0 }}><strong>Năm:</strong> {archiveRecordItem.document_date ?? ''}</p>
                    </div>

                    <div style={{ margin: '20px 0', textAlign: 'center' }}>
                        <form onSubmit={onSubmit} style={{ display: 'inline-flex', alignItems: 'center', gap: 8, marginRight: 12 }}>
                            <label htmlFor="page_num">Nhập số trang thực tế:</label>
                            <input
                                id="page_num"
                                type="number"
                                min="1"
                                value={data.page_num}
                                onChange={(event) => setData('page_num', event.target.value)}
                                style={{ width: 80, padding: 6 }}
                            />
                            <button type="submit" disabled={processing} style={buttonStyle('#1d4ed8')}>
                                {processing ? 'Đang lưu...' : 'Lưu'}
                            </button>
                        </form>
                        <button onClick={() => window.print()} style={buttonStyle('#0f766e')}>In mục lục</button>
                    </div>

                    {errors.page_num ? <div style={{ color: '#b91c1c', marginBottom: 12 }}>{errors.page_num}</div> : null}
                    {flash?.status ? <div style={{ color: '#065f46', marginBottom: 12 }}>{flash.status}</div> : null}

                    <div style={{ padding: 20, border: '1px solid #333', borderRadius: 8, background: '#fff' }}>
                        <h2 style={{ textAlign: 'center', marginTop: 0 }}>Danh sách hồ sơ</h2>
                        {records.length > 0 ? (
                            <>
                                <div style={{ overflowX: 'auto' }}>
                                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                        <thead>
                                            <tr>
                                                <Th>Hộp số</Th>
                                                <Th>Hồ sơ số</Th>
                                                <Th>Tiêu đề</Th>
                                                <Th>Ngày bắt đầu / Ngày kết thúc</Th>
                                                <Th>Thời hạn bảo quản</Th>
                                                {isLimitedDuration ? <Th>Tình trạng tài liệu</Th> : null}
                                                <Th>Số lượng tờ</Th>
                                                <Th>Ghi chú</Th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {records.map((record) => (
                                                <tr key={record.id}>
                                                    <Td>{record.box_code ?? ''}</Td>
                                                    <Td>{record.code ?? ''}</Td>
                                                    <Td>{record.title ?? ''}</Td>
                                                    <Td>{formatDate(record.start_date)}<br />{formatDate(record.end_date)}</Td>
                                                    <Td>{record.preservation_duration ?? ''}</Td>
                                                    {isLimitedDuration ? <Td>{record.condition ?? ''}</Td> : null}
                                                    <Td>{record.page_count ?? ''}</Td>
                                                    <Td>{record.note ?? ''}</Td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div style={{ textAlign: 'center', marginTop: 20, fontSize: 20, fontWeight: 700 }}>TỜ KẾT THÚC</div>
                                <div style={{ marginTop: 10, lineHeight: 1.6 }}>
                                    <p>Mục lục này gồm có: <strong>{records.length} hồ sơ (HS), {boxCount} cặp (hộp)</strong></p>
                                    <p>Viết bằng chữ: <strong>{recordCountInWords} (Hồ sơ), {boxCountInWords} (hộp)</strong></p>
                                    <p>Từ hồ sơ số <strong>{fromRecord}</strong> đến hồ sơ số <strong>{toRecord}</strong>, từ cặp số <strong>{fromBox}</strong> đến cặp số <strong>{toBox}</strong></p>
                                    <p>Số chèn, số đúp: <strong>0</strong></p>
                                    <p>Phần kê hồ sơ, tài liệu của mục lục này gồm có <strong>{pageCount}</strong> tờ (Đánh số liên tục từ trang 01 đến trang {pageCount})</p>
                                </div>
                            </>
                        ) : (
                            <div style={{ textAlign: 'center', color: '#666' }}>Không có hồ sơ nào trong mục lục.</div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
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
    return <th style={{ border: '1px solid #333', padding: 10, background: '#e9ecef', textAlign: 'left' }}>{children}</th>;
}

function Td({ children }) {
    return <td style={{ border: '1px solid #333', padding: 10, textAlign: 'left' }}>{children}</td>;
}

function buttonStyle(background) {
    return {
        border: 'none',
        background,
        color: '#fff',
        borderRadius: 6,
        padding: '8px 14px',
        cursor: 'pointer',
    };
}
