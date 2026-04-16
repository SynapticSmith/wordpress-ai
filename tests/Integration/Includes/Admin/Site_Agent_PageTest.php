<?php
/**
 * Integration tests for the Site_Agent_Page class.
 *
 * @package WordPress\AI\Tests\Integration\Includes\Admin
 */

namespace WordPress\AI\Tests\Integration\Includes\Admin;

use WP_UnitTestCase;
use WordPress\AI\Admin\Site_Agent_Page;

/**
 * Test case for Site_Agent_Page.
 *
 * @since 0.7.0
 */
class Site_Agent_PageTest extends WP_UnitTestCase {
	/**
	 * Test init adds hooks.
	 */
	public function test_init_adds_hooks() {
		Site_Agent_Page::init();

		$this->assertEquals(
			10,
			has_action( 'admin_menu', array( Site_Agent_Page::class, 'add_agent_page' ) )
		);
	}

	/**
	 * Test render_page displays form.
	 */
	public function test_render_page_displays_form() {
		ob_start();
		Site_Agent_Page::render_page();
		$output = ob_get_clean();
		if ( false === $output ) {
			$output = '';
		}

		$this->assertStringContainsString( '<form method="post" action="">', $output );
		$this->assertStringContainsString( 'name="wp_ai_agent_command"', $output );
		$this->assertStringContainsString( 'name="wp_ai_agent_nonce"', $output );
	}

	/**
	 * Test render_page handles POST with valid nonce (skip AI execution).
	 */
	public function test_render_page_handles_post() {
		$_POST['wp_ai_agent_command'] = 'Update site title';
		$_POST['wp_ai_agent_nonce']   = wp_create_nonce( 'run_ai_agent' );

		ob_start();
		try {
			Site_Agent_Page::render_page();
		} catch ( \Throwable $e ) {
			// Ignore AI execution errors.
		}
		$output = ob_get_clean();
		if ( false === $output ) {
			$output = '';
		}

		// Cleanup.
		unset( $_POST['wp_ai_agent_command'] );
		unset( $_POST['wp_ai_agent_nonce'] );

		// We can't easily assert the specific notice without mocking the AI client,
		// but we can assert the form still renders.
		$this->assertStringContainsString( '<form method="post" action="">', $output );
	}
}
