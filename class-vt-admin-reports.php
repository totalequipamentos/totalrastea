<?php
/**
 * Página de relatórios
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_Reports extends VT_Admin {
    
    public function render() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'alerts';
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                __('Relatórios', 'vehicle-tracker'),
                __('Análise de dados da sua frota', 'vehicle-tracker')
            ); ?>
            
            <nav class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=vt-reports&tab=alerts'); ?>" 
                   class="nav-tab <?php echo $tab === 'alerts' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Alertas', 'vehicle-tracker'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vt-reports&tab=fleet'); ?>" 
                   class="nav-tab <?php echo $tab === 'fleet' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Frota', 'vehicle-tracker'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=vt-reports&tab=commands'); ?>" 
                   class="nav-tab <?php echo $tab === 'commands' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Comandos', 'vehicle-tracker'); ?>
                </a>
            </nav>
            
            <div class="vt-tab-content">
                <?php
                switch ($tab) {
                    case 'fleet':
                        $this->render_fleet_report();
                        break;
                    case 'commands':
                        $this->render_commands_report();
                        break;
                    default:
                        $this->render_alerts_report();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    private function render_alerts_report() {
        $alert_model = new VT_Alert();
        $vehicle_model = new VT_Vehicle();
        $vehicles = $vehicle_model->get_all();
        
        $filters = array(
            'vehicle_id' => isset($_GET['vehicle']) ? intval($_GET['vehicle']) : null,
            'type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : null,
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null,
            'start_date' => isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-d 00:00:00', strtotime('-7 days')),
            'end_date' => isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-d 23:59:59'),
            'limit' => 100
        );
        
        $alerts = $alert_model->get_all($filters);
        $alert_types = VT_Alert::get_types();
        ?>
        
        <!-- Filtros -->
        <div class="vt-card vt-filters-card">
            <form method="get" class="vt-filter-form">
                <input type="hidden" name="page" value="vt-reports">
                <input type="hidden" name="tab" value="alerts">
                
                <div class="vt-form-row">
                    <div class="vt-form-group">
                        <label for="vehicle"><?php _e('Veículo', 'vehicle-tracker'); ?></label>
                        <select id="vehicle" name="vehicle">
                            <option value=""><?php _e('Todos', 'vehicle-tracker'); ?></option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?php echo esc_attr($v['id']); ?>" <?php selected($filters['vehicle_id'], $v['id']); ?>>
                                    <?php echo esc_html($v['plate']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="vt-form-group">
                        <label for="type"><?php _e('Tipo', 'vehicle-tracker'); ?></label>
                        <select id="type" name="type">
                            <option value=""><?php _e('Todos', 'vehicle-tracker'); ?></option>
                            <?php foreach ($alert_types as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($filters['type'], $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="vt-form-group">
                        <label for="status"><?php _e('Status', 'vehicle-tracker'); ?></label>
                        <select id="status" name="status">
                            <option value=""><?php _e('Todos', 'vehicle-tracker'); ?></option>
                            <option value="pending" <?php selected($filters['status'], 'pending'); ?>><?php _e('Pendente', 'vehicle-tracker'); ?></option>
                            <option value="read" <?php selected($filters['status'], 'read'); ?>><?php _e('Lido', 'vehicle-tracker'); ?></option>
                            <option value="resolved" <?php selected($filters['status'], 'resolved'); ?>><?php _e('Resolvido', 'vehicle-tracker'); ?></option>
                        </select>
                    </div>
                    
                    <div class="vt-form-group">
                        <label for="start"><?php _e('De', 'vehicle-tracker'); ?></label>
                        <input type="date" id="start" name="start" value="<?php echo esc_attr(date('Y-m-d', strtotime($filters['start_date']))); ?>">
                    </div>
                    
                    <div class="vt-form-group">
                        <label for="end"><?php _e('Até', 'vehicle-tracker'); ?></label>
                        <input type="date" id="end" name="end" value="<?php echo esc_attr(date('Y-m-d', strtotime($filters['end_date']))); ?>">
                    </div>
                    
                    <div class="vt-form-group vt-form-group-btn">
                        <button type="submit" class="button button-primary"><?php _e('Filtrar', 'vehicle-tracker'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Lista de Alertas -->
        <div class="vt-card">
            <?php if (empty($alerts)): ?>
                <?php $this->render_empty_state(__('Nenhum alerta encontrado', 'vehicle-tracker')); ?>
            <?php else: ?>
                <table class="vt-table">
                    <thead>
                        <tr>
                            <th><?php _e('Data/Hora', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Veículo', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Tipo', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Título', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Severidade', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Status', 'vehicle-tracker'); ?></th>
                            <th class="vt-col-actions"><?php _e('Ações', 'vehicle-tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                            <tr>
                                <td><?php echo $this->format_datetime($alert['created_at']); ?></td>
                                <td><?php echo esc_html($alert['plate']); ?></td>
                                <td><?php echo esc_html($alert_types[$alert['type']] ?? $alert['type']); ?></td>
                                <td><?php echo esc_html($alert['title']); ?></td>
                                <td><?php echo $this->get_severity_badge($alert['severity']); ?></td>
                                <td><?php echo $this->get_status_badge($alert['status']); ?></td>
                                <td class="vt-col-actions">
                                    <?php if ($alert['status'] === 'pending'): ?>
                                        <button type="button" class="button button-small vt-mark-read" data-id="<?php echo $alert['id']; ?>">
                                            <?php _e('Marcar Lido', 'vehicle-tracker'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.vt-mark-read').on('click', function() {
                var $btn = $(this);
                var id = $btn.data('id');
                
                $.ajax({
                    url: vt_ajax.api_url + 'alerts/' + id,
                    method: 'PUT',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', vt_ajax.nonce);
                    },
                    data: JSON.stringify({ action: 'read' }),
                    contentType: 'application/json',
                    success: function() {
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function render_fleet_report() {
        $vehicle_model = new VT_Vehicle();
        $vehicles = $vehicle_model->get_all();
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php _e('Resumo da Frota', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <table class="vt-table">
                    <thead>
                        <tr>
                            <th><?php _e('Veículo', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Status', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Odômetro', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Última Posição', 'vehicle-tracker'); ?></th>
                            <th><?php _e('Última Velocidade', 'vehicle-tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($v['plate']); ?></strong><br>
                                    <small><?php echo esc_html($v['brand'] . ' ' . $v['model']); ?></small>
                                </td>
                                <td><?php echo $this->get_status_badge($v['status']); ?></td>
                                <td><?php echo number_format($v['odometer'], 1); ?> km</td>
                                <td><?php echo $v['last_update'] ? $this->format_datetime($v['last_update']) : '-'; ?></td>
                                <td><?php echo $v['last_speed'] ?? 0; ?> km/h</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private function render_commands_report() {
        global $wpdb;
        $db = new VT_Database();
        
        $commands = $wpdb->get_results(
            "SELECT c.*, v.plate FROM {$db->commands_table} c 
             LEFT JOIN {$db->vehicles_table} v ON c.vehicle_id = v.id 
             ORDER BY c.created_at DESC LIMIT 100",
            ARRAY_A
        );
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php _e('Histórico de Comandos', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <?php if (empty($commands)): ?>
                    <?php $this->render_empty_state(__('Nenhum comando registrado', 'vehicle-tracker')); ?>
                <?php else: ?>
                    <table class="vt-table">
                        <thead>
                            <tr>
                                <th><?php _e('Data/Hora', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Veículo', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Comando', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Enviado Via', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Status', 'vehicle-tracker'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commands as $cmd): ?>
                                <tr>
                                    <td><?php echo $this->format_datetime($cmd['created_at']); ?></td>
                                    <td><?php echo esc_html($cmd['plate']); ?></td>
                                    <td><?php echo esc_html($cmd['command']); ?></td>
                                    <td><?php echo esc_html(strtoupper($cmd['sent_via'])); ?></td>
                                    <td><?php echo $this->get_status_badge($cmd['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
