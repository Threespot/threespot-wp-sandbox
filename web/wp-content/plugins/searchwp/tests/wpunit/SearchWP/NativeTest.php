<?php
namespace SearchWP;

class NativeTest extends \Codeception\TestCase\WPTestCase {
	protected static $factory;
	protected static $post_type;
	protected static $post_ids;

	function _before() {
		self::$factory = static::factory();
		self::$post_type = 'post' . SEARCHWP_SEPARATOR . 'post';

		$post_ids[] = self::$factory->post->create( [
			'post_title' => 'SearchWP Native Test Title',
		] );

		add_post_meta( $post_ids[0], '_test_meta_key_1', 'swpnative' );

		$post_ids[] = self::$factory->post->create( [
			'post_title' => 'lorem amet',
		] );

		add_post_meta( $post_ids[1], '_test_meta_key_2', 'swpnative' );

		self::$post_ids = $post_ids;

		\SearchWP\Settings::update_engines_config( [
			'default' => [
				'sources'  => [
					'post.post' => [
						'attributes' => [
							'title'   => 300,
							'meta'  => [
								'_test_meta_key_1' => 5,
							],
						],
						'rules'      => [],
						'options'    => [],
					],
				],
			],
		] );

		foreach ( self::$post_ids as $post_id ) {
			\SearchWP::$index->add(
				new \SearchWP\Entry( self::$post_type, $post_id )
			);
		}
	}

	function _after() {
		$index = \SearchWP::$index;
		$index->reset();

		\SearchWP\Settings::update_engines_config( [] );
	}

	/**
	 * SearchWP results are returned for native search.
	 */
	public function test_native_search() {
		$this->go_to( site_url( '?s=swpnative' ) );
		$results = $GLOBALS['wp_query']->posts;

		$this->assertArrayHasKey( 'searchwp', $GLOBALS['wp_query']->query_vars );
		$this->assertEquals( 'default', $GLOBALS['wp_query']->query_vars['searchwp'] );

		$this->assertEquals( 1, count( $results ) );
		$this->assertArrayHasKey( 0, $results );

		$this->assertContains( intval( $results[0]->ID ), self::$post_ids );
	}
}
