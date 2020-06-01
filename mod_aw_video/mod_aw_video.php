<?php
 
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
 
// Include the syndicate functions only once
require_once( dirname(__FILE__).DS.'helper.php' );

if ($params->def('prepare_content', 1))
{
	JPluginHelper::importPlugin('content');
	$module->content = JHtml::_('content.prepare', $module->content, '', 'mod_aw_video.content');
}
 
//$ggpopup = modGgPopUpHelper::getPopUp( $params );
require JModuleHelper::getLayoutPath('mod_aw_video', $params->get('layout', 'default'));

?>