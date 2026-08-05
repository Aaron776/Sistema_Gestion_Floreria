document.addEventListener('DOMContentLoaded', () => {
    // Eventos para los botones PDF
    const pdfButtons = document.querySelectorAll('.btn-pdf');
    pdfButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const reportType = btn.getAttribute('data-report-pdf');
            if (reportType) {
                window.open(`../reportes/reporte_${reportType}.php`, '_blank');
            }
        });
    });

    // Cerrar vista previa (si existe)
    const closePreviewBtn = document.getElementById('closePreviewBtn');
    if (closePreviewBtn) {
        closePreviewBtn.addEventListener('click', () => {
            document.getElementById('reportPreview').style.display = 'none';
        });
    }
});