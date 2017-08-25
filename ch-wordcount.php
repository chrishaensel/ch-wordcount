<?php


/*
Plugin Name: CH Easy Word Count
Plugin URI: https://www.chaensel.de/easy-word-count/
Description: Displays the word count of posts and pages in the overview tables. Also adds a dashboard widget.
Author: Christian Hänsel
Version: 1.0
Author URI: https://chaensel.de
Text Domain: ch-wordcount
License:     GPLv2

CH Easy Word Count is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

CH Easy Word Count is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CH Easy Word Count.
*/

class ChWordcount {

	public $total_word_count = 0;
	public $displayType      = null;
	public $chApiUrl         = "http://wc.chaensel.de/index.php";

	public function __construct() {
		add_filter( 'manage_posts_columns', array( $this, 'add_wordcount_column' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_wordcount_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'ch_columns_content' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'ch_columns_content' ), 10, 2 );
		add_action( 'load-edit.php', array( $this, 'display_total_word_count' ), 1500 );
		add_action( 'wp_dashboard_setup', array( $this, 'ch_custom_dashboard_widgets' ) );
	}

	/**
	 * Adding the word count column
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_wordcount_column( $columns ) {
		return array_merge( $columns,
			array( 'wordcount' => __( 'Words', 'ch-wordcount' ) ) );
	}

	/**
	 * Echo out the word count
	 */
	public function ch_post_word_count() {
		global $post;

		return str_word_count( strip_tags( $post->post_content ), 0, "" );
	}


	/**
	 * Display the word count in the table rows
	 *
	 * @param $column_name
	 * @param $post_ID
	 */
	public function ch_columns_content( $column_name, $post_ID ) {
		if ( $column_name == 'wordcount' ) {
			$wordcount = $this->ch_post_word_count( $post_ID );
			if ( $wordcount ) {
				$this->total_word_count = $this->total_word_count + $wordcount;
				echo $wordcount;
			}
		}
	}

	/**
	 * Display a box with the total word count on the posts and pages list pages
	 */
	public function display_total_word_count() {
		$screen = get_current_screen();

		$this->displayType = "Posts";
		switch ( $screen->id ) {
			case "edit-post":
			default:
				$this->displayType = "Posts";
				break;

			case "edit-page":
				$this->displayType = "Pages";
				break;
		}

		if ( 'edit-post' === $screen->id || 'edit-page' === $screen->id ) {
			add_action( 'in_admin_footer', function () {
				echo '
				<div class="postbox" id="total_word_count" style="padding: 5px 10px">	
				        Total Word Count in ' . $this->displayType . ': ' . $this->total_word_count . '
				</div>
				';
			} );
		}
	}

	/**
	 * Getting the sum of all words by content type
	 *
	 * @param null $type
	 *
	 * @return stdClass
	 */
	public function getAllWordCountByType( $type = null ) {
		if ( is_null( $type ) ) {
			$type = "post";
		}

		$ret            = new stdClass();
		$ret->wordcount = 0;
		$ret->itemcount = 0;

		$wpb_all_query = new WP_Query( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => - 1 ) );

		if ( $wpb_all_query->have_posts() ) :

			while ( $wpb_all_query->have_posts() ) :
				$wpb_all_query->the_post();
				$content        = get_the_content();
				$ret->wordcount += str_word_count( strip_tags( $content ), 0, "" );
				$ret->itemcount ++;
			endwhile;
		endif;

		return $ret;
	}

	/********************
	 * DASHBOARD WIDGET
	 ********************/


	/**
	 * Add the dashboard widget
	 */
	public function ch_custom_dashboard_widgets() {
		global $wp_meta_boxes;

		wp_add_dashboard_widget( 'custom_help_widget', 'CH Easy Word Count', array( $this, 'ch_wordcount_dashboard_content' ) );
	}

	/**
	 * The dashboard widget content
	 */
	public function ch_wordcount_dashboard_content() {
		$posts_data = $this->getAllWordCountByType( "post" );
		$pages_data = $this->getAllWordCountByType( "page" );
		$posts_wc   = $posts_data->wordcount;
		$pages_wc   = $pages_data->wordcount;

		$posts_count = $posts_data->itemcount;
		$pages_count = $pages_data->itemcount;

		echo 'Posts: ' . $posts_wc . " words in " . $posts_count . " posts<br>";
		echo 'Pages: ' . $pages_wc . " words in " . $pages_count . " pages<br>";

		echo '<div style="margin-top:15px">Support at <a href="https://www.chaensel.de/easy-word-count/" target="_blank">chaensel.de</a></div>';
	}

}


$ch = new ChWordcount();

