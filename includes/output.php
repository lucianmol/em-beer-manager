<?php 
/*
Copyright (c) 2013, Erin Morelli. 

This program is free software; you can redistribute it and/or 
modify it under the terms of the GNU General Public License 
as published by the Free Software Foundation; either version 2 
of the License, or (at your option) any later version. 

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA. 
*
*
* EM Beer Manager shortcodes and template tags
*
*/
 

// Single Beer shortcode
function embm_single_beer($atts) {
   extract(shortcode_atts(array(
      'id' => 0,
      'show_profile' => 'true',
      'show_extras' => 'true',
   ), $atts));
   
   return embm_beer_single($id, $show_profile, $show_extras);
}
add_shortcode('beer', 'embm_single_beer');


// Single beer template code
function embm_beer_single($postid, $profile = 'true', $extras = 'true') {
	$args = array('id' => $postid, 'profile' => $profile, 'extras' => $extras);
	return embm_beer_single_output ($args);
}

// Single beer display
function embm_beer_single_output ($beer) {

	$bid = $beer['id'];
	$showprofile = $beer['profile'];
	$showextras = $beer['extras'];

	$output = '';
	
	// Set up new loop data
	global $post;
	$wp_query = new WP_Query(); 
	
	// The query
	$args = array (
		'post_type' => 'beer',
		'page_id' => $bid
	);
	
	$wp_query->query($args);
	
		
	while ($wp_query->have_posts()) : $wp_query->the_post();
	  
	  $output .= embm_display_beer($post->ID, $showprofile, $showextras);
		
	endwhile;

	wp_reset_query();
	wp_reset_postdata(); //reset
	
	return $output;
}



// Beer list shortcode
function embm_all_beers($atts) {
   extract(shortcode_atts(array(
      'exclude' => '',
      'show_profile' => 'true',
      'show_extras' => 'true',
      'style' => '',
      'beers_per_page' => -1,
   ), $atts));
   
   return embm_beer_list($exclude, $show_profile, $show_extras, $style, $beers_per_page);
}
add_shortcode('beer-list', 'embm_all_beers');


// Beer list template code
function embm_beer_list($exclude = '', $profile = 'true', $extras = 'true', $style = '', $pagenum = -1) {
	$args = array('exclude' => $exclude, 'profile' => $profile, 'extras' => $extras, 'style' => $style, 'page_num' => $pagenum);
	return embm_beer_list_output ($args);
}

// Beer list display
function embm_beer_list_output ($beers) {
	
	// Declared shortcode variables
	$excludes = explode(',', $beers['exclude']);
	$showprofile = $beers['profile'];
	$showextras = $beers['extras'];
	$showstyle = $beers['style'];
	$showpages = $beers['page_num']; 
	
	$output = '';	
	
	// Set up new loop data
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	global $post;
	$wp_query = new WP_Query(); 
	
	// The query
	$args = array (
		'post_type' => 'beer',
		'showposts' => $showpages,
		'paged' => $paged,
		
	);
	
	// Add styles filter
	if ($showstyle != '') {
		$style_slug = get_term_by('name', $showstyle, 'style', 'ARRAY_A');
		$args['style'] = $style_slug['slug'];
	}
	
	// Add id filter
	if ($excludes) {
		$args['post__not_in'] = $excludes;
	}

	$wp_query->query($args);
	
	$output .= '<div class="beer-list">'."\n";
		
	while ($wp_query->have_posts()) : $wp_query->the_post();
	  
	  $output .= embm_display_beer($post->ID, $showprofile, $showextras);
		
	endwhile;
		
	// Display navigation to next/previous pages when applicable
	$output .= '<div class="nav-below">'."\n";
	
		$big = 999999999; // need an unlikely integer
		$output .= paginate_links( array(
		  	'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
		  	'format' => '?paged=%#%',
		  	'current' => max( 1, get_query_var('paged') ),
		  	'total' => $wp_query->max_num_pages
		  	) );
	
	$output .= '</div>'."\n";
	$output .= '</div>'."\n";
	
	wp_reset_query();
	wp_reset_postdata(); //reset

	return $output;
	
}


