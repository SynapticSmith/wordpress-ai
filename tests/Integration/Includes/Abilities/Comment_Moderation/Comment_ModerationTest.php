<?php
/**
 * Integration tests for the Comment_Moderation Ability class.
 *
 * @package WordPress\AI\Tests\Integration\Includes\Abilities\Comment_Moderation
 */

namespace WordPress\AI\Tests\Integration\Includes\Abilities\Comment_Moderation;

use WP_Error;
use WP_UnitTestCase;
use WordPress\AI\Abilities\Comment_Moderation\Comment_Moderation;

/**
 * Test case for Comment_Moderation Ability.
 *
 * @since 0.7.0
 */
class Comment_ModerationTest extends WP_UnitTestCase {
	/**
	 * Ability instance.
	 *
	 * @var Comment_Moderation
	 */
	private $ability;

	/**
	 * Setup method.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ability = new Comment_Moderation(
			'ai/comment_moderation',
			array(
				'label'       => 'Comment Moderation',
				'description' => 'Comment Moderation',
			)
		);
	}

	/**
	 * Test input_schema returns correct structure.
	 */
	public function test_input_schema_returns_correct_structure() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'input_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( $this->ability );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'comment_text', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['comment_text']['type'] );
		$this->assertArrayHasKey( 'author_name', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['author_name']['type'] );
		$this->assertArrayHasKey( 'author_url', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['author_url']['type'] );
		$this->assertContains( 'comment_text', $schema['required'] );
	}

	/**
	 * Test output_schema returns correct structure.
	 */
	public function test_output_schema_returns_correct_structure() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'output_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( $this->ability );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'is_spam', $schema['properties'] );
		$this->assertEquals( 'boolean', $schema['properties']['is_spam']['type'] );
		$this->assertArrayHasKey( 'toxicity_score', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['toxicity_score']['type'] );
		$this->assertArrayHasKey( 'reason', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['reason']['type'] );
		$this->assertArrayHasKey( 'recommendation', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['recommendation']['type'] );
		$this->assertContains( 'approve', $schema['properties']['recommendation']['enum'] );
		$this->assertContains( 'is_spam', $schema['required'] );
		$this->assertContains( 'toxicity_score', $schema['required'] );
		$this->assertContains( 'reason', $schema['required'] );
		$this->assertContains( 'recommendation', $schema['required'] );
	}

	/**
	 * Test permission_callback allows any user.
	 */
	public function test_permission_callback_allows_any_user() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'permission_callback' );
		$method->setAccessible( true );

		wp_set_current_user( 0 ); // Guest user

		$result = $method->invoke( $this->ability, array() );
		$this->assertTrue( $result );
	}

	/**
	 * Test execute_callback rejects empty comment text.
	 */
	public function test_execute_callback_rejects_empty_comment_text() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_callback' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, array() );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_comment_text', $result->get_error_code() );
	}

	/**
	 * Test execute_callback handles execution skip if no AI client.
	 */
	public function test_execute_callback_with_valid_input() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_callback' );
		$method->setAccessible( true );

		$input = array(
			'comment_text' => 'This is a test comment.',
			'author_name'  => 'Test User',
			'author_url'   => 'https://example.com',
		);

		try {
			$result = $method->invoke( $this->ability, $input );
		} catch ( \Throwable $e ) {
			$this->markTestSkipped( 'AI client not available in test environment: ' . $e->getMessage() );
			return;
		}

		if ( is_wp_error( $result ) ) {
			$this->markTestSkipped( 'AI client not available in test environment: ' . $result->get_error_message() );
			return;
		}

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertArrayHasKey( 'is_spam', $result, 'Result should have is_spam key' );
	}

	/**
	 * Test meta() returns empty array.
	 */
	public function test_meta_returns_empty_array() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'meta' );
		$method->setAccessible( true );

		$meta = $method->invoke( $this->ability );
		$this->assertIsArray( $meta );
		$this->assertEmpty( $meta );
	}
}
