<?php
/*
Plugin Name: Rotating Header
Plugin URI: http://captico.com/wp/rotating-header
Description: Create a rotating header similar to how the builtin WP 3.0 header works but for multiple images that rotate at a set interval
Author: Jonathan Phillips and Todd Fisher
Version: 0.6
Author URI: http://www.captico.com
*/

class RotatingHeader {
	function RotatingHeader() {
		//Ajax functions
	  add_action('wp_print_styles', array(&$this, "add_dl_css"), 10);
		add_action('wp_print_scripts', array(&$this, "add_dl_js"), 10);
    add_action('admin_init', array(&$this, 'init_plugin'));
    add_action('admin_menu', array(&$this, 'attach_rotating_header_menu'));
    add_action('wp_ajax_update_rotating_header', array(&$this,'rotate_header_update'));

	}

  function init_plugin() {
    // register settings
    register_setting("rotating-header", "rh_transition","intval");
    register_setting("rotating-header", "rh_duration","intval");
    register_setting("rotating-header", "rh_transition_type");
  }

	function header_text() {
		if ( defined( 'NO_HEADER_TEXT' ) && NO_HEADER_TEXT )
			return false;

		return true;
	}

	function js_includes() {
		$step = $this->step();

		if ( ( 1 == $step || 3 == $step ) && $this->header_text() ) {
			wp_enqueue_script( 'farbtastic' );
      wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'jquery-ui-draggable' );
      wp_enqueue_script( 'jquery-ui-droppable' );
      wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'dl', plugins_url('/rotating-header/js/jquery-presenter.js', '0.1' ));
		} elseif ( 2 == $step ) {
			wp_enqueue_script('imgareaselect');
    }
	}

	function css_includes() {
		$step = $this->step();

		if ( ( 1 == $step || 3 == $step ) && $this->header_text() ) {
			wp_enqueue_style('farbtastic');
			wp_enqueue_style('dl', plugins_url('/rotating-header/css/dl.css'));
		} elseif ( 2 == $step ) {
			wp_enqueue_style('imgareaselect');
    }
	}
	
	function add_dl_css() {
	  wp_enqueue_style('dl', plugins_url('/rotating-header/css/dl.css'));
	}

  function attach_rotating_header_menu() {

    //add_options_page("Rotating Header", "Rotating Header", 'edit_themes', "functions.php", array(&$this,'rotating_header_setup_page'));
    //add_submenu_page("themes.php", "Rotating Header", "Rotating Header", 'edit_themes', __FILE__, array(&$this,'rotating_header_setup_page'));
    if (!defined('HEADER_IMAGE_WIDTH')) {
      define('HEADER_IMAGE_WIDTH',902);
    }
    if (!defined('HEADER_IMAGE_HEIGHT')) {
      define('HEADER_IMAGE_HEIGHT',248);
    }
		$this->page = $page = add_theme_page(__('Rotating Header'), __('Rotating Header'),
                              'edit_theme_options', __FILE__, array(&$this, 'rotating_header_setup_page'));
		add_action("admin_print_scripts-$page", array(&$this, 'js_includes'));
		add_action("admin_print_styles-$page", array(&$this, 'css_includes'));
    add_action("admin_head-$page", array(&$this, 'js'), 50);
  }

	function js() {
		$step = $this->step();
		if ( ( 1 == $step || 3 == $step ) && $this->header_text() )
			$this->js_1();
		elseif ( 2 == $step )
			$this->js_2();
	}

  function rotate_header_update() {
    // $_POST['url']
    $active_list_url = $_POST['active'];
    $inactive_list_url = $_POST['inactive'];

    if (!is_array($active_list_url) && !empty($active_list_url) ) { echo "error empty array"; die(); }
    if (!is_array($inactive_list_url) && !empty($inactive_list_url) ) { echo "error empty array"; die(); }

    set_theme_mod("available_headers",$inactive_list_url);
    set_theme_mod("active_headers",$active_list_url);

    rotating_header_draw();

    die();
  }

	function js_1() { ?>
<script type="text/javascript">
  (function($) {
    $(function() {
      /*var options = { 
                      rotation_delay:parseInt(<?php echo stripslashes(get_option('rh_duration')); ?>),
                      rotation_duration:parseInt(<?php echo stripslashes(get_option('rh_transition')); ?>),
                      transition: "<?php echo stripslashes(get_option('rh_transition_type')); ?>"
                    };

      $("#preview-header .rotating-header").dl(options);
      */

      function serializeLists() {
        var active = $("#active li").map(function() { return { image:    $(this).find('img').attr('src'),
                                                               link:     $(this).find('input[name=link]').val(),
                                                               message:  $(this).find('input[name=message]').val(),
                                                               color:    $(this).find('input[name=color]').val()
                                                             };
                                                    });
        var inactive = $("#inactive li").map(function() { return { image:   $(this).find('img').attr('src'),
                                                                   link:    $(this).find('input[name=link]').val(),
                                                                   message: $(this).find('input[name=message]').val(),
                                                                   color: $(this).find('input[name=color]').val()
                                                                  };
                                                         });
        $("#header-indicator").show();
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {action:'update_rotating_header', active:active, inactive:inactive}, function(data) {
          $("#preview-header").html(data);
          //$("#preview-header .rotating-header").dl({reset:true});
          $("#header-indicator").hide();
        });
      }

      $("#inactive").sortable({connectWith:'#active', stop: serializeLists});
      $("#active").sortable({connectWith:'#inactive', stop: serializeLists});
      $("#inactive input.text").live('change',serializeLists);
      $("#active input.text").live('change',serializeLists);
      $("#inactive input.save").live('click',serializeLists);
      $("#active input.save").live('click',serializeLists);
      $("#inactive input.delete").live('click',function(e) { e.preventDefault();
        if (confirm("Are you sure you want to remove this from your pool of rotating headers? You will not be able to recover it.")) {
          $(this).parent("li").remove();
          serializeLists();
        }
      });
      $("#active input.delete").live('click',function(e) { e.preventDefault();
        $("#inactive").append($(this).parent("li").detach());
        serializeLists();
      });
      $("#active input.color").live("click",function() {
        $("div.colorpicker").hide();
        $("#colorpicker").show();
        $("#colorpicker").css({left:($(this).offset().left-180)+'px',top:($(this).offset().top-350)+'px'});
        $(this).siblings("div.colorpicker").show();
        var self = this;
        $(this).siblings("div.colorpicker").farbtastic(function(c) {
          $(self).val(c);
          $(self).css({backgroundColor:c});
        });
      });
      $("#active input.color").live("blur",function() {
        $("div.colorpicker").hide();
      });
    });
  })(jQuery);
