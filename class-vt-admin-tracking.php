<?php
/**
 * Página de rastreamento em tempo real
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_Tracking extends VT_Admin {
    
    public function render() {
        $vehicle_model = new VT_Vehicle();
        $vehicles = $vehicle_model->get_all(array('status' => 'active'));
        $selected_vehicle = isset($_GET['vehicle']) ? intval($_GET['vehicle']) : 0;
        ?>
        <div class="wrap vt-admin-wrap vt-tracking-wrap">
            <?php $this->render_page_header(
                __('Rastreamento em Tempo Real', 'vehicle-tracker'),
                __('Monitore sua frota em tempo real', 'vehicle-tracker')
            ); ?>
            
            <div class="vt-tracking-layout">
                <button type="button" class="vt-mobile-toggle">
                    <span class="dashicons dashicons-menu"></span>
                </button>
                <div class="vt-sidebar-backdrop"></div>
                <!-- Sidebar com lista de veículos -->
                <div class="vt-tracking-sidebar">
                    <div class="vt-sidebar-header">
                        <input type="search" id="vt-vehicle-search" class="vt-search-input" 
                               placeholder="<?php _e('Buscar veículo...', 'vehicle-tracker'); ?>">
                        
                        <div class="vt-filter-buttons">
                            <button type="button" class="vt-filter-btn active" data-filter="all">
                                <?php _e('Todos', 'vehicle-tracker'); ?>
                            </button>
                            <button type="button" class="vt-filter-btn" data-filter="moving">
                                <?php _e('Movimento', 'vehicle-tracker'); ?>
                            </button>
                            <button type="button" class="vt-filter-btn" data-filter="stopped">
                                <?php _e('Parados', 'vehicle-tracker'); ?>
                            </button>
                            <button type="button" class="vt-filter-btn" data-filter="offline">
                                <?php _e('Offline', 'vehicle-tracker'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="vt-vehicle-list" id="vt-vehicle-list">
                        <?php foreach ($vehicles as $vehicle): ?>
                            <div class="vt-vehicle-item <?php echo $selected_vehicle === intval($vehicle['id']) ? 'active' : ''; ?>" 
                                 data-id="<?php echo esc_attr($vehicle['id']); ?>"
                                 data-status="<?php echo $vehicle['is_moving'] ? 'moving' : ($vehicle['is_online'] ? 'stopped' : 'offline'); ?>"
                                 data-lat="<?php echo esc_attr($vehicle['last_latitude']); ?>"
                                 data-lng="<?php echo esc_attr($vehicle['last_longitude']); ?>">
                                <div class="vt-vehicle-status <?php echo $vehicle['is_moving'] ? 'moving' : ($vehicle['is_online'] ? 'online' : 'offline'); ?>"></div>
                                <div class="vt-vehicle-details">
                                    <span class="vt-vehicle-plate"><?php echo esc_html($vehicle['plate']); ?></span>
                                    <span class="vt-vehicle-info">
                                        <?php echo esc_html($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                    </span>
                                    <span class="vt-vehicle-speed">
                                        <?php if ($vehicle['is_online']): ?>
                                            <?php echo esc_html($vehicle['last_speed'] ?? 0); ?> km/h
                                        <?php else: ?>
                                            <?php _e('Sem sinal', 'vehicle-tracker'); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="vt-vehicle-actions">
                                    <button type="button" class="vt-btn-icon vt-focus-vehicle" title="<?php _e('Centralizar', 'vehicle-tracker'); ?>">
                                        <span class="dashicons dashicons-location-alt"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="vt-sidebar-footer">
                        <button type="button" id="vt-refresh-positions" class="button button-secondary">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Atualizar', 'vehicle-tracker'); ?>
                        </button>
                        <span class="vt-last-update">
                            <?php _e('Última atualização:', 'vehicle-tracker'); ?>
                            <span id="vt-last-update-time">--:--</span>
                        </span>
                    </div>
                </div>
                
                <!-- Mapa -->
                <div class="vt-tracking-map">
                    <div id="vt-realtime-map" class="vt-map-fullsize"></div>
                    
                    <!-- Painel de detalhes do veículo -->
                    <div id="vt-vehicle-panel" class="vt-vehicle-panel" style="display: none;">
                        <button type="button" class="vt-panel-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                        
                        <div class="vt-panel-header">
                            <h3 id="vt-panel-plate">---</h3>
                            <span id="vt-panel-vehicle">---</span>
                        </div>
                        
                        <div class="vt-panel-body">
                            <div class="vt-panel-info-grid">
                                <div class="vt-panel-info-item">
                                    <span class="vt-info-label"><?php _e('Status', 'vehicle-tracker'); ?></span>
                                    <span class="vt-info-value" id="vt-panel-status">---</span>
                                </div>
                                <div class="vt-panel-info-item">
                                    <span class="vt-info-label"><?php _e('Velocidade', 'vehicle-tracker'); ?></span>
                                    <span class="vt-info-value" id="vt-panel-speed">--- km/h</span>
                                </div>
                                <div class="vt-panel-info-item">
                                    <span class="vt-info-label"><?php _e('Ignição', 'vehicle-tracker'); ?></span>
                                    <span class="vt-info-value" id="vt-panel-ignition">---</span>
                                </div>
                                <div class="vt-panel-info-item">
                                    <span class="vt-info-label"><?php _e('Última Atualização', 'vehicle-tracker'); ?></span>
                                    <span class="vt-info-value" id="vt-panel-update">---</span>
                                </div>
                            </div>
                            
                            <div class="vt-panel-address">
                                <span class="vt-info-label"><?php _e('Localização', 'vehicle-tracker'); ?></span>
                                <span class="vt-info-value" id="vt-panel-address"><?php _e('Carregando...', 'vehicle-tracker'); ?></span>
                            </div>
                            
                            <div class="vt-panel-coords">
                                <span id="vt-panel-lat">---</span>, <span id="vt-panel-lng">---</span>
                            </div>
                        </div>
                        
                        <div class="vt-panel-actions">
                            <button type="button" class="button vt-cmd-block" data-command="block">
                                <span class="dashicons dashicons-lock"></span>
                                <?php _e('Bloquear', 'vehicle-tracker'); ?>
                            </button>
                            <button type="button" class="button vt-cmd-unblock" data-command="unblock">
                                <span class="dashicons dashicons-unlock"></span>
                                <?php _e('Desbloquear', 'vehicle-tracker'); ?>
                            </button>
                            <a href="#" id="vt-panel-history" class="button">
                                <span class="dashicons dashicons-clock"></span>
                                <?php _e('Histórico', 'vehicle-tracker'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Controles do mapa -->
                    <div class="vt-map-controls">
                        <button type="button" id="vt-fit-bounds" class="vt-map-control" title="<?php _e('Ver todos', 'vehicle-tracker'); ?>">
                            <span class="dashicons dashicons-fullscreen-alt"></span>
                        </button>
                        <button type="button" id="vt-toggle-traffic" class="vt-map-control" title="<?php _e('Trânsito', 'vehicle-tracker'); ?>">
                            <span class="dashicons dashicons-car"></span>
                        </button>
                        <button type="button" id="vt-toggle-satellite" class="vt-map-control" title="<?php _e('Satélite', 'vehicle-tracker'); ?>">
                            <span class="dashicons dashicons-admin-site-alt3"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var map, markers = {}, selectedVehicle = null;
            var autoRefresh = true;
            var refreshInterval = <?php echo intval(get_option('vt_refresh_interval', 30)); ?> * 1000;
            
            // Inicializa mapa
            function initMap() {
                map = L.map('vt-realtime-map').setView([-15.7801, -47.9292], 4);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);
                
                loadPositions();
                
                // Auto refresh
                setInterval(function() {
                    if (autoRefresh) {
                        loadPositions();
                    }
                }, refreshInterval);
            }
            
            // Carrega posições
            function loadPositions() {
                $.ajax({
                    url: vt_ajax.api_url + 'positions',
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', vt_ajax.nonce);
                    },
                    success: function(data) {
                        updateMarkers(data);
                        updateLastTime();
                    }
                });
            }
            
            // Atualiza marcadores no mapa
            function updateMarkers(positions) {
                positions.forEach(function(pos) {
                    var id = pos.vehicle_id;
                    var color = pos.is_moving ? '#22c55e' : (pos.is_online ? '#3b82f6' : '#6b7280');
                    
                    if (markers[id]) {
                        markers[id].setLatLng([pos.latitude, pos.longitude]);
                        markers[id].setStyle({ fillColor: color });
                    } else {
                        markers[id] = L.circleMarker([pos.latitude, pos.longitude], {
                            radius: 10,
                            fillColor: color,
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.9
                        }).addTo(map);
                        
                        markers[id].on('click', function() {
                            selectVehicle(id, pos);
                        });
                    }
                    
                    markers[id].bindTooltip(pos.plate + ' - ' + pos.speed + ' km/h', {
                        permanent: false,
                        direction: 'top'
                    });
                    
                    // Atualiza item na lista
                    var $item = $('.vt-vehicle-item[data-id="' + id + '"]');
                    $item.attr('data-status', pos.is_moving ? 'moving' : (pos.is_online ? 'stopped' : 'offline'));
                    $item.find('.vt-vehicle-status').attr('class', 'vt-vehicle-status ' + (pos.is_moving ? 'moving' : (pos.is_online ? 'online' : 'offline')));
                    $item.find('.vt-vehicle-speed').text(pos.is_online ? pos.speed + ' km/h' : 'Sem sinal');
                });
            }
            
            // Seleciona veículo
            function selectVehicle(id, data) {
                selectedVehicle = id;
                
                $('.vt-vehicle-item').removeClass('active');
                $('.vt-vehicle-item[data-id="' + id + '"]').addClass('active');
                
                $('#vt-panel-plate').text(data.plate);
                $('#vt-panel-vehicle').text(data.brand + ' ' + data.model);
                $('#vt-panel-status').html(data.status_label);
                $('#vt-panel-speed').text(data.speed + ' km/h');
                $('#vt-panel-ignition').text(data.ignition ? 'Ligada' : 'Desligada');
                $('#vt-panel-update').text(data.last_update || '---');
                $('#vt-panel-lat').text(parseFloat(data.latitude).toFixed(6));
                $('#vt-panel-lng').text(parseFloat(data.longitude).toFixed(6));
                $('#vt-panel-history').attr('href', 'admin.php?page=vt-history&vehicle=' + id);
                
                $('#vt-vehicle-panel').show();
                
                map.setView([data.latitude, data.longitude], 15);
            }
            
            // Atualiza hora
            function updateLastTime() {
                var now = new Date();
                $('#vt-last-update-time').text(now.toLocaleTimeString());
            }
            
            // Event handlers
            $('.vt-vehicle-item').on('click', function() {
                var id = $(this).data('id');
                var lat = $(this).data('lat');
                var lng = $(this).data('lng');
                
                if (lat && lng) {
                    $.ajax({
                        url: vt_ajax.api_url + 'vehicles/' + id + '/position',
                        method: 'GET',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', vt_ajax.nonce);
                        },
                        success: function(data) {
                            selectVehicle(id, data);
                        }
                    });
                }
            });
            
            $('.vt-filter-btn').on('click', function() {
                var filter = $(this).data('filter');
                
                $('.vt-filter-btn').removeClass('active');
                $(this).addClass('active');
                
                if (filter === 'all') {
                    $('.vt-vehicle-item').show();
                } else {
                    $('.vt-vehicle-item').hide();
                    $('.vt-vehicle-item[data-status="' + filter + '"]').show();
                }
            });
            
            $('#vt-vehicle-search').on('input', function() {
                var search = $(this).val().toLowerCase();
                
                $('.vt-vehicle-item').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(search) > -1);
                });
            });
            
            // Mobile Toggle Logic
            $('.vt-mobile-toggle').on('click', function() {
                $('.vt-tracking-sidebar').addClass('open');
                $('.vt-sidebar-backdrop').addClass('active');
            });

            $('.vt-sidebar-backdrop').on('click', function() {
                $('.vt-tracking-sidebar').removeClass('open');
                $('.vt-sidebar-backdrop').removeClass('active');
            });

            // Close panel on mobile also hides it nicely
            $('.vt-panel-close').on('click', function() {
                $('#vt-vehicle-panel').removeClass('active');
                setTimeout(function() {
                     $('#vt-vehicle-panel').hide();
                     selectedVehicle = null;
                }, 300);
            });
            
            // Override selectVehicle to handle mobile panel
            var originalSelectVehicle = selectVehicle;
            selectVehicle = function(id, data) {
                // Call original logic (we need to duplicate it or expose it, but here we just update UI)
                 selectedVehicle = id;
                
                $('.vt-vehicle-item').removeClass('active');
                $('.vt-vehicle-item[data-id="' + id + '"]').addClass('active');
                
                $('#vt-panel-plate').text(data.plate);
                $('#vt-panel-vehicle').text(data.brand + ' ' + data.model);
                $('#vt-panel-status').html(data.status_label);
                $('#vt-panel-speed').text(data.speed + ' km/h');
                $('#vt-panel-ignition').text(data.ignition ? 'Ligada' : 'Desligada');
                $('#vt-panel-update').text(data.last_update || '---');
                $('#vt-panel-lat').text(parseFloat(data.latitude).toFixed(6));
                $('#vt-panel-lng').text(parseFloat(data.longitude).toFixed(6));
                $('#vt-panel-history').attr('href', 'admin.php?page=vt-history&vehicle=' + id);
                
                $('#vt-vehicle-panel').show().addClass('active'); // Add active class for mobile slide-up
                
                // On mobile, close sidebar if open
                $('.vt-tracking-sidebar').removeClass('open');
                $('.vt-sidebar-backdrop').removeClass('active');

                map.setView([data.latitude, data.longitude], 15);
            };

            // Inicializa
            initMap();
        });
        </script>
        <?php
    }
}
