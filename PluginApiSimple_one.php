<?php
class PluginApiSimple_one{
  function __construct(){
    wfPlugin::includeonce('wf/yml');
  }
  public function __call($method, $args) {
    /**
     * header
     */
    if(wfRequest::get('xurl')){
      header('Access-Control-Allow-Origin: '.wfRequest::get('xurl'));
    }else{
      header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Credentials: true');
    /**
     * 
     */
    $method = wfPhpfunc::substr($method, 5);
    $class = wfGlobals::get('class');
    $settings = new PluginWfArray(wfGlobals::get("settings/plugin_modules/$class/settings"));
    $settings->set('security/active', null);
    $key = wfRequest::get('key');
    $remote_addr = wfServer::getRemoteAddr();
    $json = new PluginWfArray(array('theme' => $this->get_theme(), 'request' => array('xurl' => wfRequest::get('xurl'), 'url' => wfServer::calcUrl(), 'class' => $class, 'method' => $method, 'key' => $key, 'remote_addr' => $remote_addr), 'user' => array('role' => array(), 'language' => null), 'error' => array(), 'data' => array()));
    $this_class = __CLASS__;
    /**
     * Security key.
     */
    $security_keys_data = array();
    if($settings->get('security/keys')){
      $key_match = false;
      foreach($settings->get('security/keys') as $value){
        $v = new PluginWfArray($value);
        if($v->get('value')==$key){
          $key_match = true;
          $security_keys_data = $v->get('data');
          $settings->set('security/active', $value);
          break;
        }
      }
      if(!$key_match){
        $json->set('error/message/', "$this_class says: Param security/keys does not match key.");
      }
    }
    if(!$settings->is_set("methods/$method")){
      $json->set('error/message/', "$this_class says: Method $method is not set in theme settings file!");
    }
    $settings->set("methods/$method/security_keys_data", $security_keys_data);
    /**
     * Security remote_addr.
     */
    if($settings->get('security/remote_addr')){
      $remote_addr_match = false;
      foreach($settings->get('security/remote_addr') as $value){
        $v = new PluginWfArray($value);
        if($v->get('value')==$remote_addr){
          $remote_addr_match = true;
          break;
        }
      }
      if(!$remote_addr_match){
        $json->set('error/message/', "$this_class says: Param security/remote_addr does not match remote_addr.");
      }
    }
    /**
     * Method.
     */
    if(!sizeof($json->get('error'))){
      if($settings->get("methods/$method")){
        $plugin = $settings->get("methods/$method/plugin");
        wfPlugin::includeonce($plugin);
        $obj = wfSettings::getPluginObj($plugin);
        $obj_method = $settings->get("methods/$method/name");
        if(!method_exists($obj, $obj_method) && !method_exists($obj, '__call')){
          $json->set('error/message/', "$this_class says: Method $obj_method does not exist in plugin $plugin.");
        }else{
          $method_data = new PluginWfArray($obj->$obj_method($settings->get("methods/$method"), $settings));
          /**
           * data or rs
           */
          if($method_data->is_set('data')){
            $json->set('data', $method_data->get('data'));
          }elseif($method_data->is_set('rs')){
            $json->set('data', $method_data->get('rs'));
          }
          /**
           * error
           */
          if($method_data->get('error')){
            foreach($method_data->get('error') as $v){
              $json->set('error/', $v);
            }
          }
        }
      }else{
        $json->set('error/message/', "$this_class says: Param methods does not has method $method.");
      }
    }
    /**
     * Slack
     */
    if($settings->get('slack')){
      $run = true;
      /**
       * 
       */
      if($settings->get('slack/domain_filter') && !wfPhpfunc::strstr(wfServer::getServerName(), $settings->get('slack/domain_filter'))){
        $run = false;
      }
      /**
       * 
       */
      if($run){
        /**
         * Set data in text.
         */
        $settings->set('slack/text/remote_addr', wfServer::getRemoteAddr().'');
        $settings->set('slack/text/request_uri', wfServer::getServerName().wfServer::getRequestUri() );
        $settings->set('slack/text/error', $json->get('error'));
        if(false){
          $settings->set('slack/text/json', $json->get());
          $settings->set('slack/text/session', wfUser::getSession()->get());
        }
        /**
         * Send.
         */
        wfPlugin::includeonce('slack/webhook_v1');
        $slack_webhook = new PluginSlackWebhook_v1();
        $slack_webhook->url = $settings->get('slack/webhook');
        $slack_webhook->channel = $settings->get('slack/group');
        $slack_webhook->text = wfHelp::getYmlDump($settings->get('slack/text'), true);
        $slack_webhook->send();
      }
    }
    /**
     * 
     */
    $json->set('user/role/client', wfUser::hasRole('client'));
    /**
     * 
     */
    $json->set('user/language', wfI18n::getLanguage());
    /**
     * 
     */
    if(wfRequest::get('output')=='yml'){
      echo '<pre>';
      echo wfHelp::getYmlDump($json->get());
      echo '</pre>';
      exit;
    }else{
      exit(json_encode($json->get()));
    }
  }
  private function get_theme(){
    $theme_manifest = new PluginWfYml('/theme/[theme]/config/manifest.yml');
    return array('version' => $theme_manifest->get('version'));
  }
}
