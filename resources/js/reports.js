window.exportDashboardToPDF = async function () {
    const element = document.getElementById('dashboard-report-area');
    if (!element) return;

    const { default: html2pdf } = await import('html2pdf.js');

    await html2pdf().set({
        margin: 0.3,
        filename: 'SITOKO_Dashboard_Report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' },
    }).from(element).save();
};
