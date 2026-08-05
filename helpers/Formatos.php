<?php
class Formatos {
    /**
     * Calcula el tiempo transcurrido desde una fecha dada hasta ahora.
     * @param string $fecha Fecha en formato Y-m-d H:i:s
     * @return string Tiempo formateado
     */
    public static function tiempoAgo($fecha) {
        if (empty($fecha) || $fecha == '0000-00-00 00:00:00') return "Nunca";
        
        // Aseguramos que PHP use la zona horaria configurada en la App
        $fecha_registro = new DateTime($fecha);
        $ahora = new DateTime();
        
        $intervalo = $ahora->diff($fecha_registro);
        
        // Si la fecha es futura (por desincronización de segundos entre servidores)
        if ($fecha_registro > $ahora) {
            return "Justo ahora";
        }

        if ($intervalo->y > 0) return 'Hace ' . $intervalo->y . ($intervalo->y > 1 ? ' años' : ' año');
        if ($intervalo->m > 0) return 'Hace ' . $intervalo->m . ($intervalo->m > 1 ? ' meses' : ' mes');
        if ($intervalo->d > 0) {
            if ($intervalo->d >= 7) {
                $semanas = floor($intervalo->d / 7);
                return 'Hace ' . $semanas . ($semanas > 1 ? ' semanas' : ' semana');
            }
            return 'Hace ' . $intervalo->d . ($intervalo->d > 1 ? ' días' : ' día');
        }
        if ($intervalo->h > 0) return 'Hace ' . $intervalo->h . ($intervalo->h > 1 ? ' horas' : ' hora');
        if ($intervalo->i > 0) return 'Hace ' . $intervalo->i . ($intervalo->i > 1 ? ' minutos' : ' minuto');
        
        return 'Justo ahora';
    }
}
