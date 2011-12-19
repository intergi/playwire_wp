<?php ob_start();
/***************************************************************************
Plugin Name:  Playwire for Wordpress
Plugin URI:   http://www.playwire.com/features/wordpress_plugin
Description:  This plugin allows you to upload and manage your Playwire videos
Version:      1.0
Author:       Playwire
Author URI:   http://www.playwire.com/
Author Name:   Playwire
**************************************************************************/
add_action('admin_menu', 'venues_admin_actions');

function my_css() {
echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') .'/wp-content/plugins/playwire_wp/css/style.css">' . "\n";
}

add_action('admin_head', 'my_css');

 function venues_admin_actions() {
    	// Add a new top-level menu for plugin:
	add_menu_page('Playwire', 'Playwire','','mt-top-level-handle', 'mt_toplevel_page');	
        // Add a submenu to the custom top-level menu:
   	add_submenu_page('mt-top-level-handle', '', 'List Video', 'publish_posts', 'mt_sublevel_handle', 'mt_sublevel_page');
	add_submenu_page('mt-top-level-handle', '','Add Video',   'publish_posts', 'mt-toplevel-handle', 'mt_toplevel_page');
	add_submenu_page('mt-top-level-handle', '','',   'publish_posts', 'mt-view-level-handle', 'mt_viewlevel_page');
	add_submenu_page('mt-top-level-handle', '','',   'publish_posts', 'mt-delete-level-handle', 'mt_deletelevel_page');
 }

//Function to delete the video start here
function mt_deletelevel_page() {
	require_once('delete.php');
}
//Function to delete the video end here

//Function to view the video start here
function mt_viewlevel_page() {
	if($_GET["sandbox"] == 1) {
		require_once('video_profile.php');
	}
	else {
		require_once('video.php');
	}
}
//Function to view the video end here


//Function to add the videos start here   
// mt_toplevel_page() displays the page content for the custom Test Toplevel menu

