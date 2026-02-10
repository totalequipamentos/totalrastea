<?php
/**
 * Classe base para administração
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin {
    
    /**
     * Renderiza o wrapper do admin
     */
    protected function render_page_header($title, $subtitle = '', $actions = array()) {
        ?>
        <div class="vt-admin-header">
            <div class="vt-header-content">
                <h1 class="vt-page-title"><?php echo esc_html($title); ?></h1>
                <?php if ($subtitle): ?>
                    <p class="vt-page-subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($actions)): ?>
                <div class="vt-header-actions">
                    <?php foreach ($actions as $action): ?>
                        <a href="<?php echo esc_url($action['url']); ?>" class="button <?php echo esc_attr($action['class'] ?? 'button-secondary'); ?>">
                            <?php if (isset($action['icon'])): ?>
                                <span class="dashicons <?php echo esc_attr($action['icon']); ?>"></span>
                            <?php endif; ?>
                            <?php echo esc_html($action['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza card de estatística
     */
    protected function render_stat_card($title, $value, $icon, $color = 'primary', $change = null) {
        ?>
        <div class="vt-stat-card vt-stat-<?php echo esc_attr($color); ?>">
            <div class="vt-stat-icon">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
            </div>
            <div class="vt-stat-content">
                <span class="vt-stat-value"><?php echo esc_html($value); ?></span>
                <span class="vt-stat-label"><?php echo esc_html($title); ?></span>
                <?php if ($change !== null): ?>
                    <span class="vt-stat-change <?php echo $change >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $change >= 0 ? '+' : ''; ?><?php echo esc_html($change); ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza notificação
     */
    protected function render_notice($message, $type = 'info') {
        ?>
        <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
    
    /**
     * Renderiza tabela vazia
     */
    protected function render_empty_state($message, $action = null) {
        ?>
        <div class="vt-empty-state">
            <span class="dashicons dashicons-warning"></span>
            <p><?php echo esc_html($message); ?></p>
            <?php if ($action): ?>
                <a href="<?php echo esc_url($action['url']); ?>" class="button button-primary">
                    <?php echo esc_html($action['label']); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza loading
     */
    protected function render_loading() {
        ?>
        <div class="vt-loading">
            <span class="spinner is-active"></span>
            <p><?php _e('Carregando...', 'vehicle-tracker'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Formata data para exibição
     */
    protected function format_datetime($datetime) {
        if (!$datetime) {
            return '-';
        }
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($datetime));
    }
    
    /**
     * Retorna badge de status
     */
    protected function get_status_badge($status, $labels = array()) {
        $default_labels = array(
            'active' => __('Ativo', 'vehicle-tracker'),
            'inactive' => __('Inativo', 'vehicle-tracker'),
            'blocked' => __('Bloqueado', 'vehicle-tracker'),
            'maintenance' => __('Manutenção', 'vehicle-tracker'),
            'online' => __('Online', 'vehicle-tracker'),
            'offline' => __('Offline', 'vehicle-tracker'),
            'moving' => __('Em movimento', 'vehicle-tracker'),
            'stopped' => __('Parado', 'vehicle-tracker'),
            'pending' => __('Pendente', 'vehicle-tracker'),
            'read' => __('Lido', 'vehicle-tracker'),
            'resolved' => __('Resolvido', 'vehicle-tracker'),
            'dismissed' => __('Descartado', 'vehicle-tracker')
        );
        
        $labels = array_merge($default_labels, $labels);
        $label = $labels[$status] ?? $status;
        
        return sprintf('<span class="vt-badge vt-badge-%s">%s</span>', esc_attr($status), esc_html($label));
    }
    
    /**
     * Retorna badge de severidade
     */
    protected function get_severity_badge($severity) {
        $labels = array(
            'low' => __('Baixa', 'vehicle-tracker'),
            'medium' => __('Média', 'vehicle-tracker'),
            'high' => __('Alta', 'vehicle-tracker'),
            'critical' => __('Crítica', 'vehicle-tracker')
        );
        
        $label = $labels[$severity] ?? $severity;
        
        return sprintf('<span class="vt-badge vt-severity-%s">%s</span>', esc_attr($severity), esc_html($label));
    }
}