</script>
<?php
	}

	/**
	 * Display Javascript based on Step 2.
	 *
	 * @since 2.6.0
	 */
	function js_2() { ?>
<script type="text/javascript">
/* <![CDATA[ */
	function onEndCrop( coords ) {
		jQuery( '#x1' ).val(coords.x);
		jQuery( '#y1' ).val(coords.y);
		jQuery( '#width' ).val(coords.w);
		jQuery( '#height' ).val(coords.h);
	}

	jQuery(document).ready(function() {
		var xinit = <?php echo HEADER_IMAGE_WIDTH; ?>;
		var yinit = <?php echo HEADER_IMAGE_HEIGHT; ?>;
		var ratio = xinit / yinit;
		var ximg = jQuery('img#upload').width();
		var yimg = jQuery('img#upload').height();

		if ( yimg < yinit || ximg < xinit ) {
			if ( ximg / yimg > ratio ) {
				yinit = yimg;
				xinit = yinit * ratio;
			} else {
				xinit = ximg;
				yinit = xinit / ratio;
			}
		}

		jQuery('img#upload').imgAreaSelect({
			handles: true,
			keys: true,
			aspectRatio: xinit + ':' + yinit,
			show: true,
			x1: 0,
			y1: 0,
			x2: xinit,
			y2: yinit,
			maxHeight: <?php echo HEADER_IMAGE_HEIGHT; ?>,
			maxWidth: <?php echo HEADER_IMAGE_WIDTH; ?>,
			onInit: function () {
				jQuery('#width').val(xinit);
				jQuery('#height').val(yinit);
			},
			onSelectChange: function(img, c) {
				jQuery('#x1').val(c.x1);
				jQuery('#y1').val(c.y1);
				jQuery('#width').val(c.width);
				jQuery('#height').val(c.height);
			}
		});
	});
/* ]]> */
</script>
<?php
	}

  function step_1() {
  ?>
  <div class="wrap" style="position:relative;">
    <h2><?php _e('Custom Rotating Header'); ?></h2>
      <style type="text/css">
        #inactive, #active { border:2px solid #888; list-style-type: none; margin: 0; padding: 0; float: left; margin-right: 10px; background: #eee; padding: 5px; width: 355px; min-height:300px; }
        #inactive li, #active li { border:1px dashed #888; margin: 5px; padding: 5px; font-size: 1.2em; width: 332px; cursor: move; }
      </style>

      <div id="preview-header">
        <?php rotating_header_draw(); ?>
      </div>

      <form style="float:left;position:relative;width:350px" enctype="multipart/form-data" id="upload-form" method="post" action="<?php echo esc_attr( add_query_arg( 'step', 2 ) ) ?>">
        <h3>1. Upload a Header Image</h3>
        <p><?php _e( 'You can upload a custom header image to be shown at the top of your site instead of the default one. On the next screen you will be able to crop the image.' ); ?><br />
        <?php printf( __( 'Images of exactly <strong>%1$d &times; %2$d pixels</strong> will be used as-is.' ), HEADER_IMAGE_WIDTH, HEADER_IMAGE_HEIGHT ); ?></p>
        <p>
          <label for="upload"><?php _e( 'Choose an image from your computer:' ); ?></label><br />
          <input type="file" id="upload" name="import" />
          <input type="hidden" name="action" value="save" />
          <?php wp_nonce_field( 'custom-header-upload', '_wpnonce-custom-header-upload' ) ?>
          <input type="submit" class="button" value="<?php esc_attr_e( 'Upload' ); ?>" />
          <span style="display:none;position:absolute;top:210px;left:800px;width:100px;" id="header-indicator">Updating... <img src="/wp-admin/images/wpspin_light.gif"/></span>
        </p>
      </form>
      <div style="float:left;width:530px;margin-left:121px">
        <h3>2. Settings</h3>
        <form method="post" action="options.php">
          <?#php wp_nonce_field('update-options'); ?>
          <?php settings_fields('rotating-header'); ?>

          <table class="form-table">
            <tr valign="top">
              <th style="white-space:nowrap;" scope="row"><label for="rh_transition"><?php _e("Transition Time"); ?>:</label></th>
              <?php
                $selected = stripslashes(get_option('rh_transition'));
                if (!$selected) { $selected = 2000; }
              ?>
              <td><input size="8" id="rh_transition" type="text" name="rh_transition" value="<?php echo $selected ?>" /></td>
              <td style="width:100%;">The amount of time each slide takes to scroll.</td>
            </tr>
            <tr valign="top">
              <th style="white-space:nowrap;" scope="row"><label for="rh_duration"><?php _e("Duration"); ?>:</label></th>
              <?php
                $selected = stripslashes(get_option('rh_duration'));
                if (!$selected) { $selected = 2250; }
              ?>
              <td><input size="8" id="rh_duration" type="text" name="rh_duration" value="<?php echo $selected; ?>" /></td>
              <td style="width:100%;">The amount of time to stay on each slide.</td>
            </tr>
            <tr valign="top">
              <th style="white-space:nowrap;" scope="row"><label for="rh_transition_type"><?php _e("Transition Type"); ?>:</label></th>
              <td>
                <?php
                  $selected = stripslashes(get_option('rh_transition_type'));
                  if (!$selected) { $selected = 'slider'; }
                ?>
                <select id="rh_transition_type" name="rh_transition_type">
                  <option <?php if ($selected =='slider') { echo 'selected'; }  ?> value="slider">Slider</option>
                  <option <?php if ($selected =='crossfade') { echo 'selected'; } ?> value="crossfade">Cross Fade</option>
                </select>
              </td>
              <td style="width:100%;">The amount of time to stay on each slide.</td>
            </tr>
          </table>
 
          <!--<input type="hidden" name="action" value="update" />
          <input type="hidden" name="page_options" value="rh_transition,rh_duration"/>-->
 
          <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
        </form>
      </div>
      <div class="clear"></div>
      <?php
        $scaled_w = (1/3 * HEADER_IMAGE_WIDTH);
        $scaled_h = (1/3 * HEADER_IMAGE_HEIGHT);
      ?>
      <div style="width:800px;border-top:2px solid #888;">
        <div id="available_images" style="float: left; margin-right: 10px;">
          <h3>Available Images</h3>
          <ul id="inactive">
            <?php 
              $available = get_theme_mod("available_headers");
              if (is_array($available)) {
              foreach ($available as $header) {
                // migrate old scheme
                if (is_string($header)) {
                  $header = array('image' => $header); // assume old scheme that stored just an array of images
                }
            ?>
              <li>
                <img src="<?php echo $header['image']; ?>" width="<?php echo $scaled_w ?>px" height="<?php echo $scaled_h ?>px"/>
                <label>Link: </label><input style="margin-left:32px;width:256px" name="link" class="text" type="text" value="<?php echo stripslashes($header['link']) ?>"/>
                <label>Message: </label><input style="width:256px" class="text" name="message" type="text" value="<?php echo stripslashes($header['message']) ?>"/>
                <label>Color: </label><input style="margin-left:23px;width:64px;background:<?php echo stripslashes($header['color']) ?>" name="color" class="color" type="text" value="<?php echo stripslashes($header['color']) ?>"/>
                <input type="submit" value="Save" class="button save"/>
                <input type="submit" value="Delete" class="button delete"/>
                <div style="display:none;position:absolute;" class="colorpicker"></div>
              </li>
            <?php } } ?>
          </ul>
        </div>
        
        <div id="active_images" style="float:right;">
          <h3>Active Images</h3>
          <ul id="active">
            <?php 
              $active = get_theme_mod("active_headers");
              if (is_array($active)) {
              foreach ($active as $header) { 
            ?>
              <li>
                <img src="<?php echo $header['image']; ?>" width="<?php echo $scaled_w ?>px" height="<?php echo $scaled_h ?>px"/>
                <label>Link: </label><input style="margin-left:32px;width:256px" name="link" class="text" type="text" value="<?php echo stripslashes($header['link']) ?>"/>
                <label>Message: </label><input style="width:256px" class="text" name="message" type="text" value="<?php echo stripslashes($header['message']) ?>"/>
                <label>Color: </label><input style="margin-left:23px;width:64px;background:<?php echo stripslashes($header['color']) ?>" name="color" class="color" type="text" value="<?php echo stripslashes($header['color']) ?>"/>
                <input type="submit" value="Save" class="button save"/>
                <input type="submit" value="Delete" class="button delete"/>
                <div style="display:none;position:absolute;" class="colorpicker"></div>
              </li>
            <?php } } ?>
          </ul>
        </div>
        <div class="clear"></div>
      </div>
    </div>
      <?php
  }

  function step_2() {
		check_admin_referer('custom-header-upload', '_wpnonce-custom-header-upload');
		$overrides = array('test_form' => false);
		$file = wp_handle_upload($_FILES['import'], $overrides);

		if ( isset($file['error']) )
			wp_die( $file['error'],  __( 'Image Upload Error' ) );

		$url = $file['url'];
		$type = $file['type'];
		$file = $file['file'];
		$filename = basename($file);

		// Construct the object array
		$object = array(
		'post_title' => $filename,
		'post_content' => $url,
		'post_mime_type' => $type,
		'guid' => $url);

		// Save the data
		$id = wp_insert_attachment($object, $file);

		list($width, $height, $type, $attr) = getimagesize( $file );

		if ( $width == HEADER_IMAGE_WIDTH && $height == HEADER_IMAGE_HEIGHT ) {
			// Add the meta-data
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

      // hook into how we save the headers wp3 would set the single header here we set an array by pushing
      $available = get_theme_mod("available_headers");
      if (!is_array($available)) {
        $available = array();
      }
      array_push($available, esc_url($url));
			set_theme_mod('available_headers', $available);
      // end our hack

			do_action('wp_create_file_in_uploads', $file, $id); // For replication
			return $this->finished();
		} elseif ( $width > HEADER_IMAGE_WIDTH ) {
			$oitar = $width / HEADER_IMAGE_WIDTH;
			$image = wp_crop_image($file, 0, 0, $width, $height, HEADER_IMAGE_WIDTH, $height / $oitar, false, str_replace(basename($file), 'midsize-'.basename($file), $file));
			if ( is_wp_error( $image ) )
				wp_die( __( 'Image could not be processed.  Please go back and try again.' ), __( 'Image Processing Error' ) );

			$image = apply_filters('wp_create_file_in_uploads', $image, $id); // For replication

			$url = str_replace(basename($url), basename($image), $url);
			$width = $width / $oitar;
			$height = $height / $oitar;
		} else {
			$oitar = 1;
		}
		?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e( 'Crop Header Image' ); ?></h2>

<form method="post" action="<?php echo esc_attr(add_query_arg('step', 3)); ?>">
	<p class="hide-if-no-js"><?php _e('Choose the part of the image you want to use as your header.'); ?></p>
	<p class="hide-if-js"><strong><?php _e( 'You need Javascript to choose a part of the image.'); ?></strong></p>

	<div id="crop_image" style="position: relative">
		<img src="<?php echo esc_url( $url ); ?>" id="upload" width="<?php echo $width; ?>" height="<?php echo $height; ?>" />
	</div>

	<p class="submit">
	<input type="hidden" name="x1" id="x1" value="0"/>
	<input type="hidden" name="y1" id="y1" value="0"/>
	<input type="hidden" name="width" id="width" value="<?php echo esc_attr( $width ); ?>"/>
	<input type="hidden" name="height" id="height" value="<?php echo esc_attr( $height ); ?>"/>
	<input type="hidden" name="attachment_id" id="attachment_id" value="<?php echo esc_attr( $id ); ?>" />
	<input type="hidden" name="oitar" id="oitar" value="<?php echo esc_attr( $oitar ); ?>" />
	<?php wp_nonce_field( 'custom-header-crop-image' ) ?>
	<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Crop and Publish' ); ?>" />
	</p>
</form>
</div>
		<?php
  }

  function step_3() {
		check_admin_referer('custom-header-crop-image');
		if ( $_POST['oitar'] > 1 ) {
			$_POST['x1'] = $_POST['x1'] * $_POST['oitar'];
			$_POST['y1'] = $_POST['y1'] * $_POST['oitar'];
			$_POST['width'] = $_POST['width'] * $_POST['oitar'];
			$_POST['height'] = $_POST['height'] * $_POST['oitar'];
		}

		$original = get_attached_file( $_POST['attachment_id'] );

		$cropped = wp_crop_image($_POST['attachment_id'], $_POST['x1'], $_POST['y1'], $_POST['width'], $_POST['height'], HEADER_IMAGE_WIDTH, HEADER_IMAGE_HEIGHT);
		if ( is_wp_error( $cropped ) )
			wp_die( __( 'Image could not be processed.  Please go back and try again.' ), __( 'Image Processing Error' ) );

		$cropped = apply_filters('wp_create_file_in_uploads', $cropped, $_POST['attachment_id']); // For replication

		$parent = get_post($_POST['attachment_id']);
		$parent_url = $parent->guid;
		$url = str_replace(basename($parent_url), basename($cropped), $parent_url);

		// Construct the object array
		$object = array(
			'ID' => $_POST['attachment_id'],
			'post_title' => basename($cropped),
			'post_content' => $url,
			'post_mime_type' => 'image/jpeg',
			'guid' => $url
		);

		// Update the attachment
		wp_insert_attachment($object, $cropped);
		wp_update_attachment_metadata( $_POST['attachment_id'], wp_generate_attachment_metadata( $_POST['attachment_id'], $cropped ) );

		// set_theme_mod('header_image', $url);
    // hook into how we save the headers wp3 would set the single header here we set an array by pushing
    $available = get_theme_mod("available_headers");
    if (!is_array($available)) {
      $available = array();
    }
    array_push($available, $url);
    set_theme_mod('available_headers', $available);
    // end our hack

		// cleanup
		$medium = str_replace(basename($original), 'midsize-'.basename($original), $original);
		@unlink( apply_filters( 'wp_delete_file', $medium ) );
		@unlink( apply_filters( 'wp_delete_file', $original ) );
		return $this->finished();
  }

	function finished() {
		$this->updated = true;
    //header("Location: " . plugins_url(__FILE__) . "?step=1"); /* Redirect browser */
    //wp_redirect(get_option('siteurl').'/wp-admin/themes.php?page=rotating-header/rotating_header.php');
    $redir = get_option('siteurl').'/wp-admin/themes.php?page=' . __FILE__;
    // redirect headers might have already been sent, i believe it's in MU environment that this is true so
    // use a dirty hack js is wonderful
    ?>
      <script>
        window.location="<?php echo $redir ?>";
      </script>
    <?php
    die();
	}

	function step() {
		if ( ! isset( $_GET['step'] ) )
			return 1;

		$step = (int) $_GET['step'];
		if ( $step < 1 || 3 < $step )
			$step = 1;

		return $step;
	}

  function rotating_header_setup_page() {
		if ( ! current_user_can('edit_theme_options') )
			wp_die(__('You do not have permission to customize rotating headers.'));
		$step = $this->step();
		if ( 1 == $step )
			$this->step_1();
		elseif ( 2 == $step )
			$this->step_2();
		elseif ( 3 == $step )
			$this->step_3();
  }

  function post_type_taxonomies($post_type) {
  	global $wp_taxonomies;
  	$categories = array();
  	foreach($wp_taxonomies as $taxon) {
  		foreach($taxon->object_type as $j) {
  			if($j == $post_type && $taxon->hierarchical == true){
  				//$new_term_obj = array("name"=> $i->name, "terms" => get_terms($i->name));
  				array_push($categories, $taxon->name);
  			}
  		}
  	}
  	return $categories;
  }

  function taxonomy_values($taxonomy_type) {
  	$types = get_terms($taxonomy_type);
  	$select_options = array();
  	foreach($types as $i){
  		$select_options[$i->slug] = $i->name;
  	}
  	return $select_options;
  }
  
  function get_args($instance, $index) {
    
    $args = array(
      'post_type'=> 'page',
      'post__in' => array($instance["page_id_{$index}"])
    );
  
    $args["showposts"] = 1;
    return $args;
  }
  
  
  
  function print_tabs($instance, $num_slides) { ?>
<ul>
  <?php
    for ($j = 0; $j < $num_slides; $j++) {
      if($instance["cat_{$j}"]) {
  ?>
  <li<?if($j==0)echo ' class="selected"';?>>
    <span <?if($j==3)echo 'class="last"'?>><?php echo $instance["category_title_{$j}"]?></span>
  </li>
  <?php 	
      }
    }
    ?>
</ul>
  <?php
  }

	
	function add_dl_js() {
		wp_enqueue_script("dl", plugins_url("rotating-header/js/jquery-presenter.js"), "jquery", "0.5");
	}
}

