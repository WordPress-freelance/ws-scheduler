<?php defined( 'ABSPATH' ) || exit; ?>
<div class="ws-adminbar">
  <div class="ws-adminbar-logo">
    <div class="ws-mark">W</div>
    WebStrategy
  </div>
  <div class="ws-adminbar-links">
    <a class="ws-alink <?php echo ( ! isset( $_GET['page'] ) || $_GET['page'] === 'ws-scheduler' ) ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler' ) ); ?>"><?php esc_html_e( 'Tableau de bord', 'ws-scheduler' ); ?></a>
    <a class="ws-alink <?php echo ( isset( $_GET['page'] ) && $_GET['page'] === 'ws-scheduler-unavail' ) ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler-unavail' ) ); ?>"><?php esc_html_e( 'Indisponibilités', 'ws-scheduler' ); ?></a>
    <a class="ws-alink <?php echo ( isset( $_GET['page'] ) && $_GET['page'] === 'ws-scheduler-settings' ) ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler-settings' ) ); ?>"><?php esc_html_e( 'Réglages', 'ws-scheduler' ); ?></a>
  </div>
  <span class="ws-adminbar-sep"><?php echo esc_html( home_url() ); ?></span>
</div>
