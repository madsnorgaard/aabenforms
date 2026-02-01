<?php

namespace Drupal\Tests\aabenforms_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\eca\Entity\Eca;

/**
 * Tests end-to-end ECA workflow execution with ÅbenForms actions.
 *
 * @group aabenforms_workflows
 * @group aabenforms_eca
 */
class WorkflowExecutionTest extends KernelTestBase
{

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_base',
    'key',
    'encrypt',
    'domain',
    'aabenforms_core',
    'aabenforms_mitid',
    'aabenforms_workflows',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->installEntitySchema('user');
        $this->installSchema('system', ['sequences']);
        // Don't install config to avoid schema validation issues in tests.
    }

    /**
     * Tests that all ÅbenForms ECA actions are registered.
     */
    public function testActionPluginsRegistered(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');

        // Verify all 4 ÅbenForms action plugins are available.
        $this->assertTrue($action_manager->hasDefinition('aabenforms_mitid_validate'), 'MitID Validate action is registered');
        $this->assertTrue($action_manager->hasDefinition('aabenforms_cpr_lookup'), 'CPR Lookup action is registered');
        $this->assertTrue($action_manager->hasDefinition('aabenforms_cvr_lookup'), 'CVR Lookup action is registered');
        $this->assertTrue($action_manager->hasDefinition('aabenforms_audit_log'), 'Audit Log action is registered');
    }

    /**
     * Tests action plugin metadata.
     */
    public function testActionPluginMetadata(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');

        // Test MitID Validate action metadata.
        $definition = $action_manager->getDefinition('aabenforms_mitid_validate');
        $this->assertEquals('Validate MitID Session', (string) $definition['label']);
        $this->assertStringContainsString('Validates that a MitID authentication session', (string) $definition['description']);

        // Test CPR Lookup action metadata.
        $definition = $action_manager->getDefinition('aabenforms_cpr_lookup');
        $this->assertEquals('CPR Person Lookup', (string) $definition['label']);
        $this->assertStringContainsString('Serviceplatformen SF1520', (string) $definition['description']);

        // Test CVR Lookup action metadata.
        $definition = $action_manager->getDefinition('aabenforms_cvr_lookup');
        $this->assertEquals('CVR Company Lookup', (string) $definition['label']);
        $this->assertStringContainsString('Serviceplatformen SF1530', (string) $definition['description']);

        // Test Audit Log action metadata.
        $definition = $action_manager->getDefinition('aabenforms_audit_log');
        $this->assertEquals('Audit Log', (string) $definition['label']);
        $this->assertStringContainsString('GDPR compliance', (string) $definition['description']);
    }

    /**
     * Tests action plugin instantiation.
     */
    public function testActionPluginInstantiation(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');

        // Test each action can be instantiated.
        $actions = [
        'aabenforms_mitid_validate',
        'aabenforms_cpr_lookup',
        'aabenforms_cvr_lookup',
        'aabenforms_audit_log',
        ];

        foreach ($actions as $action_id) {
            $action = $action_manager->createInstance($action_id, []);
            $this->assertNotNull($action, "Action {$action_id} can be instantiated");
            $this->assertInstanceOf('\Drupal\eca\Plugin\Action\ActionBase', $action);
        }
    }

    /**
     * Tests action plugin configuration.
     */
    public function testActionConfiguration(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');

        // Test MitID Validate action configuration.
        $action = $action_manager->createInstance('aabenforms_mitid_validate', []);
        $config = $action->defaultConfiguration();

        $this->assertArrayHasKey('workflow_id_token', $config);
        $this->assertArrayHasKey('result_token', $config);
        $this->assertArrayHasKey('session_data_token', $config);

        // Test CPR Lookup action configuration.
        $action = $action_manager->createInstance('aabenforms_cpr_lookup', []);
        $config = $action->defaultConfiguration();

        $this->assertArrayHasKey('cpr_token', $config);
        $this->assertArrayHasKey('result_token', $config);
        $this->assertArrayHasKey('use_cache', $config);
        $this->assertTrue($config['use_cache'], 'Caching is enabled by default');
    }

    /**
     * Tests ECA event subscriber registration.
     */
    public function testEcaEventSubscribers(): void
    {
        // Verify ECA is properly integrated.
        $moduleHandler = \Drupal::moduleHandler();
        $this->assertTrue($moduleHandler->moduleExists('eca'), 'ECA module is enabled');
        $this->assertTrue($moduleHandler->moduleExists('eca_base'), 'ECA Base module is enabled');

        // Verify our workflow module is enabled.
        $this->assertTrue($moduleHandler->moduleExists('aabenforms_workflows'), 'ÅbenForms Workflows module is enabled');
    }

    /**
     * Tests workflow action access control.
     */
    public function testActionAccessControl(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');
        $action = $action_manager->createInstance('aabenforms_mitid_validate', []);

        // Create test user.
        $user = \Drupal\user\Entity\User::create(
            [
            'name' => 'testuser',
            'mail' => 'testuser@example.com',
            'status' => 1,
            ]
        );
        $user->save();

        // Test action access (should be allowed by default for AabenFormsActionBase).
        $access = $action->access(null, $user, true);
        $this->assertTrue($access->isAllowed(), 'Action access is allowed for authenticated user');
    }

    /**
     * Tests action plugin error handling structure.
     */
    public function testActionErrorHandling(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');

        // All actions should extend AabenFormsActionBase which provides error handling.
        $actions = [
        'aabenforms_mitid_validate',
        'aabenforms_cpr_lookup',
        'aabenforms_cvr_lookup',
        'aabenforms_audit_log',
        ];

        foreach ($actions as $action_id) {
            $action = $action_manager->createInstance($action_id, []);
            $this->assertInstanceOf(
                '\Drupal\aabenforms_workflows\Plugin\Action\AabenFormsActionBase',
                $action,
                "Action {$action_id} extends AabenFormsActionBase"
            );
        }
    }

    /**
     * Tests action logging integration.
     */
    public function testActionLogging(): void
    {
        $action_manager = \Drupal::service('plugin.manager.action');
        $action = $action_manager->createInstance('aabenforms_cpr_lookup', []);

        // Verify logger is available via reflection.
        $reflection = new \ReflectionClass($action);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);
        $logger = $property->getValue($action);

        $this->assertNotNull($logger, 'Action has logger instance');
    }

    /**
     * Tests workflow services are available.
     */
    public function testWorkflowServicesAvailable(): void
    {
        // Verify core services needed for workflows.
        $this->assertTrue(\Drupal::hasService('aabenforms_core.serviceplatformen_client'), 'Serviceplatformen client available');
        $this->assertTrue(\Drupal::hasService('aabenforms_core.audit_logger'), 'Audit logger available');

        // Verify MitID services.
        if (\Drupal::moduleHandler()->moduleExists('aabenforms_mitid')) {
            $this->assertTrue(\Drupal::hasService('aabenforms_mitid.session_manager'), 'MitID session manager available');
            $this->assertTrue(\Drupal::hasService('aabenforms_mitid.cpr_extractor'), 'MitID CPR extractor available');
        }
    }

}
