<?php
/**
 * Plugin Name: WP Additions - Pagination
 * Plugin URI: http://www.p51labs.com/wordpress-additions-pagination/
 * Description: Converts Wordpress Admin Pagination (Posts, Pages, Comments, Users) to an AJAX Slider System.
 * Version: 1.0.3
 * Author: Kevin Miller
 * Author URI: http://www.p51labs.com
 * Text Domain: wp-additions-pagination
*/

/*  Copyright 2009 Kevin Miller (url : http://www.p51labs.com), All Rights Reserved

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class wp_additions_pagination
{
	/**
	 * @var string Rows to show
	 */
	public $show_rows;

	/**
	 * @var array Type to load
	 */
	public $type;

	/**
	 * @var array All possible types
	 */
	public $types;

	/**
	 * @var string HTML Prefix
	 */
	public $html_prefix = 'wpa';
	
	public function __construct() 
	{
		$this->detect();
		
		$this->types = array(
			'posts' => array(
				'callback' => array(&$this, 'wp_posts')
				,'callback_js' => 'wpa_pagination_posts'
				,'filters' => array(
					'm' => 'select'
					,'cat' => 'select'
				)
			)
			,'comments' => array(
				'callback' => array(&$this, 'wp_comments')
				,'callback_js' => 'wpa_pagination_comments'
				,'filters' => array(
					'comment_type' => 'select'
				)
			)
			,'pages' => array(
				'callback' => array(&$this, 'wp_pages')
				,'callback_js' => 'wpa_pagination_posts'
				,'filters' => array()
			)
			,'users' => array(
				'callback' => array(&$this, 'wp_users')
				,'filters' => array()
			)
		);
	}
	
	/**
	 * Detect what Type of Pagination to Load.
	 *
	 * @access public
	 */
	public function detect()
	{
		global $pagenow, $per_page;

		switch ($pagenow)
		{
			case 'edit.php':
				$this->type = 'posts';
			break;
			
			case 'edit-pages.php':
				$this->type = 'pages';
			break;

			case 'edit-comments.php':
				$this->type = 'comments';
			break;
			
			case 'link-manager.php':
				$this->type = 'links';
			break;
			
			case 'users.php':
				$this->type = 'users';
			break;
			
			default:
				$this->type = FALSE;
			break;
		}
	}

	/**
	 * Function to allow login with E-Mail address as well as username.
   *
 	 * @access public
 	 */
	public function load_plugin()
	{
		if ($this->type)
		{
			add_action('admin_print_scripts', array(&$this, 'admin_scripts'));
			add_action('admin_print_styles', array(&$this, 'admin_styles'));
		}
		add_action('wp_ajax_wpa_pagination', array(&$this, 'pagination'));
	}

	/**
	 * Load admin scripts, updates jquery and the jquery-ui, and builds javascript configuration.
	 *
	 * @access public
	 */
	public function admin_scripts()
	{
	  global $wp_version;
	
		if (strstr($wp_version, '2.7'))
		{
	  	wp_deregister_script('jquery');
    	wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js', false, '1.3.2');
			wp_enqueue_script('jquery');
		}
		
		wp_deregister_script('jquery-ui-core');
		wp_deregister_script('jquery-ui-tabs');
		wp_deregister_script('jquery-ui-sortable');
		wp_deregister_script('jquery-ui-draggable');
		wp_deregister_script('jquery-ui-resizable');
		wp_deregister_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.js', array('jquery'), '1.7.2');
		
		wp_deregister_script('admin-comments');
		wp_register_script('admin-comments', '/wp-admin/js/edit-comments.js', array('wp-lists', 'jquery-ui', 'quicktags'), '20081210');
		wp_enqueue_script('admin-comments');

		wp_enqueue_script('wpa-pagination', WP_PLUGIN_URL . '/wp-additions-pagination/public/js/wpa-pagination.js', array('jquery', 'jquery-ui', 'wp-ajax-response'), '1.0');
?>

	<script type="text/javascript">
	/* <![CDATA[ */
		var wpa_pagination_options = {
			prefix: '<?php echo $this->html_prefix; ?>-'
			,ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>'
			,type: '<?php echo $this->type; ?>'
			,show_rows: <?php echo $this->show_rows ? $this->show_rows : 'null'; ?>
			,ajax_action: 'wpa_pagination'
			,display: {
			  prefix: '<?php echo __('Displaying'); ?>'
			  ,middle: '<?php echo __('of'); ?>'
			}
			,types: {
			<?php $m = 0; foreach ($this->types as $type => $values): ?>
				<?php echo ($m > 0 ? ',' : ''); $m++; ?><?php echo $type; ?>: {
				<?php $n = 0; foreach ($values['filters'] as $filter => $input): ?>
					<?php echo ($n > 0 ? ',' : ''); $n++; ?><?php echo $filter; ?>: '<?php echo $input; ?>'
				<?php endforeach; ?>
					<?php echo ($n > 0 ? ',' : ''); $n = 0; ?>_callback: '<?php echo $values['callback_js']; ?>'
				}
			<?php endforeach; ?>
			}
			,filters: {
			<?php $m = 0; foreach ($_GET as $key => $value): ?>
				<?php echo ($m > 0 ? ',' : ''); $m++; ?><?php echo $key; ?>: '<?php echo $value; ?>'
			<?php endforeach; ?>
			}
		};
	/* ]]> */
	</script>

<?php
	}

	/**
	 * Load admin styles.
	 *
	 * @access public
	 */
	public function admin_styles()
	{
		wp_enqueue_style('wpa-pagination', WP_PLUGIN_URL . '/wp-additions-pagination/public/css/wpa-pagination.css'); 
	}

	/**
	 * Send XML Response
	 *
	 * @access public
	 */
	public function xml_send($body)
	{
		$xml = new WP_Ajax_Response();
		$xml->add(array(
			'what' => $this->type,
			'data' => $body
		));
		$xml->send();
		
		die('');
	}
	
	/**
	 * Route the AJAX pagination call.
	 *
	 * @access public
	 */
	public function pagination()
	{
		if (isset($_POST['pagination_type']))
		{
			$this->type = $_POST['pagination_type'];
			call_user_func_array($this->types[$this->type]['callback'], $this->types[$this->type]);
		}
	}
	
	/**
	 * Pagination for Wordpress Posts.
	 *
	 * @access public
	 */
	public function wp_posts()
	{
		$args = array(
			'offset' => ($_POST['pagination_offset'] - 1) * $_POST['pagination_show_rows']
			,'posts_per_page' => $_POST['pagination_show_rows']
		);
		
		if (isset($_POST['post_status']) && in_array($_POST['post_status'], array('publish', 'future', 'pending', 'draft', 'private'))) 
		{
			$args['post_status'] = $_POST['post_status'];
		}
		
		if (isset($_POST['cat']))
		{
			$args['cat'] = $_POST['cat'];
		}
		
		if (isset($_POST['m']))
		{
			$args['monthnum'] = substr($_POST['m'], 4);
			$args['year'] = substr($_POST['m'], 0, 4);
		}

		query_posts($args);
		
		ob_start();
			post_rows();
			$body = ob_get_contents();
		ob_end_clean();
		
		$this->xml_send($body);
	}
	
	/**
	 * Pagination for Wordpress Pages.
	 *
	 * @access public
	 */
	public function wp_pages()
	{
		$query = array(
			'post_type' => 'page'
			,'orderby' => 'menu_order title'
			,'what_to_show' => 'posts'
			,'posts_per_page' => -1
			,'posts_per_archive_page' => -1
			,'order' => 'asc'
		);

		if (isset($_POST['post_status']) && in_array($_POST['post_status'], array('publish', 'future', 'pending', 'draft', 'private'))) 
		{
			$query['post_status'] = $_POST['post_status'];
			$query['perm'] = 'readable';
		}

		$query = apply_filters('manage_pages_query', $query);
		
		wp($query);
		
		ob_start();
			page_rows($posts, $_POST['pagination_offset'], $_POST['pagination_show_rows']);
			$body = ob_get_contents();
		ob_end_clean();
		
		$this->xml_send($body);		
	}
	
	/**
	 * Pagination for Wordpress Users.
	 *
	 * @access public
	 */
	public function wp_users()
	{
		$usersearch = isset($_POST['usersearch']) ? $_POST['usersearch'] : null;
		$userspage = isset($_POST['pagination_offset']) ? $_POST['pagination_offset'] : null;
		$role = isset($_POST['role']) ? $_POST['role'] : null;
		
		$wp_user_search = new WP_User_Search($usersearch, $userspage, $role);
		
		$body = '';
		foreach ($wp_user_search->get_results() as $userid) 
		{
			$user_object = new WP_User($userid);
			$roles = $user_object->roles;
			$role = array_shift($roles);

			$style = (' class="alternate"' == $style) ? '' : ' class="alternate"';
			$body .= "\n\t" . user_row($user_object, $style, $role);
		}
		
		$this->xml_send($body);
	}
	
	/**
	 * Pagination for Wordpress Comments.
	 *
	 * @access public
	 */
	public function wp_comments()
	{
		$mode = (!isset($_POST['mode']) || empty($_POST['mode'])) ? 'detail' : attribute_escape($_POST['mode']);
		$comment_status = !empty($_POST['comment_status']) ? attribute_escape($_POST['comment_status']) : '';
		$comment_type = !empty($_POST['comment_type']) ? attribute_escape($_POST['comment_type']) : '';
		$search_dirty = (isset($_POST['s'])) ? $_POST['s'] : '';
		$search = attribute_escape( $search_dirty );
		
		$comments_per_page = apply_filters('comments_per_page', 20, $comment_status);

		$start = ( $_POST['pagination_offset'] - 1 ) * $comments_per_page;

		list($_comments, $total) = _wp_get_comment_list( $comment_status, $search_dirty, $start, $comments_per_page + 5, $post_id, $comment_type ); // Grab a few extra

		$_comment_post_ids = array();
		foreach ($_comments as $_c) 
		{
			$_comment_post_ids[] = $_c->comment_post_ID;
		}
		
		$_comment_pending_count_temp = (array) get_pending_comments_num($_comment_post_ids);
		foreach ((array) $_comment_post_ids as $_cpid)
		{
			$_comment_pending_count[$_cpid] = isset($_comment_pending_count_temp[$_cpid]) ? $_comment_pending_count_temp[$_cpid] : 0;
		}
		if ( empty($_comment_pending_count))
		{
			$_comment_pending_count = array();
		}
		
		if (get_option('show_avatars'))
		{
			add_filter( 'comment_author', 'floated_admin_avatar' );
		}
		
		$comments = array_slice($_comments, 0, $comments_per_page);
		
		ob_start();
			foreach ($comments as $comment)
			{
				echo _wp_comment_row($comment->comment_ID, $mode, $comment_status, true, true);
			}
			$body = ob_get_contents();
		ob_end_clean();

		$this->xml_send($body);
	}
	
}

/**
 * Load Plugin
 *
 */
 
  if (function_exists('add_action')) 
  {
  	$wpa = new wp_additions_pagination();

  	add_action('plugins_loaded', array(&$wpa, 'load_plugin'));
  }

?>