// Generate HTML output
function embm_display_beer($beer_id, $showprofile='true', $showextras='true') {
	
  $output .= '<div id="beer-'.$beer_id.'" class="single-beer beer beer-'.$beer_id.'">'."\n";

	$output .= '<div class="beer-title">'."\n";
	
	if (is_page() || is_archive()) {
		$output .= '<a href="'.get_permalink($beer_id).'" titile="'.get_the_title($beer_id).'">';
		$output .= '<h2>'.get_the_title($beer_id).'</h2>';
		$output .= '</a>'."\n";
	} else {
		$output .= '<h1>'.get_the_title($beer_id).'</h1>'."\n";
	}
	$output .= '<span class="beer-style">(';
	$output .= '<a href="'.get_term_link(embm_get_beer_style($beer_id), 'style').'" title="View All '.embm_get_beer_style($beer_id).'s">';
	$output .= embm_get_beer_style($beer_id);
	$output .= '</a>)</span>'."\n";
	$output .= '</div>'."\n";
	
	if (!is_archive()) {
		if ( get_the_post_thumbnail($beer_id) != '' ) {
			$output .= '<div class="beer-image">'."\n";
			$output .= get_the_post_thumbnail($beer_id, 'full')."\n";
			$output .= '</div>'."\n";
		}
	}
	
	$output .= '<div class="beer-description">'."\n";
	
		$output .= get_the_content($beer_id);
		
		if (is_tax('style') || is_archive()) {
			$output .= ' <a class="read-more" href="'.get_permalink($beer_id).'">';
			$output .= __('More...', 'embm');
			$output .= '</a>';
		}
	
		$ut_option = get_option('embm_options');
		$use_untappd = $ut_option['embm_untappd_check'];
		
		if ($use_untappd != "1") {
			if ( (embm_get_beer($beer_id,'untappd') != '') ) {
				$output .= '<div class="untappd"><a href="'.embm_get_beer($beer_id,'untappd').'" target="_blank" title="Check In on Untappd"></a></div>'."\n";
			}
		}
	
	$output .= '</div>'."\n";
	
	if ( ($showprofile == 'true') || ($showextras == 'true') ) {
		
		$output .= '<div class="beer-meta">'."\n";
		
		if ($showprofile == 'true') {
			
			$output .= '<div class="beer-profile">'."\n";
			
			if (embm_get_beer($beer_id,'abv') != '') {
				$output .= '<div class="abv"><span class="label">';
				$output .= __('ABV:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'abv').'</span></div>'."\n";
			} 
			if (embm_get_beer($beer_id,'ibu') != '') {
				$output .= '<div class="ibu"><span class="label">';
				$output .= __('IBU:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'ibu').'</span></div>'."\n";
			} 
			if (embm_get_beer($beer_id,'malts') != '') {
				$output .= '<div class="malts"><span class="label">';
				$output .= __('Malts:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'malts').'</span></div>'."\n";
			}
			if (embm_get_beer($beer_id,'hops') != '') {
				$output .= '<div class="hops"><span class="label">';
				$output .= __('Hops:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'hops').'</span></div>'."\n";
			}
			if (embm_get_beer($beer_id,'adds') != '') {
				$output .= '<div class="other"><span class="label">';
				$output .= __('Other:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'adds').'</span></div>'."\n";
			}
			if (embm_get_beer($beer_id,'yeast') != '') {
				$output .= '<div class="yeast"><span class="label">';
				$output .= __('Yeast:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'yeast').'</span></div>'."\n";
			}
			
			$output .= '</div>'."\n";
			
		}
		
		if ($showextras == 'true') {
			
			$output .= '<div class="beer-extras">';
			
			if (embm_get_beer($beer_id,'avail') != '') {
				$output .= '<div class="avail"><span class="label">';
				$output .= __('Availability:', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'avail').'</span></div>'."\n";
			} 
			if (embm_get_beer($beer_id,'notes') != '') {
				$output .= '<div class="notes"><span class="label">';
				$output .= __('Additional Notes', 'embm');
				$output .= '</span><span class="value">'.embm_get_beer($beer_id,'notes').'</span></div>'."\n";
			}
			
			$output .= '</div>'."\n";
			
		}
					
		$output .= '</div>'."\n";
		
	} else {
		$output .= '<div class="embm-clear"></div>'."\n";
	}
	
 $output .= '</div>'."\n";
	
 return $output;	

}

// Redirect singe beer pages to plugin theme files 
function embm_single_beer_template($single_template) {
     global $post;

     if ($post->post_type == 'beer') {
          $single_template = EMBM_PLUGIN_DIR. '/templates/single-beer.php';
     } 
     
     return $single_template;
}

add_filter( "single_template", "embm_single_beer_template" ) ;


// Redirect beer archives to plugin theme files 
function embm_archive_beer_template($tax_template) {

     if (is_tax( 'style' )) {
          $archive_template = EMBM_PLUGIN_DIR. '/templates/archive-beer.php';
     } 
     
     return $archive_template_template;
}

add_filter( "taxonomy_template", "embm_archive_beer_template" ) ;


// Redirect style pages to plugin theme files 
function embm_tax_style_template($tax_template) {

     if (is_tax( 'style' )) {
          $tax_template = EMBM_PLUGIN_DIR. '/templates/taxonomy-style.php';
     } 
     
     return $tax_template;
}

add_filter( "taxonomy_template", "embm_tax_style_template" ) ;

?>