function has_rotating_header() {
  $active = get_theme_mod("active_headers");
  return !empty($active);
}

function rotating_header_draw() {
  $height = HEADER_IMAGE_HEIGHT;

  $active = get_theme_mod("active_headers");
?>
  <div class="rotating-header" style="height:<?echo $height;?>px;width:<?echo HEADER_IMAGE_WIDTH?>px">
    <?php if (!empty($active)) { ?>
    <ul class="slides" style="height:<?echo $height;?>px;width:<?echo HEADER_IMAGE_WIDTH * count($active) ?>px;">
      <?php foreach ($active as $url) { ?>
        <li><a href="<?php echo esc_html($url['link']) ?>" title="<?php echo(esc_html(stripslashes($url['message']))) ?>"><img class="dlgraphic" width="<?php echo HEADER_IMAGE_WIDTH ?>" height="<?php echo HEADER_IMAGE_HEIGHT ?>" src="<?php echo $url['image'] ?>"/></a>
          <?php if ($url['message'] && $url['color']) { ?>
          <div class="dlcaption">
            <div class="caption" style="background:<?php echo $url['color'] ?>"></div>
            <div class="caption-text">
              <a href="<?php echo esc_html($url['link']) ?>"><?php echo stripslashes($url['message']) ?></a>
            </div>
          </div>
          <?php } ?>
        </li>
      <?php } ?>
    </ul>
    <?php } ?>
    <?php if (count($active) > 1) { ?>
    <ul class="dots">
      <?php for($i = 0; $i < count($active); $i++) { ?>
        <li <?php if ($i == 0){echo ' class="selected"'; } ?>><a href="#"><?php echo $i + 1; ?></a></li>
      <?php } ?>
    </ul>
    <?php } ?>
  </div>
  <script>
    jQuery(function($) {
      var options = { 
                      rotation_delay:parseInt(<?php echo stripslashes(get_option('rh_duration')); ?>),
                      rotation_duration:parseInt(<?php echo stripslashes(get_option('rh_transition')); ?>),
                      transition: "<?php echo stripslashes(get_option('rh_transition_type')); ?>"
                    };
      $(".rotating-header").dl(options);
    });
  </script>
<?php }

function create_rotating_header() {
  $rotating_header = new RotatingHeader();
}

$rotating_header = add_action( 'plugins_loaded', "create_rotating_header");
?>
