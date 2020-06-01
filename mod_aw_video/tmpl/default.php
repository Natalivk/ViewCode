<?php 

// No direct access

defined('_JEXEC') or die;

$db = JFactory::getDbo();

?>

<div class="awVideo">
	<div class="videoContainer" id="videoContainer_<?php echo $module->id; ?>">
		<video class="awVideo" id="awVideo_<?php echo $module->id; ?>" muted poster="<?php echo $params->get( 'image' ); ?>">
			<source type="video/mp4" src="images/video/<?php echo $params->get( 'mp4' ); ?>"></source>
			<source type="video/ogg" src="images/video/<?php echo $params->get( 'ogg' ); ?>"></source>
			<source type="video/webm" src="images/video/<?php echo $params->get( 'webm' ); ?>"></source>
		</video>
		<div class="videoOverlay" style=" <?php echo ($params->get( 'overlayColor' ) !='') ? 'background-color:'.$params->get( 'overlayColor' ).';' : ''; ?>"></div>
		<div class="textContainer">
			<?php if ($params->get( 'text' ) != ''): ?>
				<div class="awVideoText"><?php echo $params->get( 'text' ); ?></div>
			<?php endif; ?>
			<div class="awVideoButton" onClick="playvideo_<?php echo $module->id; ?>()"></div>
		</div>
	</div>
</div>

<script>

document.getElementById('awVideo_<?php echo $module->id; ?>').addEventListener('ended', handler_<?php echo $module->id; ?>,false);

function handler_<?php echo $module->id; ?>(e) {
	document.getElementById('awVideo_<?php echo $module->id; ?>').load();
	document.getElementById('videoContainer_<?php echo $module->id; ?>').classList.remove('played');
}

function playvideo_<?php echo $module->id; ?>() {
	var video_el = document.getElementById('awVideo_<?php echo $module->id; ?>');
	video_el.play();
	document.getElementById('videoContainer_<?php echo $module->id; ?>').classList.add('played');
}
</script>