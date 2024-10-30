<?php
/*
Plugin Name: Malaysia Prayer Times
Plugin URI: http://www.envigeek.com/
Description: Widget that shows current or all prayer times for locations in Malaysia. Data by JAKIM.
Version: 1.1
Author: Envigeek Web Services
Author URI: http://www.envigeek.com/

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

require_once('location.php');

function mpt_enqueue() {
		wp_enqueue_script( 'jquery-mpt', plugins_url( 'jquery.mpt.js' , __FILE__ ), array( 'jquery' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'mpt_enqueue' );

function mpt_scripts(){
	echo "<script type='text/javascript'>";
	echo "/* <![CDATA[ */";
	echo "jQuery(document).ready(function(){";
		echo "jQuery('[id^=\'mpt\']').mpt();";
	echo "});";
	echo "/* ]]> */";
	echo "</script>";
}
add_action('wp_head', 'mpt_scripts');

function mpt_wpadmin_enqueue($hook) {
    if ( 'widgets.php' != $hook )
        return;
	wp_enqueue_script( 'select2', plugins_url( 'select2.min.js' , __FILE__ ), array( 'jquery' ), '3.4.6', true );
	wp_enqueue_style( 'select2', plugins_url( 'select2.css' , __FILE__ ), array(), '3.4.6' );
}
add_action( 'admin_enqueue_scripts', 'mpt_wpadmin_enqueue' );

function mpt_wpadmin_scripts(){
	echo "<script type='text/javascript'>";
	echo "/* <![CDATA[ */";
	echo "jQuery(document).on('click','.widgets-sortables [id*=\'mpt-widget\'] .widget-top',function(e){";
		echo "jQuery(this).parent().find('select.mptlcodes').select2();";
	echo "});";
	echo "jQuery(document).ajaxSuccess(function(e, xhr, settings){";
		echo "if( settings.data.search('action=save-widget') != -1 && settings.data.search('id_base=mpt') != -1 ) {";
			echo "var widget_id = new RegExp('[\\&]' + 'widget-id' + '=([^&#]*)').exec(settings.data);";
			echo "jQuery('#widget-' + widget_id[1] + '-location').select2();";
		echo "}";
	echo "});";
	echo "/* ]]> */";
	echo "</script>";
}
add_action('admin_head-widgets.php', 'mpt_wpadmin_scripts');

class MPT_Widget_Now extends WP_Widget {

