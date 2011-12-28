<?php
/**
 *	WP UI Posts handling.
 *	
 *	
 *		
 * @since $Id$
 * @package wp-ui
 * @subpackage class-wpui-post
 **/


/**
* WP UI Posts
*/
class wpuiPosts
{
	private $options, $direct, $instance;
	
	
	function __construct()
	{
		$this->wpuiPosts();
	}
	
	function wpuiPosts()
	{
		$this->options = get_option( 'wpUI_options' );
		if ( ! isset( $this->options ) ) return;
	}
	
	
	/**
	 * 	Replace the tags on the template.
	 * 	
	 * @since 0.7
	 * @return string $template HTML
	 */
	function replace_tags( $template, $needles ) {
		
		if ( ! $template ) return;
		if ( ! is_array( $needles ) ) return;
		
		$template = str_ireplace( '{$title}' , $needles[ 'title' ], $template );
		$template = str_ireplace( '{$thumbnail}' , $needles[ 'thumbnail' ], $template );
		$template = str_ireplace( '{$excerpt}' , $needles[ 'excerpt' ], $template );
		
		$template = str_ireplace( '{$content}' , $needles[ 'content' ], $template );
		if ( isset( $this->options[ 'relative_times' ] ) )
			$template = str_ireplace( '{$date}' , $this->get_relative_time($needles[ 'date' ]), $template );
		else
			$template = str_ireplace( '{$date}' , $needles[ 'date' ], $template );
		$template = str_ireplace( '{$url}' , $needles[ 'url' ], $template );		
		$template = str_ireplace( '{$author}' , $needles[ 'author' ], $template );
		$author_posts_link = '<a href="' . get_author_posts_url( $needles[ 'author' ] ) . '" target="_blank" />' . $needles[ 'author' ] . '</a>';
		$template = str_ireplace( '{$author_posts_link}', $author_posts_link, $template );
		$first_cat = explode( ', ', $needles[ 'meta' ]['cat'] );
		$first_cat = $first_cat[ 0 ];
		
		$template = str_ireplace( '{$cats}', $needles[ 'meta' ][ 'cat' ], $template );
		$template = str_ireplace( '{$cat}', $first_cat,  $template );
		$template = str_ireplace( '{$tags}', $needles[ 'meta' ][ 'tag' ], $template );
		$template = str_ireplace( '{$num_comments}', $needles[ 'meta' ][ 'comments' ], $template );
	
		return $template;		
	} // END method replace_tags



	
	/**
	 * Generate excerpt.
	 * 
	 * @since 0.7
	 * 
	 * @param string $text to be trimmed.
	 * @param integer $length to trim.
	 * @return string $text trimmed content
	 */
	function get_excerpt( $text, $length ) {
		$text = apply_filters( 'the_content' , $text );
		$text = str_replace( '\]\]\>', ']]&gt;', $text );
		$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );
		$text = strip_tags( $text, '<p><ul><ol><li><img/><h2><h3>' );
		
		if ( ! is_int( $length ) ) {
			if ( isset( $this->options[ 'excerpt_length' ] ) )
				$length = $this->options[ 'excerpt_length' ];
			else 
				$length = 55;
		}
		
		
		// $words = explode( ' ' , $text , $length + 1 );
		
		$words = preg_split( '/ /', $text, $length + 1 );

		// if ( ! stristr( $words[ $length - 1 ], '.' ) ) {
			// $lastC = $length - 1;
			// $words[ $lastC  ] = preg_replace( '/([^\.]\.).+/sim' , '$1', $words[ $lastC ] );
			// array_splice( $words, $lastC );
		// }
		
		$words_limit = count($words);
		
		$words[ $words_limit -1 ] = preg_replace( '/([^\.]\.\s).+/sim' , '$1', $words[ $words_limit - 1] );
	
		// 
		// echo '<pre>';
		// print_r($words);
		// echo '</pre>';		