function mt_toplevel_page() {
	require_once('include/common.php');
	if (empty($api_token)) {
		showAPITokenForm();
	} else { 
		require_once('include/class.playwire.php');

		$playwire = new Playwire($api_token);

		$is_post = $_POST['is_post'];
		if ($is_post) { 
			echo "<pre>"; 
			// This is a POST, call the API
			$video = new Video();
			$video->name = $_POST['name'];
			//$video->source_url = $_POST['source_url'];
			
			//code to upload video on current server
			//echo "<pre>"; print_r($_SERVER); die;
			
			$serverPath = str_replace("wp-admin/admin.php","",$_SERVER['SCRIPT_FILENAME']);
			$target_path = $serverPath."wp-content/plugins/video-plugin/video_upload/";
			
			//code to delete all files of video_upload folder
			$handle=opendir($target_path);

			while (($file = readdir($handle))!==false) {
				@unlink($target_path.$file);
			}

			closedir($handle);
			//end code to delete all files of video_upload folder
			
			$target_path = $target_path . basename( $_FILES['source_url']['name']); 

			if(move_uploaded_file($_FILES['source_url']['tmp_name'], $target_path)) {
			    	
			} else{
			    	echo "There was an error uploading the file, please try again!";
				exit;
			}
			$pickServerPathBefore = $_SERVER["HTTP_REFERER"];
			$pickServerPath = str_replace("wp-admin/admin.php?page=mt-toplevel-handle","",$pickServerPathBefore);
			
			$video->source_url = $pickServerPath."wp-content/plugins/video-plugin/video_upload/" . basename( $_FILES['source_url']['name']);
			
			//echo "<pre>"; print_r($_POST); print_r($_FILES); die("helloooo");
			//code to upload video on current server end.....			

			$video->description = $_POST['description'];
			$video->category_id = $_POST['category_id'];
			$video->width = $_POST['width'];
			$video->height = $_POST['height'];
			$video->tag_list = $_POST['tag_list'];
			$video->show_video_watermark = checkboxValue($_POST['show_video_watermark']);
			$video->use_age_gate = checkboxValue($_POST['use_age_gate']);
			$video->auto_start = checkboxValue($_POST['auto_start']);
			try {
				$new_video = $playwire->uploadVideo($video);
				echo "<div style='padding-top:10px; text-align:center; font-weight:bold; font-size:14px;'>Successfully uploaded video, new id is " . $new_video->id."</div>";
			} catch (PlaywireException $exception) {
				echo "Had an exception: " . $exception->getMessage();
			}
			echo "</pre>";
		}
		//else {
			echo "<pre>";
			$defaults = $playwire->getVideoDefaults();
			$categories = $playwire->getVideoCategories();
			echo "</pre>";
			?>
			<h2 style="padding-left:12px;">Upload New Video</h2>
			<table width="90%" cellpading="1" cellspacing="1" border="1" style="padding-left:12px;">
			<form method="post" action="" enctype="multipart/form-data">
			<input type="hidden" name="is_post" value="1"/>

			<tr>
				<td width="20%"><label for="name">Name:</label></td>
				<td><input type="text" name="name" size="80"/></td>
			</tr>
			<tr>
				<td width="20%"><label for="source_url">Video Source URL:</label></td>
				<td><!--<input type="text" name="source_url" size="80"/>&nbsp;&nbsp;<br />--><input type="file" name="source_url" size="80"/></td>
			</tr>
			<tr>
				<td width="20%" valign="top"><label for="description">Description:</label></td>
				<td><textarea name="description" rows="6" cols="78"></textarea></td>
			</tr>
			<tr>
				<td width="20%"><label for="category_id">Category:</label></td>
				<td><select name="category_id">
					<option value="">Select Category</option>
					<?php
						foreach($categories as $category) {
							echo '<option value="' . $category->id . '">' . $category->name . '</option>'; 
						}
					?>
				</select></td>
			</tr>
			<tr>
				<td width="20%"><label for="width">Width:</label></td>
				<td><input type="text" name="width" size="5" value="<?php echo $defaults->width; ?>"/></td>
			</tr>
			<tr>
				<td width="20%"><label for="height">Height:</label></td>
				<td><input type="text" name="height" size="5" value="<?php echo $defaults->height; ?>"/></td>
			</tr>
			
			<tr>
				<td width="20%"><label for="tags">Tags:</label></td>
				<td><input type="text" name="tag_list" size="80" /></td>
			</tr>
			<tr>
				<td>&nbsp;</td><td><h4>Video Settings</h4></td>
			</tr>
			<tr>
				<td>&nbsp;</td><td><div>
				<input type="checkbox" name="show_video_watermark" value="on" <?php if ($defaults->show_video_watermark) echo 'checked="checked"'; ?>/>
					<label for="show_video_watermark">Show Video Watermark</label>
				</div>
				<div>
					<input type="checkbox" name="use_age_gate" value="on" <?php if ($defaults->use_age_gate) echo 'checked="checked"'; ?>/>
					<label for="use_age_gate">Use Age Gate</label>
				</div>
				<div>
					<input type="checkbox" name="auto_start" value="on" <?php if ($defaults->auto_start) echo 'checked="checked"'; ?>/>
					<label for="auto_start">Auto Play</label>
				</div></td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td>&nbsp;</td><td><input type="submit" name="Submit" value="Upload"/></td>
			</tr>
			</table>
<?php
		//}
	}
			

}
//Function to add the videos end here

