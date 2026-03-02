window.triggerPrint = function () {
    const printContents = document.getElementById('printable').innerHTML;
    const w = window.open('', '', 'width=800,height=600');
    w.document.write('<html><head><title>In nhãn</title><style>body {margin: 0;text-align: center;font-size: 16px; }.label {padding: 20px;border: 1px solid black;display: inline-block;margin: auto;} </style></head><body>' + printContents + '</body></html>');
    w.document.close();
    w.print();
};
window.triggerPrintDoc = function () {
    const printContents = document.getElementById('printable').innerHTML;
    const w = window.open('', '', 'width=800,height=600');
    w.document.write('<html><head><title>In phiếu tin</title><style>h1,h2{ text-align: center;  font-size: 15pt;}#printable { margin: 20px; border: 1px solid #000; padding: 20px; } </style></head><body><div id="printable">' + printContents + '</div></body></html>');
    w.document.close();
    w.print();
};