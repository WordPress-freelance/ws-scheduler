<?php defined( 'ABSPATH' ) || exit;
$current_page = isset( $_GET['page'] ) ? $_GET['page'] : 'ws-scheduler';
?>
<aside class="ws-sidemenu">
  <div class="ws-plugin-id">
    <div class="ws-plugin-logo">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="32" height="32" rx="7" fill="#221D32"/>
        <path d="M7 10l4 12 5-8 5 8 4-12" stroke="#7C5CBF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="24" cy="10" r="2" fill="#9B8EC4"/>
      </svg>
    </div>
    <div class="ws-plugin-name">WS Scheduler</div>
    <div class="ws-plugin-version">v<?php echo esc_html( WS_SCHEDULER_VERSION ); ?></div>
  </div>
  <nav class="ws-menu-items">
    <a class="ws-menu-item <?php echo $current_page === 'ws-scheduler' ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler' ) ); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Tableau de bord
    </a>
    <a class="ws-menu-item <?php echo $current_page === 'ws-scheduler-unavail' ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler-unavail' ) ); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      Indisponibilités
    </a>
    <a class="ws-menu-item <?php echo $current_page === 'ws-scheduler-settings' ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler-settings' ) ); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
      Réglages
    </a>
    <a class="ws-menu-item <?php echo $current_page === 'ws-scheduler-licence' ? 'active' : ''; ?>"
       href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler-licence' ) ); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Licence Pro
    </a>
  </nav>
</aside>
