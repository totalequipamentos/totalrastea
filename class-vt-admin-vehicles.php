<?php
/**
 * Página de gestão de veículos
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_Vehicles extends VT_Admin {
    
    public function render() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'add':
                $this->render_form();
                break;
            case 'edit':
                $this->render_form($vehicle_id);
                break;
            case 'delete':
                $this->handle_delete($vehicle_id);
                break;
            default:
                $this->render_list();
        }
    }
    
    /**
     * Lista de veículos
     */
    private function render_list() {
        $vehicle_model = new VT_Vehicle();
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $vehicles = $vehicle_model->get_all(array(
            'search' => $search,
            'status' => $status
        ));
        
        $counts = $vehicle_model->count_by_status();
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                __('Veículos', 'vehicle-tracker'),
                sprintf(__('%d veículos cadastrados', 'vehicle-tracker'), $counts['total']),
                array(
                    array(
                        'url' => admin_url('admin.php?page=vt-vehicles&action=add'),
                        'label' => __('Adicionar Veículo', 'vehicle-tracker'),
                        'icon' => 'dashicons-plus-alt',
                        'class' => 'button-primary'
                    ),
                    array(
                        'url' => '#',
                        'label' => __('Sincronizar Avatek', 'vehicle-tracker'),
                        'icon' => 'dashicons-update',
                        'class' => 'button-secondary vt-sync-avatek'
                    )
                )
            ); ?>
            
            <!-- Filtros -->
            <div class="vt-filters">
                <ul class="vt-filter-tabs">
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=vt-vehicles'); ?>" 
                           class="<?php echo empty($status) ? 'active' : ''; ?>">
                            <?php _e('Todos', 'vehicle-tracker'); ?>
                            <span class="count">(<?php echo $counts['total']; ?>)</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=vt-vehicles&status=active'); ?>" 
                           class="<?php echo $status === 'active' ? 'active' : ''; ?>">
                            <?php _e('Ativos', 'vehicle-tracker'); ?>
                            <span class="count">(<?php echo $counts['active']; ?>)</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=vt-vehicles&status=blocked'); ?>" 
                           class="<?php echo $status === 'blocked' ? 'active' : ''; ?>">
                            <?php _e('Bloqueados', 'vehicle-tracker'); ?>
                            <span class="count">(<?php echo $counts['blocked']; ?>)</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=vt-vehicles&status=inactive'); ?>" 
                           class="<?php echo $status === 'inactive' ? 'active' : ''; ?>">
                            <?php _e('Inativos', 'vehicle-tracker'); ?>
                            <span class="count">(<?php echo $counts['inactive']; ?>)</span>
                        </a>
                    </li>
                </ul>
                
                <form method="get" class="vt-search-form">
                    <input type="hidden" name="page" value="vt-vehicles">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Buscar por placa, IMEI...', 'vehicle-tracker'); ?>">
                    <button type="submit" class="button"><?php _e('Buscar', 'vehicle-tracker'); ?></button>
                </form>
            </div>
            
            <!-- Tabela -->
            <div class="vt-card">
                <?php if (empty($vehicles)): ?>
                    <?php $this->render_empty_state(
                        __('Nenhum veículo encontrado', 'vehicle-tracker'),
                        array(
                            'url' => admin_url('admin.php?page=vt-vehicles&action=add'),
                            'label' => __('Adicionar Veículo', 'vehicle-tracker')
                        )
                    ); ?>
                <?php else: ?>
                    <table class="vt-table">
                        <thead>
                            <tr>
                                <th><?php _e('Veículo', 'vehicle-tracker'); ?></th>
                                <th><?php _e('IMEI', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Dispositivo', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Operadora', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Status', 'vehicle-tracker'); ?></th>
                                <th><?php _e('Última Atualização', 'vehicle-tracker'); ?></th>
                                <th class="vt-col-actions"><?php _e('Ações', 'vehicle-tracker'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td>
                                        <div class="vt-vehicle-info">
                                            <span class="vt-vehicle-plate"><?php echo esc_html($vehicle['plate']); ?></span>
                                            <span class="vt-vehicle-model">
                                                <?php echo esc_html($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><code><?php echo esc_html($vehicle['imei']); ?></code></td>
                                    <td><?php echo esc_html($vehicle['device_model']); ?></td>
                                    <td><?php echo esc_html($vehicle['sim_operator'] ?: 'TIM'); ?></td>
                                    <td>
                                        <?php echo $this->get_status_badge($vehicle['status']); ?>
                                        <?php if ($vehicle['is_online']): ?>
                                            <?php echo $this->get_status_badge($vehicle['is_moving'] ? 'moving' : 'stopped'); ?>
                                        <?php else: ?>
                                            <?php echo $this->get_status_badge('offline'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $vehicle['last_update'] ? $this->format_datetime($vehicle['last_update']) : '-'; ?>
                                    </td>
                                    <td class="vt-col-actions">
                                        <div class="vt-actions">
                                            <a href="<?php echo admin_url('admin.php?page=vt-tracking&vehicle=' . $vehicle['id']); ?>" 
                                               class="vt-action" title="<?php _e('Rastrear', 'vehicle-tracker'); ?>">
                                                <span class="dashicons dashicons-location-alt"></span>
                                            </a>
                                            <a href="<?php echo admin_url('admin.php?page=vt-vehicles&action=edit&id=' . $vehicle['id']); ?>" 
                                               class="vt-action" title="<?php _e('Editar', 'vehicle-tracker'); ?>">
                                                <span class="dashicons dashicons-edit"></span>
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vt-vehicles&action=delete&id=' . $vehicle['id']), 'delete_vehicle_' . $vehicle['id']); ?>" 
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
        </div>
        <?php
    }
    
    /**
     * Formulário de veículo
     */
    private function render_form($id = 0) {
        $vehicle = null;
        $is_edit = $id > 0;
        
        if ($is_edit) {
            $vehicle_model = new VT_Vehicle();
            $vehicle = $vehicle_model->get($id);
            
            if (!$vehicle) {
                wp_redirect(admin_url('admin.php?page=vt-vehicles'));
                exit;
            }
        }
        
        // Processa formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vt_vehicle_nonce'])) {
            if (wp_verify_nonce($_POST['vt_vehicle_nonce'], 'vt_save_vehicle')) {
                $this->handle_save($id);
            }
        }
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                $is_edit ? __('Editar Veículo', 'vehicle-tracker') : __('Adicionar Veículo', 'vehicle-tracker'),
                $is_edit ? $vehicle['plate'] : ''
            ); ?>
            
            <form method="post" class="vt-form">
                <?php wp_nonce_field('vt_save_vehicle', 'vt_vehicle_nonce'); ?>
                
                <div class="vt-form-grid">
                    <!-- Informações do Veículo -->
                    <div class="vt-card">
                        <div class="vt-card-header">
                            <h3><?php _e('Informações do Veículo', 'vehicle-tracker'); ?></h3>
                        </div>
                        <div class="vt-card-body">
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="plate"><?php _e('Placa', 'vehicle-tracker'); ?> *</label>
                                    <input type="text" id="plate" name="plate" required
                                           value="<?php echo esc_attr($vehicle['plate'] ?? ''); ?>"
                                           placeholder="ABC-1234">
                                </div>
                                <div class="vt-form-group">
                                    <label for="brand"><?php _e('Marca', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="brand" name="brand"
                                           value="<?php echo esc_attr($vehicle['brand'] ?? ''); ?>">
                                </div>
                                <div class="vt-form-group">
                                    <label for="model"><?php _e('Modelo', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="model" name="model"
                                           value="<?php echo esc_attr($vehicle['model'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="year"><?php _e('Ano', 'vehicle-tracker'); ?></label>
                                    <input type="number" id="year" name="year" min="1990" max="2030"
                                           value="<?php echo esc_attr($vehicle['year'] ?? ''); ?>">
                                </div>
                                <div class="vt-form-group">
                                    <label for="color"><?php _e('Cor', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="color" name="color"
                                           value="<?php echo esc_attr($vehicle['color'] ?? ''); ?>">
                                </div>
                                <div class="vt-form-group">
                                    <label for="chassis"><?php _e('Chassi', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="chassis" name="chassis"
                                           value="<?php echo esc_attr($vehicle['chassis'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="renavam"><?php _e('Renavam', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="renavam" name="renavam"
                                           value="<?php echo esc_attr($vehicle['renavam'] ?? ''); ?>">
                                </div>
                                <div class="vt-form-group">
                                    <label for="odometer"><?php _e('Odômetro (km)', 'vehicle-tracker'); ?></label>
                                    <input type="number" id="odometer" name="odometer" step="0.01"
                                           value="<?php echo esc_attr($vehicle['odometer'] ?? '0'); ?>">
                                </div>
                                <div class="vt-form-group">
                                    <label for="fuel_capacity"><?php _e('Capacidade Tanque (L)', 'vehicle-tracker'); ?></label>
                                    <input type="number" id="fuel_capacity" name="fuel_capacity" step="0.01"
                                           value="<?php echo esc_attr($vehicle['fuel_capacity'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dispositivo Rastreador -->
                    <div class="vt-card">
                        <div class="vt-card-header">
                            <h3><?php _e('Dispositivo Rastreador', 'vehicle-tracker'); ?></h3>
                        </div>
                        <div class="vt-card-body">
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="imei"><?php _e('IMEI', 'vehicle-tracker'); ?> *</label>
                                    <input type="text" id="imei" name="imei" required
                                           value="<?php echo esc_attr($vehicle['imei'] ?? ''); ?>"
                                           placeholder="000000000000000" maxlength="15"
                                           <?php echo $is_edit ? 'readonly' : ''; ?>>
                                    <p class="description"><?php _e('15 dígitos do IMEI do rastreador', 'vehicle-tracker'); ?></p>
                                </div>
                                <div class="vt-form-group">
                                    <label for="device_model"><?php _e('Modelo do Dispositivo', 'vehicle-tracker'); ?></label>
                                    <select id="device_model" name="device_model">
                                        <option value="ST8310UM" <?php selected($vehicle['device_model'] ?? '', 'ST8310UM'); ?>>
                                            Suntech ST8310UM (4G LTE)
                                        </option>
                                        <option value="ST300" <?php selected($vehicle['device_model'] ?? '', 'ST300'); ?>>
                                            Suntech ST300
                                        </option>
                                        <option value="ST340" <?php selected($vehicle['device_model'] ?? '', 'ST340'); ?>>
                                            Suntech ST340
                                        </option>
                                        <option value="ST4340" <?php selected($vehicle['device_model'] ?? '', 'ST4340'); ?>>
                                            Suntech ST4340
                                        </option>
                                        <option value="other" <?php selected($vehicle['device_model'] ?? '', 'other'); ?>>
                                            Outro
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="device_serial"><?php _e('Número de Série', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="device_serial" name="device_serial"
                                           value="<?php echo esc_attr($vehicle['device_serial'] ?? ''); ?>">
                                </div>
                                <div class="vt-form-group">
                                    <label for="avatek_id"><?php _e('ID Avatek', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="avatek_id" name="avatek_id"
                                           value="<?php echo esc_attr($vehicle['avatek_id'] ?? ''); ?>">
                                    <p class="description"><?php _e('ID do veículo na plataforma Avatek (se utilizado)', 'vehicle-tracker'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chip/SIM -->
                    <div class="vt-card">
                        <div class="vt-card-header">
                            <h3><?php _e('Chip de Dados', 'vehicle-tracker'); ?></h3>
                        </div>
                        <div class="vt-card-body">
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="sim_operator"><?php _e('Operadora', 'vehicle-tracker'); ?></label>
                                    <select id="sim_operator" name="sim_operator">
                                        <option value="TIM" <?php selected($vehicle['sim_operator'] ?? 'TIM', 'TIM'); ?>>TIM</option>
                                        <option value="Vivo" <?php selected($vehicle['sim_operator'] ?? '', 'Vivo'); ?>>Vivo</option>
                                        <option value="Claro" <?php selected($vehicle['sim_operator'] ?? '', 'Claro'); ?>>Claro</option>
                                        <option value="Oi" <?php selected($vehicle['sim_operator'] ?? '', 'Oi'); ?>>Oi</option>
                                        <option value="other" <?php selected($vehicle['sim_operator'] ?? '', 'other'); ?>>Outra</option>
                                    </select>
                                </div>
                                <div class="vt-form-group">
                                    <label for="sim_phone"><?php _e('Número da Linha', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="sim_phone" name="sim_phone"
                                           value="<?php echo esc_attr($vehicle['sim_phone'] ?? ''); ?>"
                                           placeholder="(11) 99999-9999">
                                </div>
                            </div>
                            
                            <div class="vt-form-row">
                                <div class="vt-form-group vt-form-group-full">
                                    <label for="sim_iccid"><?php _e('ICCID do Chip', 'vehicle-tracker'); ?></label>
                                    <input type="text" id="sim_iccid" name="sim_iccid"
                                           value="<?php echo esc_attr($vehicle['sim_iccid'] ?? ''); ?>"
                                           placeholder="00000000000000000000">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configurações -->
                    <div class="vt-card">
                        <div class="vt-card-header">
                            <h3><?php _e('Configurações', 'vehicle-tracker'); ?></h3>
                        </div>
                        <div class="vt-card-body">
                            <div class="vt-form-row">
                                <div class="vt-form-group">
                                    <label for="status"><?php _e('Status', 'vehicle-tracker'); ?></label>
                                    <select id="status" name="status">
                                        <option value="active" <?php selected($vehicle['status'] ?? 'active', 'active'); ?>>
                                            <?php _e('Ativo', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="inactive" <?php selected($vehicle['status'] ?? '', 'inactive'); ?>>
                                            <?php _e('Inativo', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="blocked" <?php selected($vehicle['status'] ?? '', 'blocked'); ?>>
                                            <?php _e('Bloqueado', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="maintenance" <?php selected($vehicle['status'] ?? '', 'maintenance'); ?>>
                                            <?php _e('Em Manutenção', 'vehicle-tracker'); ?>
                                        </option>
                                    </select>
                                </div>
                                <div class="vt-form-group">
                                    <label for="icon"><?php _e('Ícone no Mapa', 'vehicle-tracker'); ?></label>
                                    <select id="icon" name="icon">
                                        <option value="car" <?php selected($vehicle['icon'] ?? 'car', 'car'); ?>>
                                            <?php _e('Carro', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="truck" <?php selected($vehicle['icon'] ?? '', 'truck'); ?>>
                                            <?php _e('Caminhão', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="motorcycle" <?php selected($vehicle['icon'] ?? '', 'motorcycle'); ?>>
                                            <?php _e('Moto', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="bus" <?php selected($vehicle['icon'] ?? '', 'bus'); ?>>
                                            <?php _e('Ônibus', 'vehicle-tracker'); ?>
                                        </option>
                                        <option value="van" <?php selected($vehicle['icon'] ?? '', 'van'); ?>>
                                            <?php _e('Van', 'vehicle-tracker'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="vt-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=vt-vehicles'); ?>" class="button">
                        <?php _e('Cancelar', 'vehicle-tracker'); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? __('Atualizar Veículo', 'vehicle-tracker') : __('Cadastrar Veículo', 'vehicle-tracker'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Processa salvamento
     */
    private function handle_save($id) {
        $vehicle_model = new VT_Vehicle();
        
        $data = array(
            'plate' => sanitize_text_field($_POST['plate'] ?? ''),
            'brand' => sanitize_text_field($_POST['brand'] ?? ''),
            'model' => sanitize_text_field($_POST['model'] ?? ''),
            'year' => intval($_POST['year'] ?? 0) ?: null,
            'color' => sanitize_text_field($_POST['color'] ?? ''),
            'chassis' => sanitize_text_field($_POST['chassis'] ?? ''),
            'renavam' => sanitize_text_field($_POST['renavam'] ?? ''),
            'device_model' => sanitize_text_field($_POST['device_model'] ?? 'ST8310UM'),
            'device_serial' => sanitize_text_field($_POST['device_serial'] ?? ''),
            'sim_iccid' => sanitize_text_field($_POST['sim_iccid'] ?? ''),
            'sim_phone' => sanitize_text_field($_POST['sim_phone'] ?? ''),
            'sim_operator' => sanitize_text_field($_POST['sim_operator'] ?? 'TIM'),
            'avatek_id' => sanitize_text_field($_POST['avatek_id'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'icon' => sanitize_text_field($_POST['icon'] ?? 'car'),
            'odometer' => floatval($_POST['odometer'] ?? 0),
            'fuel_capacity' => floatval($_POST['fuel_capacity'] ?? 0) ?: null
        );
        
        if ($id > 0) {
            $result = $vehicle_model->update($id, $data);
            $message = $result !== false ? 'updated' : 'error';
        } else {
            $data['imei'] = sanitize_text_field($_POST['imei'] ?? '');
            $result = $vehicle_model->create($data);
            $message = !is_wp_error($result) ? 'created' : 'error';
        }
        
        wp_redirect(admin_url('admin.php?page=vt-vehicles&message=' . $message));
        exit;
    }
    
    /**
     * Processa exclusão
     */
    private function handle_delete($id) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_vehicle_' . $id)) {
            wp_die(__('Ação não autorizada', 'vehicle-tracker'));
        }
        
        $vehicle_model = new VT_Vehicle();
        $result = $vehicle_model->delete($id);
        
        wp_redirect(admin_url('admin.php?page=vt-vehicles&message=' . ($result ? 'deleted' : 'error')));
        exit;
    }
}
