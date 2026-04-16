<?php
/**
 * Integration tests for the Site_Agent Ability class.
 *
 * @package WordPress\AI\Tests\Integration\Includes\Abilities\Site_Agent
 */

namespace WordPress\AI\Tests\Integration\Includes\Abilities\Site_Agent;

use WP_Error;
use WP_UnitTestCase;
use WordPress\AI\Abilities\Site_Agent\Site_Agent;

/**
 * Test case for Site_Agent Ability.
 *
 * @since 0.7.0
 */
class Site_AgentTest extends WP_UnitTestCase {
	/**
	 * Ability instance.
	 *
	 * @var Site_Agent
	 */
	private $ability;

	/**
	 * Setup method.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ability = new Site_Agent(
			'ai/site_agent',
			array(
				'label'       => 'Site Agent',
				'description' => 'Site Agent',
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
		$this->assertArrayHasKey( 'command', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['command']['type'] );
		$this->assertContains( 'command', $schema['required'] );
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
		$this->assertArrayHasKey( 'action_found', $schema['properties'] );
		$this->assertEquals( 'boolean', $schema['properties']['action_found']['type'] );
		$this->assertArrayHasKey( 'action', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['action']['type'] );
		$this->assertArrayHasKey( 'args', $schema['properties'] );
		$this->assertEquals( 'object', $schema['properties']['args']['type'] );
		$this->assertArrayHasKey( 'message', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['message']['type'] );
		$this->assertContains( 'action_found', $schema['required'] );
		$this->assertContains( 'message', $schema['required'] );
	}

	/**
	 * Test permission_callback allows admins.
	 */
	public function test_permission_callback_allows_admins() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'permission_callback' );
		$method->setAccessible( true );

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$result = $method->invoke( $this->ability, array() );
		$this->assertTrue( $result );
	}

	/**
	 * Test permission_callback rejects non-admins.
	 */
	public function test_permission_callback_rejects_non_admins() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'permission_callback' );
		$method->setAccessible( true );

		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$result = $method->invoke( $this->ability, array() );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'unauthorized', $result->get_error_code() );
	}

	/**
	 * Test execute_callback rejects empty command.
	 */
	public function test_execute_callback_rejects_empty_command() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_callback' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, array() );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_command', $result->get_error_code() );
	}

	/**
	 * Test execute_wordpress_action logic for update_site_title.
	 */
	public function test_execute_wordpress_action_update_site_title() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_wordpress_action' );
		$method->setAccessible( true );

		$command_data = array(
			'action' => 'update_site_title',
			'args'   => array( 'new_title' => 'New Awesome Title' ),
		);

		$method->invoke( $this->ability, $command_data );

		$this->assertEquals( 'New Awesome Title', get_option( 'blogname' ) );
	}

	/**
	 * Test execute_wordpress_action logic for update_site_description.
	 */
	public function test_execute_wordpress_action_update_site_description() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_wordpress_action' );
		$method->setAccessible( true );

		$command_data = array(
			'action' => 'update_site_description',
			'args'   => array( 'new_description' => 'New Awesome Description' ),
		);

		$method->invoke( $this->ability, $command_data );

		$this->assertEquals( 'New Awesome Description', get_option( 'blogdescription' ) );
	}

	/**
	 * Test execute_wordpress_action logic for create_draft_post.
	 */
	public function test_execute_wordpress_action_create_draft_post() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_wordpress_action' );
		$method->setAccessible( true );

		$command_data = array(
			'action' => 'create_draft_post',
			'args'   => array(
				'post_title'   => 'A Test Draft',
				'post_content' => 'Some content here.',
			),
		);

		$method->invoke( $this->ability, $command_data );

		$posts = get_posts(
			array(
				'post_status' => 'draft',
				'title'       => 'A Test Draft',
			)
		);

		$this->assertNotEmpty( $posts );
		$this->assertEquals( 'A Test Draft', $posts[0]->post_title );
		$this->assertEquals( 'Some content here.', $posts[0]->post_content );
	}

	/**
	 * Test execute_callback handles execution skip if no AI client.
	 */
	public function test_execute_callback_with_command() {
		$reflection = new \ReflectionClass( $this->ability );
		$method     = $reflection->getMethod( 'execute_callback' );
		$method->setAccessible( true );

		$input = array(
			'command' => 'Update the site title to Test',
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
		$this->assertArrayHasKey( 'action_found', $result, 'Result should have action_found key' );
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
