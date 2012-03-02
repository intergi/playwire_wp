<?php
class playwire_media {

	protected $playwire = false;
	protected $api_key;

	function __construct($plugin) {	
			
		$this->api_key = get_option('playwire-api-key');
		if(!empty($this->api_key)) {
			$this->playwire = new Playwire($this->api_key);
			
		}

		add_action('admin_init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'menu'));
		
		add_shortcode('blogvideo', array(&$this, 'embed_video'));
		
		
	}

	function init() {
		register_setting('playwire', 'playwire-api-key');
		

		# Respond to ajax calls
		add_action('wp_ajax_playwire', array(&$this, 'view_video'));
	}

	function menu() {
		if($this->playwire) {
			## Main Menu
			#(page_title, menu_title, capability, menu_slug, func, icon, position)
			add_menu_page('Playwire', 'Playwire', '', 'playwire_menu', array(&$this, 'list_videos'));	
		    
		    #(parent_slug, page_title, menu_title, capability, menu_slug, func)
			add_submenu_page('playwire_menu', '', 'List Videos', 'publish_posts', 'playwire-list', array(&$this, 'list_videos_wp'));
			add_submenu_page('playwire_menu', '','Add Video',   'publish_posts', 'playwire-add', array(&$this, 'new_video'));
			add_submenu_page('playwire_menu', '','',   'publish_posts', 'playwire-delete', array(&$this, 'delete_video'));
		} else {
			add_menu_page('Playwire', 'Playwire', '', 'playwire_menu', array(&$this, 'options_page'));	
		    
		    #(parent_slug, page_title, menu_title, capability, menu_slug, func)
			add_submenu_page('playwire_menu', '', 'List Videos', 'publish_posts', 'playwire-list', array(&$this, 'options_page'));
			add_submenu_page('playwire_menu', '','Add Video',   'publish_posts', 'playwire-add', array(&$this, 'options_page'));
			add_submenu_page('playwire_menu', '','',   'publish_posts', 'playwire-delete', array(&$this, 'options_page'));
		}
		


		## Options Page
		#(page_title, menu_title, capability, slug, function)
		add_options_page('Playwire', 'Playwire', 'manage_options', 'playwire-settings', array(&$this, 'options_page'));
	}


	function options_page() {
		$missing = !is_object($this->playwire);
		include 'options.tpl.php';
	}

	function list_videos_wp() {
		$list_table = new playwire_list_table();
		$list_table->prepare_items();

		add_thickbox();
		echo '<div class="wrap nosubsub">';
		screen_icon('upload');
		echo '<h2>Playwire Videos <a href="?page=playwire-add" class="add-new-h2">Add New</a></h2>';

		$list_table->display();
		echo '</div>';
	}

	# Upload a new video to playwire
	function new_video() {
		if($_SERVER['REQUEST_METHOD'] == 'POST') {

			$name_parts = explode('.', basename($_FILES['source']['name']));
			$filename = uniqid().'.'.array_pop($name_parts);

			$upload = wp_upload_bits($filename, null, file_get_contents($_FILES["source"]["tmp_name"]));
			print_r($upload);
			if(!$upload['error']) {
				$video = new Video();
				$video->name = $_POST['name'];
				$video->description = $_POST['description'];
				$video->category_id = $_POST['category_id'];
				$video->width = $_POST['width'];
				$video->height = $_POST['height'];
				$video->tag_list = $_POST['tag_list'];
				$video->show_video_watermark = isset($_POST['show_video_watermark']);
				$video->use_age_gate = isset($_POST['use_age_gate']);
				$video->auto_start = isset($_POST['autostart']);
				

				$video->source_url = $upload['url'];

				try{
					$new_video = $this->playwire->uploadVideo($video);
					header('Location: ?page=playwire-list');
					exit();
				} catch(PlaywireException $exception) {
					echo "Had an exception: " . $exception->getMessage();
				}
			}
			

		} else {
			$defaults = $this->playwire->getVideoDefaults();
			$categories = $this->playwire->getVideoCategories();

			include 'add.tpl.php';
		}
	}

	# View a single video
	function view_video() {
		$sandbox = isset($_GET['sandbox']) && $_GET['sandbox'] == 1;
		$id = $_GET['id'];
		
		$video = ($sandbox) ? $this->playwire->getSandboxVideo($id) : $this->playwire->getVideo($id);
		include 'view.tpl.php';
		exit();
	}

	# Delete a single video
	function delete_video() {
		$error = false;
		try {
			$this->playwire->deleteVideo($_GET['id']);
			header('Location: ?page=playwire-list');
			exit();
		} catch (PlaywireException $exception) {
			$error = $exception->getMessage();
		}
	}

	# Embed the video via HTML
	# Used via short codes in a post
	function embed_video($id, $sandbox = false) {
		$id = $id['id'];
		$video = ($sandbox) ? $this->playwire->getSandboxVideo($id) : $this->playwire->getVideo($id);

		echo $video->js_embed_code;
	}
}


?>