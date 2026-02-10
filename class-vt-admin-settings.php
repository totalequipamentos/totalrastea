<?php
/**
 * Admin settings page
 * 
 * @package VehicleTracker
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure WordPress functions are available
if (!function_exists('current_user_can')) {
    wp_die('WordPress functions not available. This file must be included within WordPress.');
}

/**
 * Admin Settings Class
 * 
 * IMPORTANTE: A classe VT_Admin DEVE estar carregada ANTES desta classe.
 * Isso é garantido pela ordem de carregamento em vehicle-tracker.php
 */
class VT_Admin_Settings extends VT_Admin {

    /**
     * Main render method
     */
    public function render() {
        // Verifica permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'vehicle-tracker'));
        }

        // Processa formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->process_form();
        }

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        ?>
        <div class="wrap vt-admin-wrap">
            <?php $this->render_page_header(
                __('Configurações', 'vehicle-tracker'),
                __('Configure as integrações e parâmetros do sistema', 'vehicle-tracker')
            ); ?>

            <nav class="nav-tab-wrapper" id="vt-tabs">
                <?php $this->render_tabs($tab); ?>
            </nav>

            <form method="post" action="" class="vt-settings-form">
                <?php 
                wp_nonce_field('vt_save_settings', 'vt_settings_nonce'); 
                ?>
                <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">

                <div class="vt-tab-content" style="margin-top: 20px;">
                    <?php $this->render_tab_content($tab); ?>
                </div>

                <div class="vt-form-actions" style="margin-top: 20px; padding: 20px 0; border-top: 1px solid #ddd;">
                    <?php submit_button(__('Salvar Configurações', 'vehicle-tracker'), 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Process form submission
     */
    private function process_form() {
        if (!isset($_POST['vt_settings_nonce']) || !wp_verify_nonce($_POST['vt_settings_nonce'], 'vt_save_settings')) {
            add_settings_error('vt_messages', 'vt_nonce_error', __('Erro de verificação de segurança.', 'vehicle-tracker'), 'error');
            return;
        }

        if (!current_user_can('manage_options')) {
            add_settings_error('vt_messages', 'vt_permission_error', __('Você não tem permissão para salvar configurações.', 'vehicle-tracker'), 'error');
            return;
        }

        $this->save_settings();
        // Mensagem de sucesso é tratada dentro de save_settings()
    }

    /**
     * Render navigation tabs
     */
    private function render_tabs($current_tab) {
        $tabs = array(
            'general' => __('Geral', 'vehicle-tracker'),
            'avatek'  => __('API Avatek', 'vehicle-tracker'),
            'tcp'     => __('Servidor TCP', 'vehicle-tracker'),
            'maps'    => __('Mapas', 'vehicle-tracker'),
            'alerts'  => __('Alertas', 'vehicle-tracker')
        );

        foreach ($tabs as $tab => $label) {
            $url = add_query_arg(array(
                'page' => 'vt-settings',
                'tab'  => $tab
            ), admin_url('admin.php'));
            
            $active = ($current_tab === $tab) ? 'nav-tab-active' : '';
            
            printf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url($url),
                esc_attr($active),
                esc_html($label)
            );
        }
    }

    /**
     * Render tab content
     */
    private function render_tab_content($tab) {
        settings_errors('vt_messages');
        
        switch ($tab) {
            case 'avatek':
                $this->render_avatek_settings();
                break;
            case 'tcp':
                $this->render_tcp_settings();
                break;
            case 'maps':
                $this->render_maps_settings();
                break;
            case 'alerts':
                $this->render_alerts_settings();
                break;
            default:
                $this->render_general_settings();
        }
    }

    /**
     * General Settings Tab
     */
    private function render_general_settings() {
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Configurações Gerais', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="vt_company_name"><?php esc_html_e('Nome da Empresa', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_company_name" 
                                       name="vt_company_name" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_company_name', '')); ?>">
                                <p class="description">
                                    <?php esc_html_e('Nome exibido nos relatórios e cabeçalhos', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_timezone"><?php esc_html_e('Fuso Horário', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <select id="vt_timezone" name="vt_timezone" class="regular-text">
                                    <option value="-3" <?php selected(get_option('vt_timezone', '-3'), '-3'); ?>>
                                        Brasília (GMT-3)
                                    </option>
                                    <option value="-4" <?php selected(get_option('vt_timezone', '-3'), '-4'); ?>>
                                        Manaus (GMT-4)
                                    </option>
                                    <option value="-5" <?php selected(get_option('vt_timezone', '-3'), '-5'); ?>>
                                        Acre (GMT-5)
                                    </option>
                                    <option value="-2" <?php selected(get_option('vt_timezone', '-3'), '-2'); ?>>
                                        Fernando de Noronha (GMT-2)
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_refresh_interval">
                                    <?php esc_html_e('Intervalo de Atualização (segundos)', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_refresh_interval" 
                                       name="vt_refresh_interval" 
                                       min="10" 
                                       max="300"
                                       value="<?php echo esc_attr(get_option('vt_refresh_interval', '30')); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Intervalo para atualização automática do mapa (mínimo: 10s)', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_offline_threshold">
                                    <?php esc_html_e('Tempo para considerar Offline (segundos)', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_offline_threshold" 
                                       name="vt_offline_threshold" 
                                       min="60" 
                                       max="3600"
                                       value="<?php echo esc_attr(get_option('vt_offline_threshold', '300')); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Tempo sem comunicação para marcar veículo como offline', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_data_retention">
                                    <?php esc_html_e('Retenção de Dados (dias)', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_data_retention" 
                                       name="vt_data_retention" 
                                       min="30" 
                                       max="365"
                                       value="<?php echo esc_attr(get_option('vt_data_retention', '90')); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Número de dias para manter histórico de posições', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Avatek API Settings Tab
     */
    private function render_avatek_settings() {
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Integração API Avatek', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
                    <p>
                        <?php esc_html_e('Configure suas credenciais da API Avatek para sincronização de dados.', 'vehicle-tracker'); ?>
                        <br>
                        <?php esc_html_e('Documentação: ', 'vehicle-tracker'); ?>
                        <a href="https://avatek.docs.apiary.io/" target="_blank" rel="noopener noreferrer">
                            https://avatek.docs.apiary.io/
                        </a>
                    </p>
                </div>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Ativar Integração', 'vehicle-tracker'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="vt_avatek_enabled" 
                                           name="vt_avatek_enabled" 
                                           value="1"
                                           <?php checked(get_option('vt_avatek_enabled', '0'), '1'); ?>>
                                    <?php esc_html_e('Habilitar sincronização com API Avatek', 'vehicle-tracker'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_avatek_api_url"><?php esc_html_e('URL da API', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="vt_avatek_api_url" 
                                       name="vt_avatek_api_url" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_avatek_api_url', 'https://api.avatek.com.br')); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_avatek_api_key"><?php esc_html_e('API Key', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_avatek_api_key" 
                                       name="vt_avatek_api_key" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_avatek_api_key', '')); ?>"
                                       autocomplete="off">
                                <p class="description">
                                    <?php esc_html_e('Chave de API fornecida pela Avatek', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_avatek_api_secret"><?php esc_html_e('API Secret', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="vt_avatek_api_secret" 
                                       name="vt_avatek_api_secret" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_avatek_api_secret', '')); ?>"
                                       autocomplete="new-password">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_avatek_username"><?php esc_html_e('Usuário', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_avatek_username" 
                                       name="vt_avatek_username" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_avatek_username', '')); ?>"
                                       autocomplete="off">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_avatek_password"><?php esc_html_e('Senha', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="vt_avatek_password" 
                                       name="vt_avatek_password" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_avatek_password', '')); ?>"
                                       autocomplete="new-password">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_avatek_sync_interval">
                                    <?php esc_html_e('Intervalo de Sincronização', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <select id="vt_avatek_sync_interval" name="vt_avatek_sync_interval">
                                    <option value="1" <?php selected(get_option('vt_avatek_sync_interval', '5'), '1'); ?>>
                                        1 minuto
                                    </option>
                                    <option value="5" <?php selected(get_option('vt_avatek_sync_interval', '5'), '5'); ?>>
                                        5 minutos
                                    </option>
                                    <option value="10" <?php selected(get_option('vt_avatek_sync_interval', '5'), '10'); ?>>
                                        10 minutos
                                    </option>
                                    <option value="15" <?php selected(get_option('vt_avatek_sync_interval', '5'), '15'); ?>>
                                        15 minutos
                                    </option>
                                    <option value="30" <?php selected(get_option('vt_avatek_sync_interval', '5'), '30'); ?>>
                                        30 minutos
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Status da Conexão', 'vehicle-tracker'); ?></th>
                            <td>
                                <?php
                                $api_key = get_option('vt_avatek_api_key', '');
                                if (!empty($api_key)) {
                                    echo '<span class="vt-badge vt-badge-success">' . 
                                         esc_html__('Configurado', 'vehicle-tracker') . '</span>';
                                } else {
                                    echo '<span class="vt-badge vt-badge-inactive">' . 
                                         esc_html__('Não configurado', 'vehicle-tracker') . '</span>';
                                }
                                ?>
                                <button type="button" id="vt-test-avatek" class="button" style="margin-left: 10px;">
                                    <?php esc_html_e('Testar Conexão', 'vehicle-tracker'); ?>
                                </button>
                                <button type="button" id="vt-sync-avatek" class="button" style="margin-left: 5px;">
                                    <?php esc_html_e('Sincronizar Agora', 'vehicle-tracker'); ?>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * TCP Server Settings Tab
     */
    private function render_tcp_settings() {
        $server_host = get_option('vt_tcp_server_host', '0.0.0.0');
        $server_port = get_option('vt_tcp_server_port', '5000');
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Servidor TCP/UDP', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
                    <p>
                        <?php esc_html_e('O servidor TCP deve ser executado como processo separado no servidor.', 'vehicle-tracker'); ?>
                        <br>
                        <?php esc_html_e('Configure aqui os parâmetros que os rastreadores Suntech irão usar para conexão.', 'vehicle-tracker'); ?>
                    </p>
                </div>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Servidor TCP', 'vehicle-tracker'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="vt_tcp_server_enabled" 
                                           name="vt_tcp_server_enabled" 
                                           value="1"
                                           <?php checked(get_option('vt_tcp_server_enabled', '0'), '1'); ?>>
                                    <?php esc_html_e('Receber dados direto dos rastreadores via TCP', 'vehicle-tracker'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_tcp_server_host">
                                    <?php esc_html_e('Endereço do Servidor', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_tcp_server_host" 
                                       name="vt_tcp_server_host" 
                                       class="regular-text"
                                       value="<?php echo esc_attr($server_host); ?>">
                                <p class="description">
                                    <?php esc_html_e('IP externo ou 0.0.0.0 para todas as interfaces', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_tcp_server_port">
                                    <?php esc_html_e('Porta TCP', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_tcp_server_port" 
                                       name="vt_tcp_server_port" 
                                       min="1024" 
                                       max="65535"
                                       value="<?php echo esc_attr($server_port); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Porta que os rastreadores irão conectar (padrão: 5000)', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Webhook URL', 'vehicle-tracker'); ?></th>
                            <td>
                                <code style="padding: 8px 12px; display: inline-block; background: #f0f0f0; border-radius: 4px;">
                                    <?php echo esc_html(rest_url('vehicle-tracker/v1/webhook/position')); ?>
                                </code>
                                <p class="description">
                                    <?php esc_html_e('Use esta URL para receber dados via HTTP POST de gateways externos', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_webhook_api_key">
                                    <?php esc_html_e('Webhook API Key', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_webhook_api_key" 
                                       name="vt_webhook_api_key" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_webhook_api_key', '')); ?>">
                                <p class="description">
                                    <?php esc_html_e('Chave de autenticação para o webhook (Header: X-API-Key)', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Configuração do Rastreador Suntech ST8310', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <p><?php esc_html_e('Configure o rastreador Suntech ST8310UM para enviar dados usando os seguintes parâmetros:', 'vehicle-tracker'); ?></p>

                <table class="widefat striped" style="max-width: 600px; margin: 20px 0;">
                    <tbody>
                        <tr>
                            <th style="width: 200px;"><?php esc_html_e('Servidor:', 'vehicle-tracker'); ?></th>
                            <td><strong><?php echo esc_html($server_host); ?></strong></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Porta:', 'vehicle-tracker'); ?></th>
                            <td><strong><?php echo esc_html($server_port); ?></strong></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Protocolo:', 'vehicle-tracker'); ?></th>
                            <td><strong>TCP</strong></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('APN (TIM):', 'vehicle-tracker'); ?></th>
                            <td><strong>timbrasil.br</strong></td>
                        </tr>
                    </tbody>
                </table>

                <h4 style="margin-top: 20px;"><?php esc_html_e('Comandos AT para configuração:', 'vehicle-tracker'); ?></h4>
                <pre style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px;"># Configurar APN da TIM
AT+CAPN=0,"timbrasil.br","tim","tim"

# Configurar servidor TCP
AT+CSRV=0,"<?php echo esc_html($server_host); ?>",<?php echo esc_html($server_port); ?>,0

# Ativar GPS
AT+CGPS=1

# Intervalo de envio (60 segundos)
AT+CSTM=60

# Salvar configurações
AT+CSAV</pre>
            </div>
        </div>
        <?php
    }

    /**
     * Maps Settings Tab
     */
    private function render_maps_settings() {
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Configurações de Mapas', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="vt_map_provider"><?php esc_html_e('Provedor de Mapas', 'vehicle-tracker'); ?></label>
                            </th>
                            <td>
                                <select id="vt_map_provider" name="vt_map_provider">
                                    <option value="osm" <?php selected(get_option('vt_map_provider', 'osm'), 'osm'); ?>>
                                        OpenStreetMap (Gratuito)
                                    </option>
                                    <option value="google" <?php selected(get_option('vt_map_provider', 'osm'), 'google'); ?>>
                                        Google Maps (Requer API Key)
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('OpenStreetMap é gratuito. Google Maps oferece mais recursos mas requer API Key paga.', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_google_maps_key">
                                    <?php esc_html_e('Google Maps API Key', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_google_maps_key" 
                                       name="vt_google_maps_key" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_google_maps_key', '')); ?>">
                                <p class="description">
                                    <?php esc_html_e('Obtenha sua chave em: ', 'vehicle-tracker'); ?>
                                    <a href="https://console.cloud.google.com/apis/credentials" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        Google Cloud Console
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_default_center_lat">
                                    <?php esc_html_e('Centro Padrão do Mapa', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="vt_default_center_lat" 
                                       name="vt_default_center_lat" 
                                       placeholder="Latitude"
                                       value="<?php echo esc_attr(get_option('vt_default_center_lat', '-15.7801')); ?>" 
                                       style="width: 120px;">
                                <input type="text" 
                                       id="vt_default_center_lng" 
                                       name="vt_default_center_lng" 
                                       placeholder="Longitude"
                                       value="<?php echo esc_attr(get_option('vt_default_center_lng', '-47.9292')); ?>" 
                                       style="width: 120px;">
                                <p class="description">
                                    <?php esc_html_e('Coordenadas do centro inicial do mapa (padrão: Brasília)', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_default_zoom">
                                    <?php esc_html_e('Zoom Padrão', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_default_zoom" 
                                       name="vt_default_zoom" 
                                       min="1" 
                                       max="20"
                                       value="<?php echo esc_attr(get_option('vt_default_zoom', '12')); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Nível de zoom inicial (1-20)', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Opções de Exibição', 'vehicle-tracker'); ?></th>
                            <td>
                                <fieldset>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_map_cluster" value="1"
                                               <?php checked(get_option('vt_map_cluster', '1'), '1'); ?>>
                                        <?php esc_html_e('Agrupar marcadores próximos (cluster)', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_map_traffic" value="1"
                                               <?php checked(get_option('vt_map_traffic', '0'), '1'); ?>>
                                        <?php esc_html_e('Mostrar camada de tráfego', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block;">
                                        <input type="checkbox" name="vt_map_fullscreen" value="1"
                                               <?php checked(get_option('vt_map_fullscreen', '1'), '1'); ?>>
                                        <?php esc_html_e('Permitir tela cheia', 'vehicle-tracker'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Alerts Settings Tab
     */
    private function render_alerts_settings() {
        ?>
        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Configurações de Alertas', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="vt_speed_limit">
                                    <?php esc_html_e('Limite de Velocidade Padrão (km/h)', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_speed_limit" 
                                       name="vt_speed_limit" 
                                       min="20" 
                                       max="200"
                                       value="<?php echo esc_attr(get_option('vt_speed_limit', '80')); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Velocidade máxima antes de gerar alerta (pode ser ajustado por veículo)', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_idle_timeout">
                                    <?php esc_html_e('Tempo de Ociosidade (segundos)', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="vt_idle_timeout" 
                                       name="vt_idle_timeout" 
                                       min="60" 
                                       max="3600"
                                       value="<?php echo esc_attr(get_option('vt_idle_timeout', '300')); ?>" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Tempo parado com ignição ligada para gerar alerta de ociosidade', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Tipos de Alertas', 'vehicle-tracker'); ?></th>
                            <td>
                                <fieldset>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_alert_speed" value="1"
                                               <?php checked(get_option('vt_alert_speed', '1'), '1'); ?>>
                                        <?php esc_html_e('Excesso de velocidade', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_alert_geofence" value="1"
                                               <?php checked(get_option('vt_alert_geofence', '1'), '1'); ?>>
                                        <?php esc_html_e('Entrada/saída de geocercas', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_alert_ignition" value="1"
                                               <?php checked(get_option('vt_alert_ignition', '1'), '1'); ?>>
                                        <?php esc_html_e('Ignição ligada/desligada', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_alert_battery" value="1"
                                               <?php checked(get_option('vt_alert_battery', '1'), '1'); ?>>
                                        <?php esc_html_e('Bateria baixa', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="vt_alert_sos" value="1"
                                               <?php checked(get_option('vt_alert_sos', '1'), '1'); ?>>
                                        <?php esc_html_e('Botão de pânico/SOS', 'vehicle-tracker'); ?>
                                    </label>
                                    <label style="display: block;">
                                        <input type="checkbox" name="vt_alert_offline" value="1"
                                               <?php checked(get_option('vt_alert_offline', '1'), '1'); ?>>
                                        <?php esc_html_e('Veículo offline', 'vehicle-tracker'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="vt-card">
            <div class="vt-card-header">
                <h3><?php esc_html_e('Notificações', 'vehicle-tracker'); ?></h3>
            </div>
            <div class="vt-card-body">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Alertas por Email', 'vehicle-tracker'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="vt_email_alerts" value="1" 
                                           <?php checked(get_option('vt_email_alerts', '1'), '1'); ?>>
                                    <?php esc_html_e('Enviar alertas críticos por email', 'vehicle-tracker'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vt_alert_email">
                                    <?php esc_html_e('Email para Alertas', 'vehicle-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="email" 
                                       id="vt_alert_email" 
                                       name="vt_alert_email" 
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('vt_alert_email', get_option('admin_email'))); ?>">
                                <p class="description">
                                    <?php esc_html_e('Separe múltiplos emails com vírgula', 'vehicle-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Notificações no Painel', 'vehicle-tracker'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="vt_admin_notifications" value="1" 
                                           <?php checked(get_option('vt_admin_notifications', '1'), '1'); ?>>
                                    <?php esc_html_e('Mostrar notificações no painel do WordPress', 'vehicle-tracker'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Notificações do Navegador', 'vehicle-tracker'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="vt_browser_notifications" value="1" 
                                           <?php checked(get_option('vt_browser_notifications', '1'), '1'); ?>>
                                    <?php esc_html_e('Usar notificações push do navegador', 'vehicle-tracker'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'general';
        
        $options_config = array(
            'general' => array(
                'vt_company_name' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_timezone' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_refresh_interval' => array('sanitize' => 'absint', 'validate' => array('min' => 10, 'max' => 300)),
                'vt_offline_threshold' => array('sanitize' => 'absint', 'validate' => array('min' => 60, 'max' => 3600)),
                'vt_data_retention' => array('sanitize' => 'absint', 'validate' => array('min' => 30, 'max' => 365))
            ),
            'avatek' => array(
                'vt_avatek_enabled' => array('sanitize' => 'absint', 'validate' => null),
                'vt_avatek_api_url' => array('sanitize' => 'esc_url_raw', 'validate' => 'url'),
                'vt_avatek_api_key' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_avatek_api_secret' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_avatek_username' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_avatek_password' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_avatek_sync_interval' => array('sanitize' => 'absint', 'validate' => null)
            ),
            'tcp' => array(
                'vt_tcp_server_enabled' => array('sanitize' => 'absint', 'validate' => null),
                'vt_tcp_server_host' => array('sanitize' => 'sanitize_text_field', 'validate' => 'ip'),
                'vt_tcp_server_port' => array('sanitize' => 'absint', 'validate' => array('min' => 1024, 'max' => 65535)),
                'vt_webhook_api_key' => array('sanitize' => 'sanitize_text_field', 'validate' => null)
            ),
            'maps' => array(
                'vt_map_provider' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_google_maps_key' => array('sanitize' => 'sanitize_text_field', 'validate' => null),
                'vt_default_center_lat' => array('sanitize' => 'sanitize_text_field', 'validate' => 'latitude'),
                'vt_default_center_lng' => array('sanitize' => 'sanitize_text_field', 'validate' => 'longitude'),
                'vt_default_zoom' => array('sanitize' => 'absint', 'validate' => array('min' => 1, 'max' => 20)),
                'vt_map_cluster' => array('sanitize' => 'absint', 'validate' => null),
                'vt_map_traffic' => array('sanitize' => 'absint', 'validate' => null),
                'vt_map_fullscreen' => array('sanitize' => 'absint', 'validate' => null)
            ),
            'alerts' => array(
                'vt_speed_limit' => array('sanitize' => 'absint', 'validate' => array('min' => 20, 'max' => 200)),
                'vt_idle_timeout' => array('sanitize' => 'absint', 'validate' => array('min' => 60, 'max' => 3600)),
                'vt_alert_speed' => array('sanitize' => 'absint', 'validate' => null),
                'vt_alert_geofence' => array('sanitize' => 'absint', 'validate' => null),
                'vt_alert_ignition' => array('sanitize' => 'absint', 'validate' => null),
                'vt_alert_battery' => array('sanitize' => 'absint', 'validate' => null),
                'vt_alert_sos' => array('sanitize' => 'absint', 'validate' => null),
                'vt_alert_offline' => array('sanitize' => 'absint', 'validate' => null),
                'vt_email_alerts' => array('sanitize' => 'absint', 'validate' => null),
                'vt_alert_email' => array('sanitize' => 'sanitize_email', 'validate' => 'email'),
                'vt_admin_notifications' => array('sanitize' => 'absint', 'validate' => null),
                'vt_browser_notifications' => array('sanitize' => 'absint', 'validate' => null)
            )
        );
        
        $has_errors = false;
        
        if (isset($options_config[$tab])) {
            foreach ($options_config[$tab] as $option => $config) {
                $value = isset($_POST[$option]) ? $_POST[$option] : '';
                
                // Sanitiza
                $sanitized_value = call_user_func($config['sanitize'], $value);
                
                // Valida
                $validation_result = $this->validate_field($option, $sanitized_value, $config['validate']);
                
                if ($validation_result !== true) {
                    $this->add_error($validation_result);
                    $has_errors = true;
                    continue;
                }
                
                // Salva
                update_option($option, $sanitized_value);
            }
            
            // Trata checkboxes não marcados
            foreach ($options_config[$tab] as $option => $config) {
                if ($config['sanitize'] === 'absint' && !isset($_POST[$option])) {
                    update_option($option, 0);
                }
            }
        }
        
        if (!$has_errors) {
            $this->add_success(__('Configurações salvas com sucesso!', 'vehicle-tracker'));
        }
    }
    
    /**
     * Valida campos
     */
    private function validate_field($field_name, $value, $validation) {
        if ($validation === null) {
            return true;
        }
        
        // Validação de range numérico
        if (is_array($validation) && isset($validation['min']) && isset($validation['max'])) {
            if ($value < $validation['min'] || $value > $validation['max']) {
                return sprintf(
                    __('O valor de %s deve estar entre %d e %d.', 'vehicle-tracker'),
                    $field_name,
                    $validation['min'],
                    $validation['max']
                );
            }
            return true;
        }
        
        // Validação de IP
        if ($validation === 'ip') {
            if ($value !== '0.0.0.0' && !filter_var($value, FILTER_VALIDATE_IP)) {
                return __('Endereço IP inválido.', 'vehicle-tracker');
            }
            return true;
        }
        
        // Validação de URL
        if ($validation === 'url') {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                return __('URL inválida.', 'vehicle-tracker');
            }
            return true;
        }
        
        // Validação de email
        if ($validation === 'email') {
            if (!empty($value) && !is_email($value)) {
                return __('Email inválido.', 'vehicle-tracker');
            }
            return true;
        }
        
        // Validação de latitude
        if ($validation === 'latitude') {
            $lat = floatval($value);
            if ($lat < -90 || $lat > 90) {
                return __('Latitude deve estar entre -90 e 90.', 'vehicle-tracker');
            }
            return true;
        }
        
        // Validação de longitude
        if ($validation === 'longitude') {
            $lng = floatval($value);
            if ($lng < -180 || $lng > 180) {
                return __('Longitude deve estar entre -180 e 180.', 'vehicle-tracker');
            }
            return true;
        }
        
        return true;
    }
}