	function MPT_Widget_Now() {
		$widget_ops = array( 'classname' => 'mpt', 'description' => __('Display current prayer time based on selected location/region in Malaysia.', 'mpt') );
		$control_ops = array( 'id_base' => 'mpt-widget-now' );
		$this->WP_Widget( 'mpt-widget-now', __('Current Prayer Time', 'mpt'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		// Widget output
		extract( $args );

		//Our variables from the widget settings.
		$title = apply_filters('widget_title', $instance['title'] );
		
		global $lcodes;
		$location = $instance['location'];
		$location_name = array_search($location, $lcodes);
		
		$textstring = $instance['textstring'];
		$prayertime = $instance['prayertime'];
		
		echo "<script type='text/javascript'>";
		echo "/* <![CDATA[ */";
		echo "jQuery(document).ready(function(){";
			echo "jQuery('#mpt".$this->id."').bind('prayerChanged', function(e) {";
				echo "var prayerNames = ['Subuh', 'Syuruk', 'Zohor', 'Asar', 'Maghrib', 'Isyak'];";
				echo "var time = e.currentPrayerTime.getHours() + ':' + e.currentPrayerTime.getMinutes();";
				echo "jQuery('#mpt".$this->id." .mpt-prayer').html(prayerNames[e.currentPrayer] + ' (' + time + ')');";
				echo "});";
			echo "jQuery('#mpt".$this->id."').mpt('getData', '".$location."');";
		echo "});";
		echo "/* ]]> */";
		echo "</script>";

		echo $before_widget;

		// Display the widget title 
		if ( $title )
			echo $before_title . $title . $after_title;
		?>
		<div id="mpt<?php echo $this->id; ?>" class="wp-mpt">
		<p>
			<?php if (!$prayertime) { ?><span class="mpt-prayer"></span><?php } ?>
			<span><?php _e(sprintf($textstring, $location_name), 'mpt'); ?></span>
			<?php if ($prayertime) { ?><span class="mpt-prayer"></span><?php } ?>
		</p>
		</div>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
		$instance = $old_instance;

		//Strip tags from title and name to remove HTML 
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['location'] = $new_instance['location'];
		$instance['textstring'] = $new_instance['textstring'];
		$instance['prayertime'] = $new_instance['prayertime'];

		return $instance;
	}

	function form( $instance ) {
		// Output admin widget options form
		// Set up some default widget settings.
		global $lcodes;
		$defaults = array(
			'title' => __('Current Prayer Time', 'mpt'),
			'location' => __('wlp-0', 'mpt'),
			'textstring' => __('Current Prayer in %s :', 'mpt'),
			'prayertime' => __(1, 'mpt'),
			);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'mpt'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'location' ); ?>"><?php _e('Location:', 'mpt'); ?></label>
			<select id="<?php echo $this->get_field_id( 'location' ); ?>" name="<?php echo $this->get_field_name( 'location' ); ?>" class="widefat mptlcodes">
				<?php
					$selected = $instance['location'];
					foreach ( $lcodes as $location => $lcode ) {
						if ( substr($lcode, -1) == "x" ) {
							echo "<optgroup label='".$location."'>";
						} else {
							if ( $lcode == $selected ) {
								echo "<option name='".$location."' value='".$lcode."' selected='selected'>".$location."</option>";
							} else {
								echo "<option name='".$location."' value='".$lcode."'>".$location."</option>";
							}
						}
					}
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'textstring' ); ?>"><?php _e('Text to appear:', 'mpt'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'textstring' ); ?>" name="<?php echo $this->get_field_name( 'textstring' ); ?>" value="<?php echo $instance['textstring']; ?>" class="widefat" />
			<small><?php _e('%s will be replace with the location name', 'mpt'); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'prayertime' ); ?>"><?php _e('Prayer time shown:', 'mpt'); ?></label>
			<select id="<?php echo $this->get_field_id( 'prayertime' ); ?>" name="<?php echo $this->get_field_name( 'prayertime' ); ?>">
				<?php
					$selected_prayertime = $instance['prayertime'];
					$prayertimes = array('Prepend','Append');
					foreach ( $prayertimes as $key => $prayertime ) {
						if ( $key == $selected_prayertime ) {
							echo "<option name='".$prayertime."' value='".$key."' selected='selected'>".$prayertime."</option>";
						} else {
							echo "<option name='".$prayertime."' value='".$key."'>".$prayertime."</option>";
						}
					}
				?>
			</select>
			<br/><small><?php _e('Choose to show prayer name and time before or after above text.', 'mpt'); ?></small>
			<br/><small><?php _e('Formatted in \'Subuh (HH:mm)\'', 'mpt'); ?></small>
		</p>
	<?php
	}
}


class MPT_Widget_All extends WP_Widget {

