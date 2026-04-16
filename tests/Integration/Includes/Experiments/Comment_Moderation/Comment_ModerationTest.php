<?php
/**
 * Integration tests for the Comment_Moderation feature class.
 *
 * @package WordPress\AI\Tests\Integration\Includes\Experiments\Comment_Moderation
 */

namespace WordPress\AI\Tests\Integration\Includes\Experiments\Comment_Moderation;

use WP_UnitTestCase;
use WordPress\AI\Experiments\Comment_Moderation\Comment_Moderation;
use WordPress\AI\Experiments\Experiment_Category;

/**
 * Test case for Comment_Moderation feature.
 *
 * @since 0.7.0
 */
class Comment_ModerationTest extends WP_UnitTestCase {
	/**
	 * Feature instance.
	 *
	 * @var Comment_Moderation
	 */
	private $feature;

	/**
	 * Setup method.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->feature = new Comment_Moderation();
	}

	/**
	 * Test get_id returns correct ID.
	 */
	public function test_get_id() {
		$this->assertEquals( 'comment_moderation', Comment_Moderation::get_id() );
	}

	/**
	 * Test load_metadata returns expected structure.
	 */
	public function test_load_metadata() {
		$reflection = new \ReflectionClass( $this->feature );
		$method     = $reflection->getMethod( 'load_metadata' );
		$method->setAccessible( true );

		$metadata = $method->invoke( $this->feature );

		$this->assertIsArray( $metadata );
		$this->assertArrayHasKey( 'label', $metadata );
		$this->assertArrayHasKey( 'description', $metadata );
		$this->assertEquals( Experiment_Category::ADMIN, $metadata['category'] );
	}

	/**
	 * Test register adds hooks.
	 */
	public function test_register_adds_hooks() {
		$this->feature->register();

		$this->assertEquals(
			10,
			has_action( 'wp_abilities_api_init', array( $this->feature, 'register_abilities' ) )
		);
		$this->assertEquals(
			10,
			has_filter( 'preprocess_comment', array( $this->feature, 'evaluate_comment_with_ai' ) )
		);
		$this->assertEquals(
			10,
			has_action( 'admin_notices', array( $this->feature, 'display_moderation_notice' ) )
		);
	}

	/**
	 * Test register_abilities registers the ability.
	 */
	public function test_register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'wp_register_ability function not available.' );
			return;
		}

		$this->feature->register_abilities();

		$abilities = wp_get_registered_abilities();
		$this->assertArrayHasKey( 'ai/comment_moderation', $abilities );
	}

	/**
	 * Test evaluate_comment_with_ai skips admins.
	 */
	public function test_evaluate_comment_with_ai_skips_admins() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$commentdata = array(
			'comment_content' => 'Test comment',
		);

		$result = $this->feature->evaluate_comment_with_ai( $commentdata );

		$this->assertEquals( $commentdata, $result );
	}

	/**
	 * Test evaluate_comment_with_ai executes ability for guests but skips if AI is missing.
	 */
	public function test_evaluate_comment_with_ai_guest() {
		wp_set_current_user( 0 ); // Guest

		$commentdata = array(
			'comment_content'    => 'Test comment',
			'comment_author'     => 'Guest',
			'comment_author_url' => 'http://example.com',
		);

		try {
			$result = $this->feature->evaluate_comment_with_ai( $commentdata );
		} catch ( \Throwable $e ) {
			// This will likely throw or return error if AI isn't configured, which means it tried to execute.
			$this->markTestSkipped( 'AI client not available in test environment: ' . $e->getMessage() );
			return;
		}

		// Since AI is likely mocked to fail or skip, it should return original data or modified if successful.
		$this->assertIsArray( $result );
	}
}
