<?php
/**
 * Integration tests for the Observability_Logger class.
 *
 * @package WordPress\AI\Tests\Integration\Includes\Admin
 */

namespace WordPress\AI\Tests\Integration\Includes\Admin;

use WP_UnitTestCase;
use WordPress\AI\Admin\Observability_Logger;

/**
 * Test case for Observability_Logger.
 *
 * @since 0.7.0
 */
class Observability_LoggerTest extends WP_UnitTestCase {
	/**
	 * Tear down method.
	 */
	public function tearDown(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . Observability_Logger::TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		parent::tearDown();
	}

	/**
	 * Test table creation.
	 */
	public function test_create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . Observability_Logger::TABLE_NAME;

		// Ensure table doesn't exist before test.
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		Observability_Logger::create_table();

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertTrue( $table_exists, 'Table should be created.' );
	}

	/**
	 * Test table is only created once (dbDelta behavior).
	 */
	public function test_create_table_idempotent() {
		global $wpdb;
		$table_name = $wpdb->prefix . Observability_Logger::TABLE_NAME;

		Observability_Logger::create_table();
		Observability_Logger::create_table();

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertTrue( $table_exists, 'Table should still exist after second creation call.' );
	}

	/**
	 * Test logging functionality.
	 */
	public function test_log_inserts_record() {
		global $wpdb;
		$table_name = $wpdb->prefix . Observability_Logger::TABLE_NAME;

		Observability_Logger::create_table();

		Observability_Logger::log( 'openai', 'test prompt', 'test response' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results( "SELECT * FROM {$table_name}" );

		$this->assertCount( 1, $logs );
		$this->assertEquals( 'openai', $logs[0]->provider );
		$this->assertEquals( 'test prompt', $logs[0]->prompt );
		$this->assertEquals( 'test response', $logs[0]->response );
		$this->assertEquals( 'success', $logs[0]->status );
	}

	/**
	 * Test logging functionality with error status.
	 */
	public function test_log_inserts_error_status() {
		global $wpdb;
		$table_name = $wpdb->prefix . Observability_Logger::TABLE_NAME;

		Observability_Logger::create_table();

		Observability_Logger::log( 'openai', 'test prompt', 'error details', 'error' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results( "SELECT * FROM {$table_name}" );

		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]->status );
	}

	/**
	 * Test render_page displays empty state when no logs exist.
	 */
	public function test_render_page_empty_state() {
		Observability_Logger::create_table();

		ob_start();
		Observability_Logger::render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No logs recorded yet.', $output );
	}

	/**
	 * Test render_page displays logs when they exist.
	 */
	public function test_render_page_with_logs() {
		Observability_Logger::create_table();
		Observability_Logger::log( 'openai', 'test prompt', 'test response', 'success' );
		Observability_Logger::log( 'anthropic', 'another prompt', 'error response', 'error' );

		ob_start();
		Observability_Logger::render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'openai', $output );
		$this->assertStringContainsString( 'success', $output );
		$this->assertStringContainsString( 'test prompt...', $output );
		$this->assertStringContainsString( 'anthropic', $output );
		$this->assertStringContainsString( 'error', $output );
		$this->assertStringContainsString( 'another prompt...', $output );
		$this->assertStringNotContainsString( 'No logs recorded yet.', $output );
	}
}