//Function to listing the videos in admin section start here
function mt_sublevel_page() {
require_once('include/common.php'); 
	$is_sandbox = false;
	$title = "My Videos";
	if ($_GET['sandbox']) {
		$is_sandbox = true;
		$title = "Sandbox";
	}

	if (empty($api_token)) { 
		showAPITokenForm();
	} else {
		require_once('include/class.playwire.php');

		$playwire = new Playwire($api_token);

		?>
		<table width="90%" cellpading="1" cellspacing="1" border="1" style="padding-left:13px;">
		<tr><td><h3 style="padding-left:44px;"><?php echo $title ?> for token <?php echo $api_token ?></h3>
		<h4 style="padding-left:44px;">If you would like any of these videos to be displayed on your blog post, please copy the short code of the particular video (e.g. [blogvideo id="11590"]) from the listing below and paste it in the blog post.</h4>
		<!--<a href="admin.php?page=mt_sublevel_handle&clear_token=1">Clear API Token</a><br/><br/>-->
		<?php
			function paginationLink($is_sandbox, $count, $page)
			{
				$result = '';
				$result .= "<li><a href=\"admin.php?page=mt_sublevel_handle&count=".$count."&pageno=".$page;
				if ($is_sandbox)
					$result .= '&sandbox=1';
				$result .= "\">".$page."</a></li>";
				return $result;
			}

			if ($is_sandbox) {
				$total_videos = $playwire->getVideoSandboxCount();
				//echo '<a href="admin.php?page=mt_sublevel_handle" style="padding-left:44px;">Go To My Videos</a>';
			} else {
				$total_videos = $playwire->getVideoCount();
				//echo '<a href="admin.php?page=mt_sublevel_handle&sandbox=1" style="padding-left:44px;">Go To Video Sandbox</a>';
			}
			echo '<a href="admin.php?page=mt-toplevel-handle" style="padding-left:44px;">Add New Video</a>';

			echo "<h4 style='padding-left:44px;'>Total videos is " . $total_videos . "</h4>";

			$page = 1;
			if ($_GET['pageno']) {
				$page = $_GET['pageno'];
			}
			$count = 20;
			if ($_GET['count']) {
				$count = $_GET['count'];
			}
			$sort_by = '';
			if ($_GET['sort']) {
				$sort_by = $_GET['sort'];
			}

			echo "<h4 style='padding-left:44px;'>Videos per page: ";
			foreach(array(10, 20, 30, 40, 50) as $per_page) {
				if ($count == $per_page) {
					echo $per_page . " ";
				} else {
					if ($is_sandbox)
						echo "<a href=\"admin.php?page=mt_sublevel_handle&count=" . $per_page . "&sandbox=1\">" . $per_page . "</a> ";
					else
						echo "<a href=\"admin.php?page=mt_sublevel_handle&count=" . $per_page . "\">" . $per_page . "</a> ";
				}
			}
			echo "</h4>";
			$params = array();
			if ($sort_by != '') {
				$params['get'] = array();
				$params['get']['sort'] = $sort_by;
			}

			echo '<div class="videos">';
			if ($is_sandbox)
				$videos = $playwire->getVideoSandboxIndex($count, $page, $params);
			else
				$videos = $playwire->getVideoIndex($count, $page, $params);
			echo '<div class="wrapper">';
			echo '<div class="main">';

			//start video page outer
		    	echo '<div class="video_pageOuter">';
		
				//start video page header
				echo '<div class="video_pageheader">';
			    	echo '<ul>'; ?>
				        <li><span><a>Sort by:</a></span></li>
				    <?php
				    if ($is_sandbox) { 
					    if($_GET["sort"] == "created_at") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=created_at_desc&sandbox=1">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } 
					    else if($_GET["sort"] == "created_at_desc") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=created_at&sandbox=1">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } else { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=created_at&sandbox=1">Date Created</a></span></li>
						
					    <?php }

					    if($_GET["sort"] == "title") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=title_desc&sandbox=1">Title<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					   <?php } elseif($_GET["sort"] == "title_desc") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=title&sandbox=1">Title<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } else {
					    	echo '<li><span><a href="admin.php?page=mt_sublevel_handle&sort=title&sandbox=1">Title</a></span></li>';
					    }

					    if($_GET["sort"] == "total_views") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=total_views_desc&sandbox=1">Views<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } elseif($_GET["sort"] == "total_views_desc") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=total_views&sandbox=1">Views<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } else {
					    	echo '<li><span><a href="admin.php?page=mt_sublevel_handle&sort=total_views&sandbox=1">Views</a></span></li>';
					    }
				    } else {
					     if($_GET["sort"] == "created_at") {?>
					 	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=created_at_desc">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					<?php } else if($_GET["sort"] == "created_at_desc") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=created_at">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } else { ?>
						<li><span><a href="admin.php?page=mt_sublevel_handle&sort=created_at">Date Created</a></span></li>
					<?php }
					     if($_GET["sort"] == "title") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=title_desc">Title<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					   <?php } elseif($_GET["sort"] == "title_desc") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=title">Title<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } else {
					    	echo '<li><span><a href="admin.php?page=mt_sublevel_handle&sort=title">Title</a></span></li>';
					    }
					    if($_GET["sort"] == "total_views") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=total_views_desc">Views<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } elseif($_GET["sort"] == "total_views_desc") { ?>
					    	<li><span><a href="admin.php?page=mt_sublevel_handle&sort=total_views">Views<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
					    <?php } else {
					    	echo '<li><span><a href="admin.php?page=mt_sublevel_handle&sort=total_views">Views</a></span></li>';
					    }
				    }
				echo '</ul>';
			    echo '</div>';
			    //end video page header

		    	echo '<div class="video_page">';
			echo '<ul>';
			$counter = 0; 
			foreach($videos as $video) { $counter++;
				echo '<li>';
					echo '<div class="videoBoxOuter">';
				        echo '<div class="videoBox">';
					if ($is_sandbox)
						$link = "admin.php?page=mt-view-level-handle&sandbox=1&id=".$video->id;
					else
						$link = "admin.php?page=mt-view-level-handle&id=".$video->id;
				        echo '<p><a href="'.$link.'"><img class="thumbnail" src="'.$video->thumb_url.'"/></a></p>';
					$retStr=$video->name;
					$strLength=strlen($video->name);
					$retStr=substr($retStr,0,20);
					if($strLength > 20){
						echo '<h4>'.$retStr=$retStr."...</h4>";
					} else {
						echo '<h4>'.$retStr.'</h4>';
					}
				            echo '<p class="view"><strong>Views:</strong>'.$video->total_views.'</p>';
				            echo '<p class="date"><strong>Date Created:</strong>'.$video->created_at.'</p>';
				            echo '<p class="category"><strong>Category:</strong>'.$video->category_name.'</p>';
					    if ($is_sandbox)
					    	echo '<p class="category"><strong>Short Code:</strong>[blogvideo id="'.$video->id.'" is_sandbox="1"]</p>';
					    else 
						echo '<p class="category"><strong>Short Code:</strong>[blogvideo id="'.$video->id.'"]</p>';
				        echo '</div>';
				        echo '<div class="videoLinks">';
				        	echo '<span class="viewVideo"><a href="'.$link.'">View Video</a></span>'; ?>
				                <span class="deleteVideo"><a href="admin.php?page=mt-delete-level-handle&id=<?php echo $video->id; ?>" onclick="return confirm('Are you sure you want to delete this video?')">Delete Video</a></span>
					<?php
				        echo '</div>';
				    echo '</div>';
				echo '</li>';
				if($counter%4==0)
				echo '</ul><ul>';
			}
			echo '</div>';

			 echo '</div>';
			//end video page outer
		
			//start video pageing
			echo '<div class="video_pageing">';
				echo '<ul>';

				$iterations = ceil($total_videos/$count);
		
				for ($i = 1; $i <= $iterations; $i++) {
					if ($page == $i)
						echo '<li><a class="active">'.($i).'</a></li>';
					else
						echo paginationLink($is_sandbox, $count, $i) . " ";
				}
				?>
				<li><a><img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/right_arow.png" alt="" border="0" /></a></li>
				<?php
			    echo '</ul>';
		      echo '</div>';


		    echo '</div>';
		echo '</div>';
		?>
		</div>
		</td></tr></table>
		<?php

	} // for top level if
}
//Function to listing the videos end here

