<?php
/**
 * Página de histórico de trajetos
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_History extends VT_Admin {
    
    public function render() {
        $vehicle_model = new VT_Vehicle();
        $vehicles = $vehicle_model->get_all();
        
        $selected_vehicle = isset($_GET['vehicle']) ? intval($_GET['vehicle']) : 0;
        $start_date = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-d 00:00:00');
        $end_date = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-d 23:59:59');
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                __('Histórico de Trajetos', 'vehicle-tracker'),
                __('Visualize o trajeto percorrido pelos veículos', 'vehicle-tracker')
            ); ?>
            
            <div class="vt-history-layout">
                <!-- Filtros -->
                <div class="vt-card vt-history-filters">
                    <form method="get" class="vt-filter-form">
                        <input type="hidden" name="page" value="vt-history">
                        
                        <div class="vt-form-row">
                            <div class="vt-form-group">
                                <label for="vehicle"><?php _e('Veículo', 'vehicle-tracker'); ?></label>
                                <select id="vehicle" name="vehicle" required class="vt-select2">
                                    <option value=""><?php _e('Selecione um veículo', 'vehicle-tracker'); ?></option>
                                    <?php foreach ($vehicles as $v): ?>
                                        <option value="<?php echo esc_attr($v['id']); ?>" <?php selected($selected_vehicle, $v['id']); ?>>
                                            <?php echo esc_html($v['plate'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="vt-form-group">
                                <label for="start"><?php _e('Data/Hora Início', 'vehicle-tracker'); ?></label>
                                <input type="datetime-local" id="start" name="start" 
                                       value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($start_date))); ?>">
                            </div>
                            
                            <div class="vt-form-group">
                                <label for="end"><?php _e('Data/Hora Fim', 'vehicle-tracker'); ?></label>
                                <input type="datetime-local" id="end" name="end" 
                                       value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($end_date))); ?>">
                            </div>
                            
                            <div class="vt-form-group vt-form-group-btn">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php _e('Buscar', 'vehicle-tracker'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($selected_vehicle): ?>
                    <?php
                    $history = $vehicle_model->get_history($selected_vehicle, $start_date, $end_date);
                    $vehicle = $vehicle_model->get($selected_vehicle);
                    ?>
                    
                    <div class="vt-history-content">
                        <!-- Estatísticas do período -->
                        <div class="vt-stats-grid vt-stats-small">
                            <?php
                            $total_distance = 0;
                            $max_speed = 0;
                            $total_time = 0;
                            $prev_pos = null;
                            
                            foreach ($history as $pos) {
                                if ($prev_pos) {
                                    $total_distance += $this->calculate_distance(
                                        $prev_pos['latitude'], $prev_pos['longitude'],
                                        $pos['latitude'], $pos['longitude']
                                    );
                                }
                                if ($pos['speed'] > $max_speed) {
                                    $max_speed = $pos['speed'];
                                }
                                $prev_pos = $pos;
                            }
                            
                            $total_distance = round($total_distance, 2);
                            $avg_speed = count($history) > 0 ? array_sum(array_column($history, 'speed')) / count($history) : 0;
                            ?>
                            
                            <div class="vt-stat-mini">
                                <span class="vt-stat-value"><?php echo number_format($total_distance, 1); ?> km</span>
                                <span class="vt-stat-label"><?php _e('Distância', 'vehicle-tracker'); ?></span>
                            </div>
                            <div class="vt-stat-mini">
                                <span class="vt-stat-value"><?php echo count($history); ?></span>
                                <span class="vt-stat-label"><?php _e('Posições', 'vehicle-tracker'); ?></span>
                            </div>
                            <div class="vt-stat-mini">
                                <span class="vt-stat-value"><?php echo number_format($max_speed, 0); ?> km/h</span>
                                <span class="vt-stat-label"><?php _e('Velocidade Máx.', 'vehicle-tracker'); ?></span>
                            </div>
                            <div class="vt-stat-mini">
                                <span class="vt-stat-value"><?php echo number_format($avg_speed, 0); ?> km/h</span>
                                <span class="vt-stat-label"><?php _e('Velocidade Média', 'vehicle-tracker'); ?></span>
                            </div>
                        </div>
                        
                        <!-- Mapa do trajeto -->
                        <div class="vt-card">
                            <div class="vt-card-body">
                                <div id="vt-history-map" class="vt-map-container" style="height: 500px;"></div>
                            </div>
                        </div>
                        
                        <!-- Tabela de eventos -->
                        <div class="vt-card">
                            <div class="vt-card-header">
                                <h3><?php _e('Histórico de Posições', 'vehicle-tracker'); ?></h3>
                                <button type="button" id="vt-export-history" class="button">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Exportar CSV', 'vehicle-tracker'); ?>
                                </button>
                            </div>
                            <div class="vt-card-body">
                                <?php if (empty($history)): ?>
                                    <?php $this->render_empty_state(__('Nenhuma posição encontrada no período', 'vehicle-tracker')); ?>
                                <?php else: ?>
                                    <table class="vt-table vt-table-compact">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Data/Hora', 'vehicle-tracker'); ?></th>
                                                <th><?php _e('Velocidade', 'vehicle-tracker'); ?></th>
                                                <th><?php _e('Direção', 'vehicle-tracker'); ?></th>
                                                <th><?php _e('Ignição', 'vehicle-tracker'); ?></th>
                                                <th><?php _e('Evento', 'vehicle-tracker'); ?></th>
                                                <th><?php _e('Coordenadas', 'vehicle-tracker'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($history, 0, 500) as $pos): ?>
                                                <tr class="vt-position-row" 
                                                    data-lat="<?php echo esc_attr($pos['latitude']); ?>"
                                                    data-lng="<?php echo esc_attr($pos['longitude']); ?>">
                                                    <td><?php echo $this->format_datetime($pos['device_time']); ?></td>
                                                    <td><?php echo esc_html($pos['speed']); ?> km/h</td>
                                                    <td><?php echo $this->get_direction_label($pos['direction']); ?></td>
                                                    <td><?php echo $pos['ignition'] ? __('Ligada', 'vehicle-tracker') : __('Desligada', 'vehicle-tracker'); ?></td>
                                                    <td><?php echo esc_html($pos['event_type'] ?? 'position'); ?></td>
                                                    <td>
                                                        <small><?php echo esc_html($pos['latitude'] . ', ' . $pos['longitude']); ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php if (count($history) > 500): ?>
                                        <p class="description"><?php printf(__('Exibindo 500 de %d posições', 'vehicle-tracker'), count($history)); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    jQuery(document).ready(function($) {
                        var historyData = <?php echo json_encode($history); ?>;
                        
                        if (historyData.length > 0 && typeof L !== 'undefined') {
                            var map = L.map('vt-history-map').setView([historyData[0].latitude, historyData[0].longitude], 14);
                            
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap'
                            }).addTo(map);
                            
                            // Linha do trajeto
                            var latlngs = historyData.map(function(pos) {
                                return [pos.latitude, pos.longitude];
                            });
                            
                            var polyline = L.polyline(latlngs, {
                                color: '#0073aa',
                                weight: 4,
                                opacity: 0.8
                            }).addTo(map);
                            
                            // Marcador início
                            L.marker([historyData[0].latitude, historyData[0].longitude], {
                                icon: L.divIcon({
                                    className: 'vt-marker-start',
                                    html: '<span class="dashicons dashicons-flag"></span>',
                                    iconSize: [30, 30]
                                })
                            }).addTo(map).bindPopup('<?php _e('Início', 'vehicle-tracker'); ?>');
                            
                            // Marcador fim
                            var lastPos = historyData[historyData.length - 1];
                            L.marker([lastPos.latitude, lastPos.longitude], {
                                icon: L.divIcon({
                                    className: 'vt-marker-end',
                                    html: '<span class="dashicons dashicons-location"></span>',
                                    iconSize: [30, 30]
                                })
                            }).addTo(map).bindPopup('<?php _e('Fim', 'vehicle-tracker'); ?>');
                            
                            // Ajusta zoom
                            map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
                            
                            // Clique na tabela foca no mapa
                            $('.vt-position-row').on('click', function() {
                                var lat = $(this).data('lat');
                                var lng = $(this).data('lng');
                                map.setView([lat, lng], 17);
                            });
                        }
                        
                        // Exportar CSV
                        $('#vt-export-history').on('click', function() {
                            var csv = 'Data/Hora,Latitude,Longitude,Velocidade,Direção,Ignição,Evento\n';
                            historyData.forEach(function(pos) {
                                csv += pos.device_time + ',' + pos.latitude + ',' + pos.longitude + ',' + 
                                       pos.speed + ',' + (pos.direction || '') + ',' + (pos.ignition ? 'Ligada' : 'Desligada') + ',' + 
                                       (pos.event_type || 'position') + '\n';
                            });
                            
                            var blob = new Blob([csv], { type: 'text/csv' });
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = 'historico_<?php echo esc_js($vehicle['plate']); ?>_<?php echo date('Y-m-d'); ?>.csv';
                            a.click();
                        });
                    });
                    </script>
                <?php else: ?>
                    <?php $this->render_empty_state(__('Selecione um veículo e período para visualizar o histórico', 'vehicle-tracker')); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Calcula distância entre dois pontos
     */
    private function calculate_distance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371;
        
        $lat1_rad = deg2rad($lat1);
        $lat2_rad = deg2rad($lat2);
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lng = deg2rad($lng2 - $lng1);
        
        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lng / 2) * sin($delta_lng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Retorna label da direção
     */
    private function get_direction_label($direction) {
        if ($direction === null) return '-';
        
        $directions = array(
            'N' => array(337.5, 22.5),
            'NE' => array(22.5, 67.5),
            'E' => array(67.5, 112.5),
            'SE' => array(112.5, 157.5),
            'S' => array(157.5, 202.5),
            'SO' => array(202.5, 247.5),
            'O' => array(247.5, 292.5),
            'NO' => array(292.5, 337.5)
        );
        
        foreach ($directions as $label => $range) {
            if ($label === 'N') {
                if ($direction >= $range[0] || $direction < $range[1]) {
                    return $label . ' (' . $direction . '°)';
                }
            } else {
                if ($direction >= $range[0] && $direction < $range[1]) {
                    return $label . ' (' . $direction . '°)';
                }
            }
        }
        
        return $direction . '°';
    }
}
