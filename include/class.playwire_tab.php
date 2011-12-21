<?php

require_once('class.playwire.php');
class playwire_tab {
	
	#protected $api_token = 'V1Msykm6WjBfcLPY';
	protected $playwire;
	protected $api_key;

	function __construct($plugin) {
		
		$this->api_key = get_option('playwire-api-key');
		if(!empty($this->api_key)) {
			$this->playwire = new Playwire($this->api_key);
			add_action('admin_init', array(&$this, 'admin_init'));
		}
		
	}

	
	function admin_init() {
		//Enqueue JS & CSS
		add_action('media_upload_playwire', array(&$this, 'add_styles') );
		//Add actions/filters
		add_filter('media_upload_tabs', array(&$this, 'tabs'));
		add_action('media_upload_playwire', array(&$this, 'tab_handler'));
	
	}
	
	//Add a tab to the media uploader:
	function tabs($tabs) {
		$tabs['playwire'] = 'Add From Playwire';	
		return $tabs;
	}
	
	function add_styles() {
		//Enqueue support files.
		if ( 'media_upload_playwire' == current_filter() )
			wp_enqueue_style('media');
	}

	//Handle the actual page:
	function tab_handler(){


		//Set the body ID
		$GLOBALS['body_id'] = 'media-upload';

		//Do an IFrame header
		iframe_header( 'Add From Playwire');

		//Add the Media buttons	
		media_upload_header();

		//Handle any imports:
		#$this->handle_imports();

		//Do the content
		#$this->main_content();
		#$this->listing();
		$list_table = new playwire_list_table();
		$list_table->tab_view = true;
		$list_table->prepare_items();
		
		echo '<h3 class="media-title">Add a video from Playwire</h3>';
		$list_table->display();

		//Do a footer
		iframe_footer();
	}
	
	function listing() {
		$sandbox = isset($_GET['sandbox']) && $_GET['sandbox'] == 1;
		$page = isset($_GET['pageno']) ? $_GET['pageno'] : 1;
		$count = isset($_GET['count']) ? $_GET['count'] : 20;
		$sort_by = isset($_GET['sort']) ? $_GET['sort'] : '';

		$params = $sort_by ? array('get' => array('sort' => $sort_by)) : array();


		$api_key = $this->api_key; 
		if($sandbox) {
			$total_videos = $this->playwire->getVideoSandboxCount();
			$videos  = $this->playwire->getVideoSandboxIndex($count, $page, $params);

		} else {
			$total_videos = $this->playwire->getVideoCount();
			$videos  = $this->playwire->getVideoIndex($count, $page, $params);
		}

		include 'listing.tpl.php';
	}
	

	//Create the content for the page
	function main_content() {
		$videos = $this->playwire->getVideoSandboxIndex(20, 1, false);
		$short_code = '[blogvideo id="%id"]';

		echo '
		<script type="text/javascript">
		var win = window.dialogArguments || opener || parent || top;
		var send = function(id) {
			win.send_to_editor(\'[blogvideo id="\'+id+\'"]\');
		}
		</script>
		';

		echo "	
//<![CDATA[
(function(){
console.log('helloooooo');
})();
//]]>";


		$link = '<a href="javascript:void(0)" onclick="send(%1$d)" id="%1$d" class="playwire_short_code">%2$s</a><br/>';
	
		foreach($videos as $video) {
			#echo $video->name.'<br />';
			printf($link, $video->id, $video->name);

		}
	}

}//end class

?>