//Function to show video at front end start from here
function show_frontend_video() {
	require_once('include/common.php');
	$is_sandbox = false;
	$title = "My Videos";
	if ($_GET['sandbox']) {
		$is_sandbox = true;
		$title = "Sandbox";
	}
	if(empty($api_token)) { 
		//showAPITokenForm();
		echo "<b>No video found.</b><br />";
	} else {
		require_once('include/class.playwire.php');

		$playwire = new Playwire($api_token);
		
		//single video view condition start
		if(!empty($_GET["videoid"]))
		{ 
			$id = $_GET['videoid'];
			//code to check if the video is coming from sendbox
			if ($is_sandbox) {
				if ($id) {
					try {
						$video = $playwire->getSandboxVideo($id);
					} catch (PlaywireException $exception) {
						echo "Had an exception: " . $exception->getMessage();
					}
				} else
					echo "No ID provided, cannot show video.";
				if ($video) { ?>
					<div>
						<h3><?php echo $video->name; ?></h3>
					</div>
					<div>
						<?php echo $video->js_embed_code; ?>
					</div>
					<div style="font-size:14px; font-weight:normal;">
						<label for="video[description]">Description:</label><br/>
						<p><?php echo $video->description; ?></p>
					</div>
					<div style="font-size:14px; font-weight:normal;">
						<label for="video[tags]">Tags:</label>
						<p><?php echo implode(', ', $video->tags); ?></p>
					</div>
			       <?php } 
				

			} //code to check if the video is coming from sendbox end........
			//code to check if the video is coming from my videos.
			else {
				if($id) {
					try { 
						$video = $playwire->getVideo($id);
						$categories = $playwire->getVideoCategories();

					} catch (PlaywireException $exception) {
						echo "Had an exception: " . $exception->getMessage();
					}
				}


				if ($video) { //echo "<pre>"; print_r($video); ?>

					<div style="font-size:18px; font-weight:bold;">
						<?php echo $video->name; ?>
					</div>
					<div>&nbsp;</div>

					<div>
						<?php 
							if ($video->status == 'encoding')
								echo "Video is still encoding.";
							else
								echo $video->js_embed_code;
						?>
					</div>

					<div style="font-size:14px; font-weight:normal;">
						<b>Description:</b><br /><?php echo $video->description; ?>
					</div>

					<div style="font-size:14px; font-weight:normal;"> <b>Category:</b>
						<?php
							foreach($categories as $category) {
								if (intval($category->id) == intval($video->category_id))
									echo $category->name;
							}
						?>
					</div>
					<div style="font-size:14px; font-weight:normal;">
						<br/>

						<b>Aspect Ratio:</b> <?php echo $video->aspect_ratio; ?><br/>
						<b>Status:</b> <?php echo $video->status; ?><br/>
						<b>Uploaded on</b> <?php echo $video->created_at; ?><br/>

						<b>Total Views:</b> <?php echo $video->total_views; ?><br/>
						<b>Total Bandwidth:</b> <?php echo $video->total_bandwidth; ?> MB<br/>
					</div>
					<div>&nbsp;</div>
				<?php } 
			} //code to check if the video is coming from my videos end..........

		} //single video view condition end....
		else { //start listing video condition

			?>
			<h3>Videos</h3>
			<?php
				function paginationLink($is_sandbox, $count, $page)
				{
					$result = '';
					$result .= "<li><a href=\"index.php?count=".$count."&page=".$page;
					if ($is_sandbox)
						$result .= '&sandbox=1';
					$result .= "\">".$page."</a></li>";
					return $result;
				}


				if ($is_sandbox) {
					$total_videos = $playwire->getVideoSandboxCount();
					//echo '<a href="index.php">Go To Videos</a>';
				} else {
					$total_videos = $playwire->getVideoCount();
					//echo '<a href="index.php?sandbox=1">Go To Video Sandbox</a>';
				} 

				//echo "<h4>Total videos is " . $total_videos . "</h4>";

				$page = 1;
				if ($_GET['page']) {
					$page = $_GET['page'];
				}
				$count = 15;
				if ($_GET['count']) {
					$count = $_GET['count'];
				}
				$sort_by = '';
				if ($_GET['sort']) {
					$sort_by = $_GET['sort'];
				}

				$params = array();
				if ($sort_by != '') {
					$params['get'] = array();
					$params['get']['sort'] = $sort_by;
				}

				echo '<div class="videos">';
				if ($is_sandbox)
					$videos = $playwire->getVideoSandboxIndex($count, $page, $params);
				else
					$videos = $playwire->getVideoIndex($count, $page, $params);

				echo '<div class="wrapperfront">';
				echo '<div class="mainfront">';

				//start video page outer
			    	echo '<div class="video_pageOuterfront">';
		
					//start video page header
					echo '<div class="video_pageheaderfront">';
				    	echo '<ul>'; ?>
						<li><img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_icn.jpg" alt="" border="0" /></li>
						<li><img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/gray_icn.jpg" alt="" border="0" /></li>
						<li><span><a>Sort by:</a></span></li>
					    <?php
					    if ($is_sandbox) { 
						    if($_GET["sort"] == "created_at") {?>
						    	<li><span><a href="index.php?sort=created_at_desc&sandbox=1">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
						    <?php } else { ?>
							<li><span><a href="index.php?sort=created_at&sandbox=1">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
						    <?php }
						    if($_GET["sort"] == "title") {
						    	echo '<li><span><a href="index.php?sort=title_desc&sandbox=1">Title</a></span></li>';
						    } else {
						 	echo '<li><span><a href="index.php?sort=title&sandbox=1">Title</a></span></li>';
						    }
						    if($_GET["sort"] == "total_views") {
						    	echo '<li><span><a href="index.php?sort=total_views_desc&sandbox=1">Views</a></span></li>';
						    } else {
							echo '<li><span><a href="index.php?sort=total_views&sandbox=1">Views</a></span></li>';
						    }
					    } else {
						     if($_GET["sort"] == "created_at") {?>
						    	<li><span><a href="index.php?sort=created_at_desc">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_down_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
						    <?php } else { ?>
							<li><span><a href="index.php?sort=created_at">Date Created<img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/green_top_arrow.jpg" alt="" style="margin:0 3px;" /></a></span></li>
						    <?php }
						    if($_GET["sort"] == "title") {
						    	echo '<li><span><a href="index.php?sort=title_desc">Title</a></span></li>';
						    } else {
						    	echo '<li><span><a href="index.php?sort=title">Title</a></span></li>';
						    }
						    if($_GET["sort"] == "total_views") {
						    	echo '<li><span><a href="index.php?sort=total_views_desc">Views</a></span></li>';
						    } else {
							echo '<li><span><a href="index.php?sort=total_views">Views</a></span></li>';
						    }
					    }
					echo '</ul>';
				    	echo '</div>';
				    	//end video page header

				    	echo '<div class="video_pagefront">';
					echo '<ul>';
					$counter = 0; 
					foreach($videos as $video) { $counter++;
						echo '<li>';
							echo '<div class="videoBoxOuter">';
							echo '<div class="videoBox">';
							if ($is_sandbox)
								$link = "index.php?sandbox=1&videoid=".$video->id;
							else
								$link = "index.php?videoid=".$video->id;
							echo '<p><a href="'.$link.'"><img class="thumbnail" src="'.$video->thumb_url.'"/></a></p>';
							$retStr=$video->name;
							$strLength=strlen($video->name);
							$retStr=substr($retStr,0,20);
							if($strLength > 20){
								echo '<h4>'.$retStr=$retStr."...</h4>";
							} else {
								echo '<h4>'.$retStr.'</h4>';
							}
							    echo '<p class="view"><strong>Views:</strong>'.$video->total_views.'</p>';
							    echo '<p class="date"><strong>Date Created:</strong>'.$video->created_at.'</p>';
							    echo '<p class="category"><strong>Category:</strong>'.$video->category_name.'</p>';
							echo '</div>';
							echo '<div class="videoLinks">';
								echo '<span class="viewVideo"><a href="'.$link.'">View Video</a></span>';
							echo '</div>';
						    echo '</div>';
						echo '</li>';
						if($counter%3==0)
						echo '</ul><ul>';
					}
					echo '</div>';

					 echo '</div>';
					//end video page outer
		
					//start video pageing
					echo '<div class="video_pageingfront">';
						echo '<ul>';

						$iterations = ceil($total_videos/$count);
		
						for ($i = 1; $i <= $iterations; $i++) {
							if ($page == $i)
								echo '<li><a class="active">'.($i).'</a></li>';
							else
								echo paginationLink($is_sandbox, $count, $i) . " ";
						}
						?>
						<li><a><img src="<?php echo bloginfo('url');?>/wp-content/plugins/video-plugin/images/right_arow.png" alt="" border="0" /></a></li>
						<?php
					    echo '</ul>';
				       echo '</div>';


				     echo '</div>';
				 echo '</div>';




			?>
			</div>
			<?php
			} //end listing video condition


		} // for top level if
}

