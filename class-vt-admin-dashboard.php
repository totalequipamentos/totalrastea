<?php
/**
 * Dashboard administrativo
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_Dashboard extends VT_Admin {
    
    public function render() {
        $vehicle_model = new VT_Vehicle();
        $alert_model = new VT_Alert();
        
        $stats = $vehicle_model->count_by_status();
        $pending_alerts = $alert_model->count_pending();
        $recent_alerts = $alert_model->get_all(array('limit' => 5, 'status' => 'pending'));
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                __('Dashboard', 'vehicle-tracker'),
                __('Visão geral da sua frota', 'vehicle-tracker'),
                array(
                    array(
                        'url' => admin_url('admin.php?page=vt-tracking'),
                        'label' => __('Ver Mapa', 'vehicle-tracker'),
                        'icon' => 'dashicons-location-alt',
                        'class' => 'button-primary'
                    )
                )
            ); ?>
            
            <div class="vt-dashboard">
                <!-- Cards de Estatísticas -->
                <div class="vt-stats-grid">
                    <?php 
                    $this->render_stat_card(
                        __('Total de Veículos', 'vehicle-tracker'),
                        $stats['total'],
                        'dashicons-car',
                        'primary'
                    );
                    
                    $this->render_stat_card(
                        __('Veículos Online', 'vehicle-tracker'),
                        $stats['online'],
                        'dashicons-visibility',
                        'success'
                    );
                    
                    $this->render_stat_card(
                        __('Em Movimento', 'vehicle-tracker'),
                        $stats['moving'],
                        'dashicons-performance',
                        'info'
                    );
                    
                    $this->render_stat_card(
                        __('Alertas Pendentes', 'vehicle-tracker'),
                        $pending_alerts,
                        'dashicons-warning',
                        'warning'
                    );
                    ?>
                </div>
                
                <div class="vt-dashboard-grid">
                    <!-- Gráfico de Atividade -->
                    <div class="vt-card vt-card-chart">
                        <div class="vt-card-header">
                            <h3><?php _e('Atividade da Frota', 'vehicle-tracker'); ?></h3>
                            <select id="vt-chart-period" class="vt-select-sm">
                                <option value="today"><?php _e('Hoje', 'vehicle-tracker'); ?></option>
                                <option value="week"><?php _e('Última Semana', 'vehicle-tracker'); ?></option>
                                <option value="month"><?php _e('Último Mês', 'vehicle-tracker'); ?></option>
                            </select>
                        </div>
                        <div class="vt-card-body">
                            <canvas id="vt-activity-chart" height="300"></canvas>
                        </div>
                    </div>
                    
                    <!-- Alertas Recentes -->
                    <div class="vt-card vt-card-alerts">
                        <div class="vt-card-header">
                            <h3><?php _e('Alertas Recentes', 'vehicle-tracker'); ?></h3>
                            <a href="<?php echo admin_url('admin.php?page=vt-reports&tab=alerts'); ?>" class="vt-link">
                                <?php _e('Ver todos', 'vehicle-tracker'); ?>
                            </a>
                        </div>
                        <div class="vt-card-body">
                            <?php if (empty($recent_alerts)): ?>
                                <div class="vt-empty-mini">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <p><?php _e('Nenhum alerta pendente', 'vehicle-tracker'); ?></p>
                                </div>
                            <?php else: ?>
                                <ul class="vt-alert-list">
                                    <?php foreach ($recent_alerts as $alert): ?>
                                        <li class="vt-alert-item vt-severity-<?php echo esc_attr($alert['severity']); ?>">
                                            <div class="vt-alert-icon">
                                                <?php echo $this->get_alert_icon($alert['type']); ?>
                                            </div>
                                            <div class="vt-alert-content">
                                                <span class="vt-alert-title"><?php echo esc_html($alert['title']); ?></span>
                                                <span class="vt-alert-meta">
                                                    <?php echo esc_html($alert['plate']); ?> - 
                                                    <?php echo $this->format_datetime($alert['created_at']); ?>
                                                </span>
                                            </div>
                                            <?php echo $this->get_severity_badge($alert['severity']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Mapa Rápido -->
                <div class="vt-card vt-card-map">
                    <div class="vt-card-header">
                        <h3><?php _e('Localização dos Veículos', 'vehicle-tracker'); ?></h3>
                        <a href="<?php echo admin_url('admin.php?page=vt-tracking'); ?>" class="button button-secondary">
                            <?php _e('Abrir Rastreamento', 'vehicle-tracker'); ?>
                        </a>
                    </div>
                    <div class="vt-card-body">
                        <div id="vt-dashboard-map" class="vt-map-container" style="height: 400px;"></div>
                    </div>
                </div>
                
                <!-- Status da Integração -->
                <div class="vt-card vt-card-status">
                    <div class="vt-card-header">
                        <h3><?php _e('Status das Integrações', 'vehicle-tracker'); ?></h3>
                    </div>
                    <div class="vt-card-body">
                        <ul class="vt-integration-list">
                            <?php
                            $avatek = new VT_Avatek_API();
                            $avatek_status = $avatek->is_configured() ? 'active' : 'inactive';
                            ?>
                            <li class="vt-integration-item">
                                <span class="vt-integration-name">
                                    <span class="dashicons dashicons-cloud"></span>
                                    <?php _e('API Avatek', 'vehicle-tracker'); ?>
                                </span>
                                <?php echo $this->get_status_badge($avatek_status); ?>
                            </li>
                            <li class="vt-integration-item">
                                <span class="vt-integration-name">
                                    <span class="dashicons dashicons-networking"></span>
                                    <?php _e('Servidor TCP', 'vehicle-tracker'); ?>
                                </span>
                                <?php echo $this->get_status_badge(get_option('vt_tcp_server_host') ? 'active' : 'inactive'); ?>
                            </li>
                            <li class="vt-integration-item">
                                <span class="vt-integration-name">
                                    <span class="dashicons dashicons-admin-site"></span>
                                    <?php _e('Google Maps', 'vehicle-tracker'); ?>
                                </span>
                                <?php echo $this->get_status_badge(get_option('vt_google_maps_key') ? 'active' : 'inactive'); ?>
                            </li>
                        </ul>
                        <div class="vt-integration-action">
                            <a href="<?php echo admin_url('admin.php?page=vt-settings'); ?>" class="button">
                                <?php _e('Configurar Integrações', 'vehicle-tracker'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Inicializa mapa do dashboard
            if (typeof L !== 'undefined' && $('#vt-dashboard-map').length) {
                var map = L.map('vt-dashboard-map').setView([-15.7801, -47.9292], 4);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                // Carrega posições
                $.ajax({
                    url: vt_ajax.api_url + 'positions',
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', vt_ajax.nonce);
                    },
                    success: function(data) {
                        if (data && data.length > 0) {
                            var bounds = [];
                            data.forEach(function(pos) {
                                var color = pos.is_moving ? 'green' : (pos.is_online ? 'blue' : 'gray');
                                var marker = L.circleMarker([pos.latitude, pos.longitude], {
                                    radius: 8,
                                    fillColor: color,
                                    color: '#fff',
                                    weight: 2,
                                    opacity: 1,
                                    fillOpacity: 0.8
                                }).addTo(map);
                                
                                marker.bindPopup(
                                    '<strong>' + pos.plate + '</strong><br>' +
                                    pos.status_label + '<br>' +
                                    'Velocidade: ' + pos.speed + ' km/h'
                                );
                                
                                bounds.push([pos.latitude, pos.longitude]);
                            });
                            
                            if (bounds.length > 0) {
                                map.fitBounds(bounds, { padding: [20, 20] });
                            }
                        }
                    }
                });
            }
            
            // Gráfico de atividade
            if (typeof Chart !== 'undefined' && $('#vt-activity-chart').length) {
                var ctx = document.getElementById('vt-activity-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['00h', '04h', '08h', '12h', '16h', '20h', '24h'],
                        datasets: [{
                            label: '<?php _e('Veículos Ativos', 'vehicle-tracker'); ?>',
                            data: [2, 1, 5, 8, 12, 10, 6],
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Retorna ícone do alerta
     */
    private function get_alert_icon($type) {
        $icons = array(
            'overspeed' => 'dashicons-performance',
            'ignition_on' => 'dashicons-marker',
            'ignition_off' => 'dashicons-marker',
            'geofence_enter' => 'dashicons-location',
            'geofence_exit' => 'dashicons-location-alt',
            'panic' => 'dashicons-sos',
            'low_battery' => 'dashicons-battery',
            'power_cut' => 'dashicons-warning',
            'tamper' => 'dashicons-lock'
        );
        
        $icon = $icons[$type] ?? 'dashicons-flag';
        
        return '<span class="dashicons ' . esc_attr($icon) . '"></span>';
    }
}