		if ( count( $words ) > $length ) {
			// array_pop( $words );
			// array_push( $words , $more_link );
			$text = implode( ' ', $words );
		}		
		return $text;
	} // END method wpui_generate_excerpt
	

	/**
	 * Get individual posts.
	 * 
	 * @since 0.7
	 * @uses get_post()
	 * @param $ID , ID of the post
	 * @param $args array.
	 */
	function wpui_get_post( $ID , $length , $type='post' ) {
		if ( ! $ID ) return;
		if ( ! $length  && isset( $this->options[ 'excerpt_length' ] ) )
			$length = $this->option[ 'excerpt_length' ];
			
		if ( $type == 'page' )			
			$length = 55;
		
		if ( $type == 'page' ) {
			if ( is_numeric( $ID ) ) 
				$wpui_post = get_page( $ID );
			else
				$wpui_post = get_page_by_title( $ID );
				
		} else {
			$wpui_post = get_post( $ID );
		}
		
		if ( ! $wpui_post ) {
			return "Please verify the post/page ID. Check the spelling if using a name or title.";
		}


		$p_title = $wpui_post->post_title;
		$p_thumb = get_the_post_thumbnail( $ID );				

		$more_link = get_permalink( $ID );
		$check_more = preg_match( '/<!--more-->/im', $wpui_post->post_content);
		// die();

		if ( $length == 'more' && $check_more ) {
			$pos = stripos( $wpui_post->post_content , '<!--more-->' );
			$post_exc = substr( $wpui_post->post_content, 0 , $pos);
		} else {
			$length = intval( $length );
			$post_exc = $wpui_post->post_content;
			$post_exc = $this->get_excerpt( $post_exc, $length );
		}
				
		$post_date = mysql2date( get_option( 'date_format' ) , $wpui_post->post_date_gmt);
		
		if ( $type != 'page' ) {
			$cats = get_the_category_list( ', ', '', $wpui_post->ID );
			$tags = $this->wpui_get_post_tags( $wpui_post->ID );
		} else {
			$cats = $tags = '';
		}
			
		
		$output = array(
			'title'		=>	$wpui_post->post_title,
			'excerpt'	=>	$post_exc,
			'content'	=>	$wpui_post->post_content,
			'thumbnail'	=>	$p_thumb,
			'date'		=>	$post_date,
			'author'	=>	get_the_author_meta('display_name' ,$wpui_post->post_author),
			'url'		=>	$more_link,
			'meta'		=>	array(
								'cat'		=>	$cats,
								'tag'		=>	$tags,
								'comments'	=>	$wpui_post->comment_count
							)
			);
		
		return $output;
		
	} // END method wpui_get_post.
	
	
	
	/**
	 * 	Get multiple posts with custom query.
	 * 
	 * 	@since 0.7
	 * 	@uses WP_Query, wp_reset_postdata()
	 * 	@return array $posts 
	 */
	function wpui_get_posts( $args='' ) {
		
		$defaults = array(
			'get'			=>	'',
			'cat'			=>	'',
			'category_name'	=>	'',
			'tag'			=>	'',
			'tag_name'		=>	'',
			'post_type'		=>	'post',
			'post_status'	=>	'publish',
			'number'		=>	'4',
			'offset'		=>	false,
			'search'		=>	'',
			'exclude'		=>	'',
			'length'		=>	'more',
			'excerpt'		=>	true,
			'thumbnail'		=>	true,
			'meta'			=>	true,
			'related'		=>	'category',
		);
		
		$r = wp_parse_args( $args, $defaults );
			

		$qquery = array();
		
		/**
		 * 	Preference - Get > Cat > Tag
		 */
		if ( $r[ 'get' ] != '' ) {
			// Get recent posts
			if ( $r['get'] == 'recent' ) {
			} else if ( $r[ 'get' ] == 'popular' ) {
				$qquery = array( 'orderby' => 'comment_count' ); 
			} elseif ( $r[ 'get' ] == 'random' ) {
				$qquery = array( 'orderby' => 'rand' );
			} elseif ( $r[ 'get' ] == 'related' ) {
				global $post;
				
				if ( $r[ 'related' ] == 'tags' ) { 
				$rtags = wp_get_post_tags( $post->ID );
					if ( $rtags ) {
						$rels = array();
						foreach( $rtags as $tags ) $rels[] = $tags->term_id;
						$qquery[ 'tag__not_in' ] = $rels;
					}
				} else {
					$rcats = get_the_category( $post->ID );
					if ( $rcats ) {
						$rels = array();
						foreach( $rcats as $cats ) $rels = $cats->term_id;
						$qquery[ 'category__in' ] = $rels;
					}
				}
			}
			if ( $r[ 'exclude' ] != '' ) {
				$excl_array = explode( ',' , $r[ 'exclude' ] );
				if ( is_array( $excl_array ) )
				$qquery[ 'post__not_in' ] = $excl_array;
			}
		} else {
		/**
		 * Categories and tags.
		 */	
		// Cats
		if ( $r[ 'cat' ] != '' || $r[ 'category_name' ] != '' ) {
			$r[ 'tag' ] = ''; 

			if ( $r[ 'category_name' ] != '' )
				$qquery[ 'category_name' ] = $r[ 'category_name' ];
			else 
				$qquery[ 'cat' ] = $r[ 'cat' ];
				
			if ( $r[ 'exclude' ] != '' ) {
				$excl_array = explode( ',' , $r[ 'exclude' ] );
				if ( is_array( $excl_array ) )
				$qquery[ 'category__not_in' ] = $excl_array;
			}
				
		}
		// Tags
		if ( $r[ 'tag' ] != '' || $r[ 'tag_name' ] != '' ) {
			
			if ( $r[ 'tag_name' ] != '' ) {
				$qquery[ 'tag' ] = $r[ 'tag_name' ];				
			} else {
				$tags_arr = explode( ',', $r[ 'tag' ] );
				$qquery[ 'tag__in' ] = $tags_arr;				
			}	
			
			if ( $r[ 'exclude' ] != '' ) {
				$excl_array = explode(',', $r[ 'exclude' ] );
				if ( is_array( $excl_array ) )
				$qquery[ 'tag__not_in' ] = $excl_array;		
			}
		
		}
		}

		$qquery[ 'posts_per_page' ] = $r['number'];
		
		if ( $r[ 'post_type' ] != '' ) 
		$qquery[ 'post_type' ] = $r[ 'post_type' ];
		
		if ( $r['post_status' ] != 'publish' )
		$qquery[ 'post_status' ] = $r[ 'post_status' ];
					
		$get_posts = new WP_Query( $qquery );
				
		$post_count = 0;
		$post_basket = array();
		
		while ( $get_posts->have_posts() ) : $get_posts->the_post();
		
		
		$wost = array();
		
		$content = get_the_content();
		
		$wost['title'] = get_the_title();
		// $wost[ 'thumbnail' ] = ( function_exists( 'get_the_post_thumbnail' ) ) ? get_the_post_thumbnail( get_the_ID() ) : '';		 
		$wost[ 'thumbnail' ] = $this->get_thumbnail( get_the_ID() );
		$wost['content'] = $content;
		$check_more = preg_match( '/<!--more-->/im', $wost['content']);

		// if ( $r[ 'length' ] == 'more' && $check_more ) {
		// 	$pos = stripos( $content , '<!--more-->' );
		// 	$wost[ 'excerpt' ] = substr( $content, 0 , $pos);
		// } else {
		if ( $r[ 'length' ] == 'more' )	{
			$wost['excerpt'] = get_the_excerpt();
		} else {
			$elength = intval( $r[ 'length' ] );
			if ( ! is_int( $elength ) ) $elength = 55;
			$wost[ 'excerpt' ] = $this->get_excerpt( $content, $elength );
		}
		
		$wost[ 'meta' ] = array();
		$wost[ 'meta' ][ 'cat' ] = get_the_category_list( ',' );			
		$wost[ 'meta' ][ 'tag' ] = 	get_the_tag_list('', ', ', '');
		$wost[ 'meta' ][ 'comments' ] = get_comments_number();
		
		// }
		$wost[ 'date' ] = get_the_date();
		$wost[ 'url' ] = get_permalink( get_the_ID() );
		$wost[ 'author' ] = get_the_author();
		// $post_basket

		$post_basket[ $post_count ] = $wost;


		$post_count++;
		endwhile; // end while get_posts loop
		
		wp_reset_postdata();


		if ( $post_basket )
			return $post_basket;
	} // END function wpui_get_posts
	
	
	/**
	 * Get the relative time e.g, 5 days ago.
	 * 
	 * @since 0.5.8
	 * @uses mysql2date, human_time_diff
	 * @param integer $time, post time in GMT.
	 * @return string, relative time
	 */
	function get_relative_time( $time )
	{
		$time = mysql2date( 'U' , $time );
		$time = human_time_diff( $time, current_time( 'timestamp' ) ) . __( ' ago', 'wp-ui' );
		return $time;
	} // END function get_relative_time
	
	
	/**
	 * Get the tags from a post ID.
	 * 
	 * @uses get_the_tags()
	 * @since 0.7
	 * @param $ID id of the post.
	 * @param $separator string 
	 * @return $output long string of tags.
	 */
	function wpui_get_post_tags( $pID='0' , $sep=', ' )
	{		
		$get_tags = get_the_tags( $pID );
		
		$output = '';
		$total_tags = count( $get_tags );
		$present_tag = 1;
		
		if ( ! $get_tags ) return;
		
		foreach( $get_tags as $tag ) {
			if ( $present_tag == $total_tags ) $sep = '';
			$output .= '<a href="' . get_tag_link( $tag->term_id ) . '">' . $tag->name . '</a>' . $sep;
			$present_tag++;
		}		
		return $output;		
	} // END function wpui_get_post_tags
	
	
	function sc_wpuifeeds( $atts, $content = null ) {
		extract( shortcode_atts( array( 
				'url'			=>	'',
				'number'		=>	3,
				'style'			=>	$this->options[ 'tab_scheme' ],
				'type'			=>	'tabs',
				'mode'			=>	'',
				'listwidth'		=>	'',
				'tab_names'		=>	'title',
				'effect'		=>	$this->options['tabsfx'],
				'speed'			=>	'600',
				'number'		=>	'4',
				'rotate'		=>	'',
				'elength'		=>	$this->options['excerpt_length'],
				'before_post'	=>	'',
				'after_post'	=>	'',
				'template'		=>	'1'
			), $atts));
			
			if ( ! $url )
				return __( 'WP-UI feeds shortcodes needs a valid RSS URL to work.' , 'wp-ui' );
				
			$results = $this->wpui_get_feeds( array(
				'url'		=>	$url,
				'elength'	=>	$elength,
				'number'	=>	$number				
			));

		$tab_names_arr = preg_split( '/\s?,\s?/i', $tab_names );

		$output = '';

		$output_s = '';

		$tmpl = ( isset( $this->options[ 'post_template_' . $template ] ) ) ?
					$this->options[ 'post_template_' . $template ] :
					$this->options[ 'post_template_1' ];

		foreach( $results as $index=>$item ) {
			$tab_num = $index+ 1;
			
			if ( $tab_names == 'title' ) {
				$tab_name = $item[ 'title' ];
			} elseif ( isset( $tab_names_arr ) && count( $tab_names_arr ) > 1 ) {
				$tab_name = $tab_names_arr[ $index ];
			} else {
				$tab_name = $tab_num;
			}
				
			$tabs_content = $this->replace_tags( $tmpl , $item );
			$output_s .= do_shortcode( '[wptabtitle]' . $tab_name. '[/wptabtitle] [wptabcontent] ' . $tabs_content . 	' [/wptabcontent]' );
			
		}		
		
		$wptabsargs = '';
		
		if ( $type != '' )
			$wptabsargs .= ' type="' . $type . '"';
		if ( $mode != '' )
			$wptabsargs .= ' mode="' . $mode . '"';
		
		if ( $listwidth != '' )
			$style .= ' listwidth-' . $listwidth;
		$wptabsargs .= ' style="' . $style . '"';
		if ( $rotate && $rotate != '' )
			$wptabsargs .= ' rotate="' . $rotate . '"';
		
		if ( $listwidth != '' )
			$style .= ' listwidth-' . $listwidth;
			
		$output .= do_shortcode( '[wptabs' . $wptabsargs . '] ' . $output_s  . ' [/wptabs]' );
		
		return $output;
			
	} // END function sc_wptabcontent

	
	/**
	 * Get a feed and output the array.
	 */
	function wpui_get_feeds( $args='' )
	{
		$defaults = array(
			'url'		=>	'',
			'number'	=>	5,
			'length'	=>	55
		);
		
		$r = wp_parse_args( $args, $defaults );
		
		$feed = fetch_feed( $r[ 'url' ] );

		if ( is_wp_error( $feed ) )
			return false;
			
		$number = $feed->get_item_quantity( $r[ 'number' ] );

		$items = $feed->get_items( 0, $number );

		$lists = array();
		$itera = 0;

		$format_opt = get_option( 'date_format' );
		$date_format = ( $format_opt ) ? $format_opt : 'l, F jS, Y';

	
		foreach( $items as $item ) {
			$thisArr[ 'title' ] = $item->get_title();
			$thisArr['url'] = $item->get_permalink();
			$thisArr[ 'date' ] = $item->get_date( $date_format );
			$thisArrAuth = $item->get_author();
			$thisArr[ 'author' ] = $thisArrAuth->get_name();

			$thisArr[ 'content' ] = $item->get_content();
			
			if ( strlen( $thisArr[ 'content' ] ) > intval( $r[ 'length' ] ) )
				$thisArr[ 'excerpt' ] = $this->get_excerpt( $thisArr[ 'content' ], $r[ 'length' ] );
			else
				$thisArr[ 'excerpt' ] = $thisArr[ 'content' ];
				
			$thisArr[ 'thumbnail' ] = $this->get_image_from_Content( $thisArr[ 'content' ] );
	
			$thisArr[ 'meta' ][ 'cat' ]  = '';
			
			$thisCat = $item->get_category();
			if ( $thisCat )
			$thisArr[ 'meta' ][ 'cat' ]  = $thisCat->get_term();
			$thisArr[ 'meta' ][ 'tag' ]  = '';
			$thisArr[ 'meta' ][ 'comments' ]  = '';
			
			$lists[ $itera ] = $thisArr;
			$itera++;
		}
		
		return $lists;
	} // END function wpui_get_feeds
	
	
	function get_thumbnail( $ID ) {
		$cache = ( isset( $this->options['enable_cache' ] ) && isset( $this->options[ 'enable_cache' ] ) ) ? true : false;
		
		$width = ( isset( $this->options['post_default_thumbnail' ] ) &&
		 	( $this->options[ 'post_default_thumbnail' ][ 'width' ] !== '' ) ) ?
		 $this->options[ 'post_default_thumbnail']['width'] : 100;
		
		$height = ( isset( $this->options['post_default_thumbnail' ] ) &&
		 	( $this->options[ 'post_default_thumbnail' ][ 'height' ] !== '' ) ) ?
		 $this->options[ 'post_default_thumbnail']['height'] : 100;
		
		if ( function_exists( 'has_post_thumbnail' ) &&
			has_post_thumbnail( $ID ) ) {
			$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $ID ), 'full' );
			if ( $cache )
				$thumb_src = get_bloginfo( 'url' ) . '?wpui-image=' . $thumbnail[0] . '&width=100&height=100';
			else $thumb_src = $thumbnail[ 0 ];
		
					
		} else {
			if ( isset( $this->options[ 'post_default_thumbnail' ] ) && $this->options[ 'post_default_thumbnail' ][ 'url' ] != '' )
				$thumb_url = $this->options['post_default_thumbnail']['url'];
			else return '';
				// $thumb_url = wpui_url( 'images/wp-light.png' );
			
			if ( $cache )
			$thumb_src = get_bloginfo( 'url' ) . '?wpui-image=' . $thumb_url .  '&width=100&height=100';
			else return '';
						
		}
		$title = get_the_title( $ID );
		
		return '<img src="' . $thumb_src . '" width="' . $width . '" height="' . $height . '" alt="' . $title . '" />';
	}
	
	/**
	 * Get image from a block of content.
	 */
	function get_image_from_content( $content )
	{
		if ( ! $content ) return;
		
		$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
		preg_match( '/<img[^>]+\>/i', $content, $matches );
		if ( empty( $matches ) && ! count( $matches ) > 0 )
			return;
		
		$thumb_width = get_option( 'thumbnail_size_w' );
		$thumb_height = get_option( 'thumbnail_size_h' );
		$image = $matches[ 0 ];
		
		$image = preg_replace( '|width="([^"]*)"|' , 'width="' . $thumb_width . '"', $image );
		$image = preg_replace( '|height="([^"]*)"|' , 'height="' . $thumb_height . '"', $image );
		

		return $image;
	} // END function get_image_from_content


	function put_related_posts( $content ) {
		global $post, $wp_query;
		
		static $sinstance = 0;
		if ( ( $sinstance != 0 ) || ( ! is_singular() ) || ( $post->ID !== $wp_query->post->ID ) ) {
			return $content;
		} else {
			$sinstance++;
		}
			
		// echo $post->ID . ':::::::::::' . $wp_query->post->ID . '<br />';
		
		// $content .= do_shortcode( '[wpui_related_posts]' );
		$content .= '[wpui_related_posts]';

		return $content;
	} // END get_related_posts;
	
	
	function insert_related_posts( $atts, $content = null) {
		extract(shortcode_atts(array(
			'type'		=>	'popular',
			'number'	=>	4,
			'per_row'	=>	4,
			'title'		=>	'We recommend',
			'singular'	=>	'true'
		), $atts));
		
		if ( ! is_singular() && $singular == 'false' ) return $content;
		global $post;
				
		$pst_id = $post->ID;		
		
		$pst_wid = ( isset( $this->options ) && $this->options[ 'post_widget'] ) ?
						$this->options[ 'post_widget' ] :
						array( 'title' => $title, 'number' => $number, 'per_row' => $per_row, 'type' => $type );
			
			
			$num =  $pst_wid[ 'number' ];
			$get = $pst_wid[ 'type' ];
			
			$rel_opts = array();
			$rel_opts['get'] = $get;
			$rel_opts['number'] = ( $get != 'related' ) ? $num - 1 : $num;
			$rel_opts[ 'exclude' ] = $pst_id;
			
			$rel_posts = $this->wpui_get_posts( $rel_opts );
			// echo '<pre>';
			// 	var_export($rel_posts);
			// 	echo '</pre>';


			$output = '';
			
			$per_row = $pst_wid[ 'per_row' ];
			
			if ( $pst_wid['title' ] != '' )
			$output .= '<h3 class="wpui-related-posts-title">' . $pst_wid[ 'title' ] . '</h3>';
					
			if ( is_array( $rel_posts ) ) {
				$output .= '<ul class="wpui-related-posts wpui-per_' . $per_row . '">';
				foreach( $rel_posts as $rel=>$rpost ) {
					$classs = '';
		
					$classs .= ( ( ($rel + 1 ) % $per_row ) == 0 ) ? ' row-last' : '';
					$classs .= ( ( ($rel ) % $per_row) == 0 ) ? ' row-first' : '';
		
					$output .= "<li class='$classs'>";
					if ( $rpost[ 'thumbnail' ] != '' )
					$output .= '<div class="wpui-rel-post-thumbnail">';
					$output .= '<a href="' . $rpost[ 'url' ] . '" title="' . $rpost[ 'title' ] . '">';
					$output .= $rpost[ 'thumbnail' ];
					$output .= '</a>';
					$output .='</div>';
					$output .= '<div class="wpui-rel-post-meta"><span class="wpui-rel-post-title">' . $rpost[ 'title' ] . '</span>';
					if ( $per_row == 2 )
					$output .= '<span class="wpui-rel-post-author">' . $rpost['author'] .'</span><span class="wpui-rel-post-time">' . $rpost['date'] . '</span>';
					$output .= '</div>';
					$output .= '</li>';
				}	
				$output .= '</ul>';			
			}
			
			// $content = apply_filters( 'the_content', $content . $output );
				
			return $output;	
				
			// return apply_filters( 'the_content', $content );
	}
	
	

} // end class wpuiPosts;




// add_action( 'init', 'wpui_add_post_type' );
// 
// function wpui_add_post_type() {
// 	register_post_type( 'wp_ui', array( 
// 			'labels'	=>	array(
// 				'name'	=>	'Slides',
// 				'singular_name'	=>	'Slide'
// 			),
// 			'public'	=>	true,
// 			'has_archive'	=>	true
// 		
// 		));
// 	
// }



?>