//Function to show video at front end end here..........

//Function to show video at front end start from here
function show_blog_video($videoId, $is_sandbox='') {
	require_once('include/common.php');
	$is_sandbox = false;
	$title = "My Videos";
	if ($videoId["is_sandbox"] == "1") {
		$is_sandbox = true;
		$title = "Sandbox";
	}
	if(empty($api_token)) { 
		echo "<b>No video found.</b><br />";
	} else {
		require_once('include/class.playwire.php');
		//echo $videoId; die("hello");
		$playwire = new Playwire($api_token);

		$id = $videoId["id"];
			//code to check if the video is coming from sendbox
			if ($is_sandbox) {
				if ($id) {
					try {
						$video = $playwire->getSandboxVideo($id);
					} catch (PlaywireException $exception) {
						echo "Had an exception: " . $exception->getMessage();
					}
				} else
					echo "No ID provided, cannot show video.";

				if ($video) { ?>
					<div>
						<?php return $video->js_embed_code; ?>
					</div>
					<div>&nbsp;</div>
					
			       <?php } 
				

			} //code to check if the video is coming from sendbox end........
			//code to check if the video is coming from my videos.
			else {
				if($id) {
					try { 
						$video = $playwire->getVideo($id);
						$categories = $playwire->getVideoCategories();

					} catch (PlaywireException $exception) {
						echo "Had an exception: " . $exception->getMessage();
					}
				}


				if ($video) { //echo "<pre>"; print_r($video); ?>
					<div>&nbsp;</div>

					<div>
						<?php 
							if ($video->status == 'encoding')
								echo "Video is still encoding.";
							else
								return $video->js_embed_code;
						?>
					</div>
					<div>&nbsp;</div>
			       <?php } 
			} //code to check if the video is coming from my videos end..........	


		
	} // for top level if

}
add_shortcode('blogvideo', 'show_blog_video'); 
?>