	function MPT_Widget_All() {
		$widget_ops = array( 'classname' => 'mpt', 'description' => __('Displays all prayer times based on selected location/region in Malaysia.', 'mpt') );
		$control_ops = array( 'id_base' => 'mpt-widget-all' );
		$this->WP_Widget( 'mpt-widget-all', __('All Prayer Times', 'mpt'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		// Widget output
		extract( $args );

		//Our variables from the widget settings.
		$title = apply_filters('widget_title', $instance['title'] );
		$location = $instance['location'];
		$orientation = isset( $instance['orientation'] ) ? $instance['orientation'] : false;
		
		echo "<script type='text/javascript'>";
		echo "/* <![CDATA[ */";
		echo "jQuery(document).ready(function(){jQuery('#mpt".$this->id."').mpt('getData', '".$location."')});";
		echo "/* ]]> */";
		echo "</script>";

		echo $before_widget;

		// Display the widget title 
		if ( $title )
			echo $before_title . $title . $after_title;

		if ( $orientation && $orientation == true ) {
			// Inline list
			?>
			<table id="mpt<?php echo $this->id; ?>" class="wp-mpt">
			  <tr>
				<td class="mpt-prayer-0-name">Subuh</td>
				<td class="mpt-prayer-1-name">Syuruk</td>
				<td class="mpt-prayer-2-name">Zohor</td>
				<td class="mpt-prayer-3-name">Asar</td>
				<td class="mpt-prayer-4-name">Maghrib</td>
				<td class="mpt-prayer-5-name">Isyak</td>
			  </tr>
			  <tr>    
				<td class="mpt-prayer-0-time">88:88</td>
				<td class="mpt-prayer-1-time">88:88</td>
				<td class="mpt-prayer-2-time">88:88</td>
				<td class="mpt-prayer-3-time">88:88</td>
				<td class="mpt-prayer-4-time">88:88</td>
				<td class="mpt-prayer-5-time">88:88</td>
			  </tr>             
			</table>
			<?php
		} else {
			// Table list
			?>
			<table id="mpt<?php echo $this->id; ?>" class="wp-mpt">
			  <tr class="mpt-prayer-0">
				<td class="name">Subuh</td>
				<td class="time">88:88</td>
			  </tr>
			  <tr class="mpt-prayer-1">
				<td class="name">Syuruk</td>
				<td class="time">88:88</td>
			  </tr>
			  <tr class="mpt-prayer-2">
				<td class="name">Zohor</td>
				<td class="time">88:88</td>
			  </tr>
			  <tr class="mpt-prayer-3">
				<td class="name">Asar</td>
				<td class="time">88:88</td>
			  </tr>
			  <tr class="mpt-prayer-4">
				<td class="name">Maghrib</td>
				<td class="time">88:88</td>
			  </tr>
			  <tr class="mpt-prayer-5">
				<td class="name">Isyak</td>
				<td class="time">88:88</td>
			  </tr>                
			</table>
			<?php
		}
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
		$instance = $old_instance;

		//Strip tags from title and name to remove HTML 
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['location'] = $new_instance['location'];
		$instance['orientation'] = (bool) $new_instance['orientation'];

		return $instance;
	}

	function form( $instance ) {
		// Output admin widget options form
		// Set up some default widget settings.
		global $lcodes;
		$defaults = array(
			'title' => __('All Prayer Times', 'mpt'),
			'location' => __('wlp-0', 'mpt'),
			'orientation' => isset( $instance['orientation'] ) ? (bool) $instance['orientation'] : false
			);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'mpt'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'location' ); ?>"><?php _e('Location:', 'mpt'); ?></label>
			<select id="<?php echo $this->get_field_id( 'location' ); ?>" name="<?php echo $this->get_field_name( 'location' ); ?>" class="widefat mptlcodes">
				<?php
					$selected = $instance['location'];
					foreach ( $lcodes as $location => $lcode ) {
						if ( substr($lcode, -1) == "x" ) {
							echo "<optgroup label='".$location."'>";
						} else {
							if ( $lcode == $selected ) {
								echo "<option name='".$location."' value='".$lcode."' selected='selected'>".$location."</option>";
							} else {
								echo "<option name='".$location."' value='".$lcode."'>".$location."</option>";
							}
						}
					}
				?>
			</select>
		</p>
		<p>
			<small><?php _e('By default shown in table list vertically. Select checkbox below to display inline.', 'mpt'); ?></small><br/>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['orientation'], true ); ?> id="<?php echo $this->get_field_id( 'orientation' ); ?>" name="<?php echo $this->get_field_name( 'orientation' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'orientation' ); ?>"><?php _e('Display as Inline List', 'mpt'); ?></label>
		</p>
	<?php
	}
}

function mpt_register_widget() {
	register_widget( 'MPT_Widget_Now' );
	register_widget( 'MPT_Widget_All' );
}
add_action( 'widgets_init', 'mpt_register_widget' );
	
?>