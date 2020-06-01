<?php
/**
 * @package     arti.Site
 * @subpackage  mod_instagram
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<div class="instagramImageWrapper">

  <?php
  // use this instagram access token generator http://instagram.pixelunion.net/
    $access_token="{$params->get('accessToken')}";
    $photo_count="{$params->get('photoNumber')}";
        
    $json_link="https://api.instagram.com/v1/users/self/media/recent/?";
    $json_link.="access_token={$access_token}&count={$photo_count}";

    $json = file_get_contents($json_link);
    $obj = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
  ?>
  <?php foreach ($obj['data'] as $post) :?>
  
    <?php 
      $pic_text=$post['caption']['text'];
      $pic_link=$post['link'];
      $pic_like_count=$post['likes']['count'];
      $pic_comment_count=$post['comments']['count'];
      $pic_src=str_replace("http://", "https://", $post['images']['standard_resolution']['url']);
      $pic_created_time=date("F j, Y", $post['created_time']);
      $pic_created_time=date("F j, Y", strtotime($pic_created_time));
     ?> 

    <div class='instagramImageBody'>

      <a href='<?php echo $pic_link; ?>' target='_blank'>
          <img class='img-responsive photo-thumb' src='<?php echo $pic_src; ?>' alt='<?php echo $pic_text; ?>'>
       </a>
  
       <div style='color:#888;'>
         <a href='<?php echo $pic_link; ?>' target='_blank'><?php echo $pic_created_time; ?></a>
       </div>
        
       <div class="intaTextPic">
		     <?php echo $pic_text; ?>
       </div>
        
    </div>
  <?php endforeach; ?>
  </div>
 

