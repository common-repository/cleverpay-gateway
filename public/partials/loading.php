<div class="clever-loading-m">
  <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto;  display: block;" width="84px" height="84px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
  <circle cx="50" cy="50" r="32" stroke-width="8" stroke="#000000" stroke-dasharray="50.26548245743669 50.26548245743669" fill="none" stroke-linecap="round">
    <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" keyTimes="0;1" values="0 50 50;360 50 50"></animateTransform>
  </circle>
  </svg>
  <div class="cl-popup-on">
    <img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/icon-1.svg" width="100px">
    <div class="cl-popup-t1">Hello Clever</div>
    <div class="cl-popup-show">Show popup again</div>
  </div>
    <!-- <img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/i8-close.svg" class="cl-close-popup"> -->
</div>

<?php $auth = Clever_Api::auth();?>
<input type="hidden" id="clever-configs" value='<?php echo json_encode([
  'ajax_url' => admin_url('admin-ajax.php'), 
  'plugin_url' => CLEVER_GATEWAY_URL, 
  'app_id' => isset($auth['app_id']) ? $auth['app_id'] : '',
  'url' => $auth['url'],
  'plugin_version' => CLEVER_GATEWAY_VERSION
  ]) ?>'>