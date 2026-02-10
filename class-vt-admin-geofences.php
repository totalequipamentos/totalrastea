<?php
/**
 * Página de gestão de geocercas
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_Geofences extends VT_Admin {
    
    public function render() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $geofence_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'add':
            case 'edit':
                $this->render_form($geofence_id);
                break;
            default:
                $this->render_list();
        }
    }
    
    private function render_list() {
        $geofence_model = new VT_Geofence();
        $geofences = $geofence_model->get_all();
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                __('Geocercas', 'vehicle-tracker'),
                __('Gerencie áreas de interesse e alertas de entrada/saída', 'vehicle-tracker'),
                array(
                    array(
                        'url' => admin_url('admin.php?page=vt-geofences&action=add'),
                        'label' => __('Nova Geocerca', 'vehicle-tracker'),
                        'icon' => 'dashicons-plus-alt',
                        'class' => 'button-primary'
                    )
                )
            ); ?>
            
            <div class="vt-geofences-layout">
                <!-- Lista de geocercas -->
                <div class="vt-card">
                    <?php if (empty($geofences)): ?>
                        <?php $this->render_empty_state(
                            __('Nenhuma geocerca cadastrada', 'vehicle-tracker'),
                            array(
                                'url' => admin_url('admin.php?page=vt-geofences&action=add'),
                                'label' => __('Criar Geocerca', 'vehicle-tracker')
                            )
                        ); ?>
                    <?php else: ?>
                        <table class="vt-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Nome', 'vehicle-tracker'); ?></th>
                                    <th><?php _e('Tipo', 'vehicle-tracker'); ?></th>
                                    <th><?php _e('Alertas', 'vehicle-tracker'); ?></th>
                                    <th><?php _e('Status', 'vehicle-tracker'); ?></th>
                                    <th><?php _e('Veículos Dentro', 'vehicle-tracker'); ?></th>
                                    <th class="vt-col-actions"><?php _e('Ações', 'vehicle-tracker'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($geofences as $geo): ?>
                                    <?php $vehicles_inside = $geofence_model->get_vehicles_inside($geo['id']); ?>
                                    <tr>
                                        <td>
                                            <div class="vt-geofence-name">
                                                <span class="vt-geofence-color" style="background-color: <?php echo esc_attr($geo['color']); ?>"></span>
                                                <?php echo esc_html($geo['name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $geo['type'] === 'circle' ? __('Circular', 'vehicle-tracker') : __('Poligonal', 'vehicle-tracker'); ?>
                                            <?php if ($geo['type'] === 'circle'): ?>
                                                <small>(<?php echo number_format($geo['radius'], 0); ?>m)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($geo['alert_on_enter']): ?>
                                                <span class="vt-badge vt-badge-sm vt-badge-info"><?php _e('Entrada', 'vehicle-tracker'); ?></span>
                                            <?php endif; ?>
                                            <?php if ($geo['alert_on_exit']): ?>
                                                <span class="vt-badge vt-badge-sm vt-badge-warning"><?php _e('Saída', 'vehicle-tracker'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $this->get_status_badge($geo['status']); ?></td>
                                        <td>
                                            <span class="vt-count"><?php echo count($vehicles_inside); ?></span>
                                        </td>
                                        <td class="vt-col-actions">
                                            <div class="vt-actions">
                                                <a href="<?php echo admin_url('admin.php?page=vt-geofences&action=edit&id=' . $geo['id']); ?>" 
                                                   class="vt-action" title="<?php _e('Editar', 'vehicle-tracker'); ?>">
                                                    <span class="dashicons dashicons-edit"></span>
                                                </a>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vt-geofences&action=delete&id=' . $geo['id']), 'delete_geofence_' . $geo['id']); ?>" 
                                                   class="vt-action vt-action-danger vt-confirm-delete" 
                                                   title="<?php _e('Excluir', 'vehicle-tracker'); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Mapa com todas as geocercas -->
                <div class="vt-card">
                    <div class="vt-card-header">
                        <h3><?php _e('Visualização no Mapa', 'vehicle-tracker'); ?></h3>
                    </div>
                    <div class="vt-card-body">
                        <div id="vt-geofences-map" class="vt-map-container" style="height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var geofences = <?php echo json_encode($geofences); ?>;
            
            if (typeof L !== 'undefined') {
                var map = L.map('vt-geofences-map').setView([-15.7801, -47.9292], 4);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);
                
                var bounds = [];
                
                geofences.forEach(function(geo) {
                    if (geo.type === 'circle' && geo.center_lat && geo.center_lng) {
                        var circle = L.circle([geo.center_lat, geo.center_lng], {
                            radius: geo.radius,
                            color: geo.color,
                            fillColor: geo.fill_color,
                            fillOpacity: geo.fill_opacity
                        }).addTo(map);
                        
                        circle.bindPopup('<strong>' + geo.name + '</strong>');
                        bounds.push([geo.center_lat, geo.center_lng]);
                    } else if (geo.type === 'polygon' && geo.coordinates) {
                        var coords = JSON.parse(geo.coordinates);
                        var latlngs = coords.map(function(c) { return [c.lat, c.lng]; });
                        
                        var polygon = L.polygon(latlngs, {
                            color: geo.color,
                            fillColor: geo.fill_color,
                            fillOpacity: geo.fill_opacity
                        }).addTo(map);
                        
                        polygon.bindPopup('<strong>' + geo.name + '</strong>');
                        bounds = bounds.concat(latlngs);
                    }
                });
                
                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                }
            }
        });
        </script>
        <?php
    }
    
    private function render_form($id = 0) {
        $geofence = null;
        $is_edit = $id > 0;
        
        if ($is_edit) {
            $geofence_model = new VT_Geofence();
            $geofence = $geofence_model->get($id);
        }
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                $is_edit ? __('Editar Geocerca', 'vehicle-tracker') : __('Nova Geocerca', 'vehicle-tracker')
            ); ?>
            
            <form method="post" class="vt-form" id="vt-geofence-form">
                <?php wp_nonce_field('vt_save_geofence', 'vt_geofence_nonce'); ?>
                
                <div class="vt-geofence-editor">
                    <div class="vt-geofence-settings">
                        <div class="vt-card">
                            <div class="vt-card-header">
                                <h3><?php _e('Configurações', 'vehicle-tracker'); ?></h3>
                            </div>
                            <div class="vt-card-body">
                                <div class="vt-form-group">
                                    <label for="name"><?php _e('Nome', 'vehicle-tracker'); ?> *</label>
                                    <input type="text" id="name" name="name" required
                                           value="<?php echo esc_attr($geofence['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="vt-form-group">
                                    <label for="description"><?php _e('Descrição', 'vehicle-tracker'); ?></label>
                                    <textarea id="description" name="description" rows="2"><?php echo esc_textarea($geofence['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="vt-form-group">
                                    <label for="type"><?php _e('Tipo', 'vehicle-tracker'); ?></label>
                                    <select id="type" name="type">
                                        <option value="circle" <?php selected($geofence['type'] ?? 'circle', 'circle'); ?>>
                                            <?php _e('Circular', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="polygon" <?php selected($geofence['type'] ?? '', 'polygon'); ?>>
                                            <?php _e('Poligonal', 'vehicle-tracker'); ?>
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="vt-form-group vt-circle-fields" style="<?php echo ($geofence['type'] ?? 'circle') !== 'circle' ? 'display:none;' : ''; ?>">
                                    <label for="radius"><?php _e('Raio (metros)', 'vehicle-tracker'); ?></label>
                                    <input type="number" id="radius" name="radius" min="10" max="100000"
                                           value="<?php echo esc_attr($geofence['radius'] ?? '500'); ?>">
                                </div>
                                
                                <div class="vt-form-row">
                                    <div class="vt-form-group">
                                        <label for="color"><?php _e('Cor da Borda', 'vehicle-tracker'); ?></label>
                                        <input type="color" id="color" name="color"
                                               value="<?php echo esc_attr($geofence['color'] ?? '#3388ff'); ?>">
                                    </div>
                                    <div class="vt-form-group">
                                        <label for="fill_color"><?php _e('Cor de Preenchimento', 'vehicle-tracker'); ?></label>
                                        <input type="color" id="fill_color" name="fill_color"
                                               value="<?php echo esc_attr($geofence['fill_color'] ?? '#3388ff'); ?>">
                                    </div>
                                </div>
                                
                                <div class="vt-form-group">
                                    <label><?php _e('Alertas', 'vehicle-tracker'); ?></label>
                                    <label class="vt-checkbox">
                                        <input type="checkbox" name="alert_on_enter" value="1" 
                                               <?php checked($geofence['alert_on_enter'] ?? 1, 1); ?>>
                                        <?php _e('Alertar ao entrar', 'vehicle-tracker'); ?>
                                    </label>
                                    <label class="vt-checkbox">
                                        <input type="checkbox" name="alert_on_exit" value="1" 
                                               <?php checked($geofence['alert_on_exit'] ?? 1, 1); ?>>
                                        <?php _e('Alertar ao sair', 'vehicle-tracker'); ?>
                                    </label>
                                </div>
                                
                                <div class="vt-form-group">
                                    <label for="status"><?php _e('Status', 'vehicle-tracker'); ?></label>
                                    <select id="status" name="status">
                                        <option value="active" <?php selected($geofence['status'] ?? 'active', 'active'); ?>>
                                            <?php _e('Ativo', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="inactive" <?php selected($geofence['status'] ?? '', 'inactive'); ?>>
                                            <?php _e('Inativo', 'vehicle-tracker'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vt-geofence-map-area">
                        <div class="vt-card">
                            <div class="vt-card-header">
                                <h3><?php _e('Desenhe a Geocerca', 'vehicle-tracker'); ?></h3>
                                <span class="description"><?php _e('Clique no mapa para definir o centro (circular) ou os vértices (poligonal)', 'vehicle-tracker'); ?></span>
                            </div>
                            <div class="vt-card-body">
                                <div id="vt-geofence-editor-map" class="vt-map-container" style="height: 500px;"></div>
                                
                                <input type="hidden" id="center_lat" name="center_lat" value="<?php echo esc_attr($geofence['center_lat'] ?? ''); ?>">
                                <input type="hidden" id="center_lng" name="center_lng" value="<?php echo esc_attr($geofence['center_lng'] ?? ''); ?>">
                                <input type="hidden" id="coordinates" name="coordinates" value="<?php echo esc_attr($geofence['coordinates'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="vt-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=vt-geofences'); ?>" class="button">
                        <?php _e('Cancelar', 'vehicle-tracker'); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? __('Atualizar Geocerca', 'vehicle-tracker') : __('Criar Geocerca', 'vehicle-tracker'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var map, geofenceLayer;
            var type = $('#type').val();
            
            // Inicializa mapa
            map = L.map('vt-geofence-editor-map').setView([-15.7801, -47.9292], 4);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            // Carrega geocerca existente
            <?php if ($is_edit && $geofence): ?>
                <?php if ($geofence['type'] === 'circle'): ?>
                    geofenceLayer = L.circle([<?php echo $geofence['center_lat']; ?>, <?php echo $geofence['center_lng']; ?>], {
                        radius: <?php echo $geofence['radius']; ?>,
                        color: '<?php echo $geofence['color']; ?>',
                        fillColor: '<?php echo $geofence['fill_color']; ?>',
                        fillOpacity: <?php echo $geofence['fill_opacity']; ?>
                    }).addTo(map);
                    map.setView([<?php echo $geofence['center_lat']; ?>, <?php echo $geofence['center_lng']; ?>], 14);
                <?php elseif ($geofence['coordinates']): ?>
                    var coords = <?php echo $geofence['coordinates']; ?>;
                    var latlngs = coords.map(function(c) { return [c.lat, c.lng]; });
                    geofenceLayer = L.polygon(latlngs, {
                        color: '<?php echo $geofence['color']; ?>',
                        fillColor: '<?php echo $geofence['fill_color']; ?>',
                        fillOpacity: <?php echo $geofence['fill_opacity']; ?>
                    }).addTo(map);
                    map.fitBounds(geofenceLayer.getBounds());
                <?php endif; ?>
            <?php endif; ?>
            
            // Desenha geocerca
            map.on('click', function(e) {
                if (type === 'circle') {
                    drawCircle(e.latlng);
                } else {
                    addPolygonPoint(e.latlng);
                }
            });
            
            function drawCircle(latlng) {
                if (geofenceLayer) {
                    map.removeLayer(geofenceLayer);
                }
                
                var radius = parseInt($('#radius').val()) || 500;
                
                geofenceLayer = L.circle(latlng, {
                    radius: radius,
                    color: $('#color').val(),
                    fillColor: $('#fill_color').val(),
                    fillOpacity: 0.2
                }).addTo(map);
                
                $('#center_lat').val(latlng.lat);
                $('#center_lng').val(latlng.lng);
            }
            
            var polygonPoints = [];
            function addPolygonPoint(latlng) {
                polygonPoints.push(latlng);
                
                if (geofenceLayer) {
                    map.removeLayer(geofenceLayer);
                }
                
                if (polygonPoints.length >= 3) {
                    geofenceLayer = L.polygon(polygonPoints, {
                        color: $('#color').val(),
                        fillColor: $('#fill_color').val(),
                        fillOpacity: 0.2
                    }).addTo(map);
                    
                    var coords = polygonPoints.map(function(p) { return { lat: p.lat, lng: p.lng }; });
                    $('#coordinates').val(JSON.stringify(coords));
                }
            }
            
            // Tipo change
            $('#type').on('change', function() {
                type = $(this).val();
                
                if (geofenceLayer) {
                    map.removeLayer(geofenceLayer);
                    geofenceLayer = null;
                }
                polygonPoints = [];
                
                if (type === 'circle') {
                    $('.vt-circle-fields').show();
                } else {
                    $('.vt-circle-fields').hide();
                }
            });
            
            // Raio change
            $('#radius').on('input', function() {
                if (geofenceLayer && type === 'circle') {
                    geofenceLayer.setRadius(parseInt($(this).val()));
                }
            });
            
            // Cores change
            $('#color, #fill_color').on('input', function() {
                if (geofenceLayer) {
                    geofenceLayer.setStyle({
                        color: $('#color').val(),
                        fillColor: $('#fill_color').val()
                    });
                }
            });
        });
        </script>
        <?php
    }
}
