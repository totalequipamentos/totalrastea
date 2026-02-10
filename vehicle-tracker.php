<?php
/**
 * Plugin Name: Vehicle Tracker Pro
 * Plugin URI: https://locminas.com.br/vehicle-tracker
 * Description: Plataforma profissional de rastreamento veicular com integração Suntech ST8310UM e API Avatek
 * Version: 1.0.0
 * Author: Sua Empresa
 * Author URI: https://seusite.com.br
 * License: GPL v2 or later
 * Text Domain: vehicle-tracker
 * Domain Path: /languages
 */

// Impede acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('VT_VERSION', '1.0.0');
define('VT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Configurações da API Avatek (serão carregadas em tempo de execução)
define('VT_AVATEK_API_URL', 'https://api.avatek.com.br/v1');

/**
 * Classe principal do plugin
 */
class Vehicle_Tracker {
    
    private static $instance = null;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Obtém chave API Avatek
     */
    public static function get_avatek_api_key() {
        return get_option('vt_avatek_api_key', '');
    }
    
    /**
     * Obtém segredo API Avatek
     */
    public static function get_avatek_api_secret() {
        return get_option('vt_avatek_api_secret', '');
    }
    
    /**
     * Obtém host do servidor TCP
     */
    public static function get_tcp_server_host() {
        return get_option('vt_tcp_server_host', '0.0.0.0');
    }
    
    /**
     * Obtém porta do servidor TCP
     */
    public static function get_tcp_server_port() {
        return get_option('vt_tcp_server_port', '5000');
    }
    
    /**
     * Carrega dependências
     */
    private function load_dependencies() {
        // Classes principais
        require_once VT_PLUGIN_DIR . 'includes/class-vt-database.php';
        require_once VT_PLUGIN_DIR . 'includes/class-vt-avatek-api.php';
        require_once VT_PLUGIN_DIR . 'includes/class-vt-suntech-parser.php';
        require_once VT_PLUGIN_DIR . 'includes/class-vt-tcp-server.php';
        require_once VT_PLUGIN_DIR . 'includes/class-vt-vehicle.php';
        require_once VT_PLUGIN_DIR . 'includes/class-vt-geofence.php';
        require_once VT_PLUGIN_DIR . 'includes/class-vt-alert.php';
        
        // API REST
        require_once VT_PLUGIN_DIR . 'includes/class-vt-rest-api.php';
        
        // Admin - DEVE ser carregado ANTES das subclasses admin
        if (is_admin()) {
            // Carrega a classe base PRIMEIRO - crítico para as subclasses funcionarem
            $admin_base_file = VT_PLUGIN_DIR . 'admin/class-vt-admin.php';
            if (file_exists($admin_base_file)) {
                require_once $admin_base_file;
            } else {
                wp_die('Erro crítico: class-vt-admin.php não encontrado.');
            }
            
            // Verifica se a classe base foi carregada corretamente
            if (!class_exists('VT_Admin')) {
                wp_die('Erro crítico: Classe VT_Admin não pôde ser carregada.');
            }
            
            // Agora carrega as subclasses admin com segurança
            $admin_files = array(
                'admin/class-vt-admin-dashboard.php',
                'admin/class-vt-admin-vehicles.php',
                'admin/class-vt-admin-tracking.php',
                'admin/class-vt-admin-history.php',
                'admin/class-vt-admin-geofences.php',
                'admin/class-vt-admin-reports.php',
                'admin/class-vt-admin-settings.php'
            );
            
            foreach ($admin_files as $file) {
                $file_path = VT_PLUGIN_DIR . $file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                }
            }
        }
    }
    
    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Ativação e desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inicialização
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // AJAX handlers
        add_action('wp_ajax_vt_get_vehicles', array($this, 'ajax_get_vehicles'));
        add_action('wp_ajax_vt_get_vehicle_position', array($this, 'ajax_get_vehicle_position'));
        add_action('wp_ajax_vt_get_vehicle_history', array($this, 'ajax_get_vehicle_history'));
        add_action('wp_ajax_vt_send_command', array($this, 'ajax_send_command'));
        add_action('wp_ajax_vt_sync_avatek', array($this, 'ajax_sync_avatek'));
        
        // Cron jobs para sincronização
        add_action('vt_sync_positions', array($this, 'cron_sync_positions'));
        add_action('vt_process_alerts', array($this, 'cron_process_alerts'));
    }
    
    /**
     * Ativação do plugin
     */
    public function activate() {
        // Cria tabelas do banco de dados
        try {
            $database = new VT_Database();
            $database->create_tables();
        } catch (Exception $e) {
            error_log('Vehicle Tracker: Erro ao criar tabelas - ' . $e->getMessage());
        }
        
        // Agenda cron jobs
        if (!wp_next_scheduled('vt_sync_positions')) {
            wp_schedule_event(time(), 'every_minute', 'vt_sync_positions');
        }
        if (!wp_next_scheduled('vt_process_alerts')) {
            wp_schedule_event(time(), 'every_five_minutes', 'vt_process_alerts');
        }
        
        // Opções padrão (não sobrescreve valores existentes)
        foreach ($this->get_default_options() as $key => $value) {
            add_option($key, $value);
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Retorna opções padrão
     */
    private function get_default_options() {
        return array(
            'vt_avatek_api_key' => '',
            'vt_avatek_api_secret' => '',
            'vt_tcp_server_host' => '0.0.0.0',
            'vt_tcp_server_port' => '5000',
            'vt_google_maps_key' => '',
            'vt_speed_limit' => 80,
            'vt_idle_timeout' => 300,
            'vt_refresh_interval' => 30,
            'vt_company_name' => get_option('blogname', ''),
            'vt_timezone' => wp_timezone_string(),
            'vt_map_provider' => 'leaflet',
            'vt_default_zoom' => 15,
            'vt_avatek_enabled' => false,
            'vt_tcp_server_enabled' => false,
            'vt_map_cluster' => true,
            'vt_map_traffic' => false,
            'vt_alert_speed' => true,
            'vt_alert_geofence' => true,
            'vt_alert_ignition' => true,
            'vt_alert_battery' => true,
            'vt_alert_sos' => true,
            'vt_alert_offline' => true,
            'vt_email_alerts' => false,
            'vt_admin_notifications' => true,
            'vt_browser_notifications' => true,
        );
    }
    
    /**
     * Desativação do plugin
     */
    public function deactivate() {
        wp_clear_scheduled_hook('vt_sync_positions');
        wp_clear_scheduled_hook('vt_process_alerts');
        flush_rewrite_rules();
    }
    
    /**
     * Inicialização
     */
    public function init() {
        // Carrega textdomain para traduções
        load_plugin_textdomain(
            'vehicle-tracker',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        // Adiciona intervalos de cron personalizados
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        
        // Registra tipos de post customizados se necessário
        $this->register_custom_post_types();
    }
    
    /**
     * Adiciona intervalos de cron personalizados
     */
    public function add_cron_schedules($schedules) {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => __('A cada minuto', 'vehicle-tracker')
        );
        $schedules['every_five_minutes'] = array(
            'interval' => 300,
            'display' => __('A cada 5 minutos', 'vehicle-tracker')
        );
        return $schedules;
    }
    
    /**
     * Registra tipos de post customizados
     */
    private function register_custom_post_types() {
        // Será implementado conforme necessário
    }
    
    /**
     * Inicialização admin
     */
    public function admin_init() {
        // Registra configurações
        if (function_exists('register_setting')) {
            $this->register_settings();
        }
    }
    
    /**
     * Registra todas as configurações
     */
    private function register_settings() {
        // Geral
        register_setting('vt_settings', 'vt_company_name');
        register_setting('vt_settings', 'vt_timezone');
        register_setting('vt_settings', 'vt_refresh_interval', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('vt_settings', 'vt_offline_threshold', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('vt_settings', 'vt_data_retention', array('type' => 'integer', 'sanitize_callback' => 'intval'));

        // Avatek
        register_setting('vt_settings', 'vt_avatek_enabled', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_avatek_api_url');
        register_setting('vt_settings', 'vt_avatek_api_key');
        register_setting('vt_settings', 'vt_avatek_api_secret');
        register_setting('vt_settings', 'vt_avatek_username');
        register_setting('vt_settings', 'vt_avatek_password');
        register_setting('vt_settings', 'vt_avatek_sync_interval', array('type' => 'integer', 'sanitize_callback' => 'intval'));

        // TCP Server
        register_setting('vt_settings', 'vt_tcp_server_enabled', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_tcp_server_host');
        register_setting('vt_settings', 'vt_tcp_server_port', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('vt_settings', 'vt_webhook_api_key');

        // Maps
        register_setting('vt_settings', 'vt_map_provider');
        register_setting('vt_settings', 'vt_google_maps_key');
        register_setting('vt_settings', 'vt_default_center_lat');
        register_setting('vt_settings', 'vt_default_center_lng');
        register_setting('vt_settings', 'vt_default_zoom', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('vt_settings', 'vt_map_cluster', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_map_traffic', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_map_fullscreen', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));

        // Alerts
        register_setting('vt_settings', 'vt_speed_limit', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('vt_settings', 'vt_idle_timeout', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('vt_settings', 'vt_alert_speed', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_alert_geofence', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_alert_ignition', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_alert_battery', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_alert_sos', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_alert_offline', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_email_alerts', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_alert_email');
        register_setting('vt_settings', 'vt_admin_notifications', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
        register_setting('vt_settings', 'vt_browser_notifications', array('type' => 'boolean', 'sanitize_callback' => array($this, 'sanitize_bool')));
    }
    
    /**
     * Sanitiza valores booleanos
     */
    public function sanitize_bool($value) {
        return (bool) $value;
    }
    
    /**
     * Menu administrativo
     */
    public function admin_menu() {
        // Menu principal
        add_menu_page(
            __('Vehicle Tracker', 'vehicle-tracker'),
            __('Rastreamento', 'vehicle-tracker'),
            'manage_options',
            'vehicle-tracker',
            array($this, 'render_dashboard_page'),
            'dashicons-location-alt',
            30
        );
        
        // Submenus
        add_submenu_page(
            'vehicle-tracker',
            __('Dashboard', 'vehicle-tracker'),
            __('Dashboard', 'vehicle-tracker'),
            'manage_options',
            'vehicle-tracker',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'vehicle-tracker',
            __('Rastreamento', 'vehicle-tracker'),
            __('Tempo Real', 'vehicle-tracker'),
            'manage_options',
            'vt-tracking',
            array($this, 'render_tracking_page')
        );
        
        add_submenu_page(
            'vehicle-tracker',
            __('Veículos', 'vehicle-tracker'),
            __('Veículos', 'vehicle-tracker'),
            'manage_options',
            'vt-vehicles',
            array($this, 'render_vehicles_page')
        );
        
        add_submenu_page(
            'vehicle-tracker',
            __('Histórico', 'vehicle-tracker'),
            __('Histórico', 'vehicle-tracker'),
            'manage_options',
            'vt-history',
            array($this, 'render_history_page')
        );
        
        add_submenu_page(
            'vehicle-tracker',
            __('Geocercas', 'vehicle-tracker'),
            __('Geocercas', 'vehicle-tracker'),
            'manage_options',
            'vt-geofences',
            array($this, 'render_geofences_page')
        );
        
        add_submenu_page(
            'vehicle-tracker',
            __('Relatórios', 'vehicle-tracker'),
            __('Relatórios', 'vehicle-tracker'),
            'manage_options',
            'vt-reports',
            array($this, 'render_reports_page')
        );
        
        // NOTE: vt-settings submenu is registered conditionally after class definition
        // to ensure VT_Admin_Settings class exists and dependencies are loaded
    }
    
    /**
     * Enfileira scripts e estilos admin
     */
    public function admin_enqueue_scripts($hook) {
        // Somente nas páginas do plugin
        if (strpos($hook, 'vehicle-tracker') === false && strpos($hook, 'vt-') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style('vt-admin-style', plugins_url('assets/css/admin-style.css', __FILE__), array(), VT_VERSION);
        wp_enqueue_style('vt-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_style('vt-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        wp_enqueue_style('vt-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        
        // JS
        wp_enqueue_script('vt-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
        wp_enqueue_script('vt-chart', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true);
        wp_enqueue_script('vt-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_script('vt-admin-script', plugins_url('assets/js/admin-script.js', __FILE__), array('jquery', 'vt-leaflet', 'vt-chart', 'wp-i18n'), VT_VERSION, true);
        
        wp_set_script_translations('vt-admin-script', 'vehicle-tracker');
        
        // Localização JS
        wp_localize_script('vt-admin-script', 'vt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vt_nonce'),
            'api_url' => rest_url('vehicle-tracker/v1/'),
            'google_maps_key' => get_option('vt_google_maps_key', ''),
            'refresh_interval' => intval(get_option('vt_refresh_interval', 30)) * 1000,
            'strings' => array(
                'loading' => __('Carregando...', 'vehicle-tracker'),
                'error' => __('Erro ao carregar dados', 'vehicle-tracker'),
                'confirm_delete' => __('Tem certeza que deseja excluir?', 'vehicle-tracker'),
                'online' => __('Online', 'vehicle-tracker'),
                'offline' => __('Offline', 'vehicle-tracker'),
                'moving' => __('Em movimento', 'vehicle-tracker'),
                'stopped' => __('Parado', 'vehicle-tracker'),
                'success' => __('Sucesso!', 'vehicle-tracker'),
                'warning' => __('Aviso', 'vehicle-tracker'),
            )
        ));
    }
    
    /**
     * Enfileira scripts e estilos frontend
     */
    public function frontend_enqueue_scripts() {
        global $post;
        
        // Verifica se está na página de rastreamento
        $show_tracker = false;
        
        if (isset($post->post_content) && has_shortcode($post->post_content, 'vehicle_tracker')) {
            $show_tracker = true;
        }
        
        if (is_page('rastreamento')) {
            $show_tracker = true;
        }
        
        if (!$show_tracker) {
            return;
        }
        
        wp_enqueue_style('vt-frontend-style', plugins_url('assets/css/public-style.css', __FILE__), array(), VT_VERSION);
        wp_enqueue_style('vt-leaflet-public', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_style('vt-fontawesome-public', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        
        wp_enqueue_script('vt-leaflet-public', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
        wp_enqueue_script('vt-frontend-script', plugins_url('assets/js/public-script.js', __FILE__), array('jquery', 'vt-leaflet-public'), VT_VERSION, true);
        
        // Localização JS
        wp_localize_script('vt-frontend-script', 'vt_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vt_nonce'),
            'api_url' => rest_url('vehicle-tracker/v1/'),
            'refresh_interval' => intval(get_option('vt_refresh_interval', 30)) * 1000,
        ));
    }
    
    /**
     * Registra rotas REST API
     */
    public function register_rest_routes() {
        $rest_api = new VT_REST_API();
        $rest_api->register_routes();
    }
    
    /**
     * Renderiza página Dashboard
     */
    public function render_dashboard_page() {
        $dashboard = new VT_Admin_Dashboard();
        $dashboard->render();
    }
    
    /**
     * Renderiza página Rastreamento
     */
    public function render_tracking_page() {
        $tracking = new VT_Admin_Tracking();
        $tracking->render();
    }
    
    /**
     * Renderiza página Veículos
     */
    public function render_vehicles_page() {
        $vehicles = new VT_Admin_Vehicles();
        $vehicles->render();
    }
    
    /**
     * Renderiza página Histórico
     */
    public function render_history_page() {
        $history = new VT_Admin_History();
        $history->render();
    }
    
    /**
     * Renderiza página Geocercas
     */
    public function render_geofences_page() {
        $geofences = new VT_Admin_Geofences();
        $geofences->render();
    }
    
    /**
     * Renderiza página Relatórios
     */
    public function render_reports_page() {
        $reports = new VT_Admin_Reports();
        $reports->render();
    }
    
    /**
     * Renderiza página Configurações
     */
    public function render_settings_page() {
        $settings = new VT_Admin_Settings();
        $settings->render();
    }
    
    /**
     * AJAX: Obtém lista de veículos
     */
    public function ajax_get_vehicles() {
        check_ajax_referer('vt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'vehicle-tracker'), 403);
        }
        
        try {
            $vehicle = new VT_Vehicle();
            $vehicles = $vehicle->get_all();
            wp_send_json_success($vehicles);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }
    
    /**
     * AJAX: Obtém posição de um veículo
     */
    public function ajax_get_vehicle_position() {
        check_ajax_referer('vt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'vehicle-tracker'), 403);
        }
        
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        
        if (!$vehicle_id) {
            wp_send_json_error(__('ID do veículo não fornecido', 'vehicle-tracker'), 400);
        }
        
        try {
            $vehicle = new VT_Vehicle();
            $position = $vehicle->get_last_position($vehicle_id);
            
            if (!$position) {
                wp_send_json_error(__('Nenhuma posição encontrada', 'vehicle-tracker'), 404);
            }
            
            wp_send_json_success($position);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }
    
    /**
     * AJAX: Obtém histórico de um veículo
     */
    public function ajax_get_vehicle_history() {
        check_ajax_referer('vt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'vehicle-tracker'), 403);
        }
        
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        
        if (!$vehicle_id || !$start_date || !$end_date) {
            wp_send_json_error(__('Parâmetros inválidos', 'vehicle-tracker'), 400);
        }
        
        try {
            $vehicle = new VT_Vehicle();
            $history = $vehicle->get_history($vehicle_id, $start_date, $end_date);
            wp_send_json_success($history);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }
    
    /**
     * AJAX: Envia comando para o rastreador
     */
    public function ajax_send_command() {
        check_ajax_referer('vt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'vehicle-tracker'), 403);
        }
        
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $command = sanitize_text_field($_POST['command'] ?? '');
        
        if (!$vehicle_id || !$command) {
            wp_send_json_error(__('Parâmetros inválidos', 'vehicle-tracker'), 400);
        }
        
        try {
            // Tenta enviar via API Avatek primeiro
            if (get_option('vt_avatek_enabled')) {
                $avatek = new VT_Avatek_API();
                $result = $avatek->send_command($vehicle_id, $command);
                
                if ($result) {
                    wp_send_json_success(__('Comando enviado com sucesso', 'vehicle-tracker'));
                }
            }
            
            // Fallback para servidor TCP próprio
            if (get_option('vt_tcp_server_enabled')) {
                $tcp = new VT_TCP_Server();
                $result = $tcp->send_command($vehicle_id, $command);
                
                if ($result) {
                    wp_send_json_success(__('Comando enviado com sucesso', 'vehicle-tracker'));
                }
            }
            
            wp_send_json_error(__('Nenhum servidor de comando disponível', 'vehicle-tracker'), 503);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }
    
    /**
     * AJAX: Sincroniza dados com API Avatek
     */
    public function ajax_sync_avatek() {
        check_ajax_referer('vt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Acesso negado', 'vehicle-tracker'), 403);
        }
        
        try {
            if (!get_option('vt_avatek_enabled')) {
                wp_send_json_error(__('API Avatek não está habilitada', 'vehicle-tracker'), 403);
            }
            
            $avatek = new VT_Avatek_API();
            $result = $avatek->sync_all_positions();
            
            if ($result) {
                wp_send_json_success(__('Sincronização concluída', 'vehicle-tracker'));
            } else {
                wp_send_json_error(__('Erro na sincronização', 'vehicle-tracker'), 500);
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Cron: Sincroniza posições
     */
    public function cron_sync_positions() {
        $avatek = new VT_Avatek_API();
        $avatek->sync_all_positions();
    }
    
    /**
     * Cron: Processa alertas
     */
    public function cron_process_alerts() {
        $alert = new VT_Alert();
        $alert->process_pending_alerts();
    }
}

// Inicializa o plugin
function vehicle_tracker() {
    return Vehicle_Tracker::get_instance();
}

// Inicia
vehicle_tracker();

/**
 * Registro seguro do submenu de Configurações
 * 
 * Evita duplicação de menu e garante que:
 * - A classe VT_Admin_Settings esteja disponível
 * - Suas dependências (VT_Admin) estejam carregadas
 * - O menu principal já tenha sido registrado
 */
if (is_admin()) {
    // Usa prioridade 20 para garantir que o menu principal já tenha sido registrado
    add_action('admin_menu', function () {
        // Verifica se a classe existe (deve existir após load_dependencies)
        if (class_exists('VT_Admin_Settings')) {
            $vt_admin_settings = new VT_Admin_Settings();
            
            add_submenu_page(
                'vehicle-tracker',                        // parent slug
                __('Configurações', 'vehicle-tracker'),  // page title
                __('Configurações', 'vehicle-tracker'),  // menu title
                'manage_options',                         // capability
                'vt-settings',                            // menu slug
                array($vt_admin_settings, 'render')       // callback
            );
        }
    }, 20);
}
