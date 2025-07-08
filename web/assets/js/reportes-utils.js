// reportes-utils.js
function formatTime(seconds) {
    if (!seconds) return '00:00:00';
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function getColorProductividad(porcentaje) {
    if (porcentaje >= 80) return 'bg-success';
    if (porcentaje >= 60) return 'bg-info';
    if (porcentaje >= 40) return 'bg-warning';
    return 'bg-danger';
}

export { formatTime, formatearFecha, getColorProductividad };