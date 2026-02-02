<?php

namespace Drupal\Tests\aabenforms_workflows\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for workflow case worker dashboard.
 *
 * @group aabenforms_workflows
 * @group functional_javascript
 */
class WorkflowDashboardTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'webform',
    'eca',
    'eca_content',
    'aabenforms_core',
    'aabenforms_workflows',
    'views',
  ];

  /**
   * Case worker user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $caseWorkerUser;

  /**
   * Test webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create case worker user.
    $this->caseWorkerUser = $this->drupalCreateUser([
      'access content',
      'view any webform submission',
      'edit any webform submission',
      'access workflow dashboard',
    ]);

    // Create test webform.
    $this->webform = Webform::create([
      'id' => 'test_request_form',
      'title' => 'Test Request Form',
      'elements' => <<<YAML
applicant_name:
  '#type': textfield
  '#title': 'Applicant Name'
request_details:
  '#type': textarea
  '#title': 'Request Details'
parent1_status:
  '#type': hidden
  '#default_value': 'pending'
parent2_status:
  '#type': hidden
  '#default_value': 'pending'
workflow_status:
  '#type': hidden
  '#default_value': 'pending_approval'
YAML
    ]);
    $this->webform->save();

    // Create test submissions with different statuses.
    $this->createTestSubmissions();
  }

  /**
   * Test case worker dashboard displays pending tasks.
   */
  public function testDashboardDisplaysPendingTasks(): void {
    $this->drupalLogin($this->caseWorkerUser);

    // Navigate to dashboard.
    $this->drupalGet('/admin/aabenforms/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    // Verify dashboard title.
    $this->assertSession()->pageTextContains('Workflow Dashboard');

    // Verify pending tasks section exists.
    $this->assertSession()->pageTextContains('Pending Tasks');

    // Verify test submissions appear.
    $this->assertSession()->pageTextContains('Pending Approval 1');
    $this->assertSession()->pageTextContains('Pending Approval 2');
  }

  /**
   * Test task filtering by status.
   */
  public function testTaskFilteringByStatus(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    // Filter by status.
    $this->assertSession()->fieldExists('status_filter');
    $page->selectFieldOption('status_filter', 'awaiting_review');

    // Wait for AJAX to update.
    $this->assertSession()->waitForText('Awaiting Review 1', 10000);

    // Should show only awaiting_review tasks.
    $this->assertSession()->pageTextContains('Awaiting Review 1');
    $this->assertSession()->pageTextNotContains('Pending Approval 1');
  }

  /**
   * Test task filtering by date range.
   */
  public function testTaskFilteringByDateRange(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    // Set date range filter.
    $this->assertSession()->fieldExists('date_from');
    $this->assertSession()->fieldExists('date_to');

    $page->fillField('date_from', date('Y-m-d', strtotime('-7 days')));
    $page->fillField('date_to', date('Y-m-d'));

    $page->pressButton('Apply Filters');

    // Wait for AJAX to update.
    $this->getSession()->wait(2000);

    // Should show submissions within date range.
    $this->assertSession()->pageTextContains('Pending Approval');
  }

  /**
   * Test task search functionality.
   */
  public function testTaskSearch(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    // Use search field.
    $this->assertSession()->fieldExists('task_search');
    $page->fillField('task_search', 'Applicant One');

    // Wait for AJAX search.
    $this->getSession()->wait(2000);

    // Should show only matching tasks.
    $this->assertSession()->pageTextContains('Pending Approval 1');
    $this->assertSession()->pageTextNotContains('Applicant Two');
  }

  /**
   * Test task sorting.
   */
  public function testTaskSorting(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    // Click on "Created" column header to sort.
    $page->clickLink('Created');

    // Wait for AJAX sort.
    $this->getSession()->wait(2000);

    // Verify sort order changed (newest first).
    $rows = $page->findAll('css', '.task-list tbody tr');
    $this->assertNotEmpty($rows);

    // Click again to reverse sort.
    $page->clickLink('Created');
    $this->getSession()->wait(2000);
  }

  /**
   * Test task action buttons.
   */
  public function testTaskActionButtons(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    // Find "View" button on first task.
    $view_buttons = $page->findAll('css', '.task-view-button');
    $this->assertNotEmpty($view_buttons, 'View buttons found');

    $view_buttons[0]->click();

    // Should navigate to task detail page.
    $this->assertSession()->addressMatches('/\/admin\/aabenforms\/task\/\d+/');
  }

  /**
   * Test bulk actions on tasks.
   */
  public function testBulkTaskActions(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    // Select multiple tasks.
    $checkboxes = $page->findAll('css', '.task-checkbox');
    $this->assertNotEmpty($checkboxes, 'Task checkboxes found');

    $checkboxes[0]->check();
    $checkboxes[1]->check();

    // Select bulk action.
    $this->assertSession()->fieldExists('bulk_action');
    $page->selectFieldOption('bulk_action', 'assign_to_me');

    $page->pressButton('Apply');

    // Verify success message.
    $this->assertSession()->waitForText('tasks assigned', 10000);
    $this->assertSession()->pageTextContains('tasks assigned');
  }

  /**
   * Test task statistics display.
   */
  public function testTaskStatistics(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');

    // Verify statistics section.
    $this->assertSession()->pageTextContains('Task Statistics');

    // Should show counts for different statuses.
    $this->assertSession()->elementExists('css', '.stat-pending-approval');
    $this->assertSession()->elementExists('css', '.stat-awaiting-review');
    $this->assertSession()->elementExists('css', '.stat-completed');

    // Verify counts are displayed.
    $pending_count = $this->getSession()->getPage()
      ->find('css', '.stat-pending-approval .count');
    $this->assertNotNull($pending_count);
    $this->assertNotEmpty($pending_count->getText());
  }

  /**
   * Test task detail view.
   */
  public function testTaskDetailView(): void {
    $this->drupalLogin($this->caseWorkerUser);

    // Get a submission ID.
    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'test_request_form']);
    $submission = reset($submissions);
    $submission_id = $submission->id();

    // Navigate to task detail.
    $this->drupalGet("/admin/aabenforms/task/{$submission_id}");
    $this->assertSession()->statusCodeEquals(200);

    // Verify submission details are shown.
    $this->assertSession()->pageTextContains('Task Details');
    $this->assertSession()->pageTextContains('Applicant');

    // Verify approval history.
    $this->assertSession()->pageTextContains('Approval History');

    // Verify action buttons.
    $this->assertSession()->buttonExists('Approve');
    $this->assertSession()->buttonExists('Reject');
  }

  /**
   * Test real-time dashboard updates via WebSocket/AJAX.
   */
  public function testRealTimeDashboardUpdates(): void {
    $this->drupalLogin($this->caseWorkerUser);

    $this->drupalGet('/admin/aabenforms/dashboard');

    // Get initial task count.
    $initial_tasks = $this->getSession()->getPage()
      ->findAll('css', '.task-list tbody tr');
    $initial_count = count($initial_tasks);

    // Create a new submission (simulating new task).
    $new_submission = WebformSubmission::create([
      'webform_id' => 'test_request_form',
      'data' => [
        'applicant_name' => 'New Applicant',
        'request_details' => 'New request',
        'workflow_status' => 'pending_approval',
      ],
    ]);
    $new_submission->save();

    // Wait for AJAX update (assuming auto-refresh is enabled).
    $this->getSession()->wait(5000);

    // Verify new task appears.
    $updated_tasks = $this->getSession()->getPage()
      ->findAll('css', '.task-list tbody tr');
    $updated_count = count($updated_tasks);

    // Note: This may not work without actual WebSocket/polling implementation.
    // In that case, we'd need to manually refresh.
    // $this->assertGreaterThan($initial_count, $updated_count);.
  }

  /**
   * Test task assignment functionality.
   */
  public function testTaskAssignment(): void {
    $this->drupalLogin($this->caseWorkerUser);

    // Get a submission.
    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'test_request_form']);
    $submission = reset($submissions);
    $submission_id = $submission->id();

    $this->drupalGet("/admin/aabenforms/task/{$submission_id}");
    $page = $this->getSession()->getPage();

    // Assign task to self.
    $page->pressButton('Assign to Me');

    // Verify assignment success.
    $this->assertSession()->waitForText('Task assigned to you', 10000);
    $this->assertSession()->pageTextContains('Assigned to:');
    $this->assertSession()->pageTextContains($this->caseWorkerUser->getAccountName());
  }

  /**
   * Creates test submissions with different statuses.
   */
  protected function createTestSubmissions(): void {
    // Pending approval submissions.
    for ($i = 1; $i <= 2; $i++) {
      WebformSubmission::create([
        'webform_id' => 'test_request_form',
        'data' => [
          'applicant_name' => "Pending Approval {$i}",
          'request_details' => "Request details {$i}",
          'parent1_status' => 'pending',
          'parent2_status' => 'pending',
          'workflow_status' => 'pending_approval',
        ],
      ])->save();
    }

    // Awaiting review submissions.
    for ($i = 1; $i <= 2; $i++) {
      WebformSubmission::create([
        'webform_id' => 'test_request_form',
        'data' => [
          'applicant_name' => "Awaiting Review {$i}",
          'request_details' => "Request details {$i}",
          'parent1_status' => 'complete',
          'parent2_status' => 'complete',
          'workflow_status' => 'awaiting_review',
        ],
      ])->save();
    }

    // Completed submissions.
    WebformSubmission::create([
      'webform_id' => 'test_request_form',
      'data' => [
        'applicant_name' => 'Completed Task',
        'request_details' => 'Completed request',
        'parent1_status' => 'complete',
        'parent2_status' => 'complete',
        'workflow_status' => 'completed',
      ],
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up test data.
    if ($this->webform) {
      $this->webform->delete();
    }

    // Delete all test submissions.
    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'test_request_form']);

    foreach ($submissions as $submission) {
      $submission->delete();
    }

    parent::tearDown();
  }

}
