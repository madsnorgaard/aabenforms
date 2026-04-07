<?php

namespace Drupal\aabenforms_core\Service;

/**
 * Collects workflow execution steps during a single request.
 *
 * ECA actions call addStep() as they execute. The WebformApiController
 * reads the collected steps after $submission->save() and includes
 * them in the JSON response. This works because ECA workflows execute
 * synchronously during entity save.
 */
class WorkflowExecutionCollector {

  /**
   * @var array
   */
  protected array $steps = [];

  /**
   * Records a completed workflow step.
   *
   * @param string $actionId
   *   The ECA action plugin ID.
   * @param string $label
   *   Human-readable step name.
   * @param string $description
   *   What this step did.
   * @param string $status
   *   Step status: 'completed' or 'failed'.
   * @param string|null $error
   *   Error message if failed.
   */
  public function addStep(string $actionId, string $label, string $description, string $status = 'completed', ?string $error = NULL): void {
    $this->steps[] = [
      'id' => $actionId,
      'name' => $label,
      'description' => $description,
      'status' => $status,
      'completed_at' => date('c'),
      'error' => $error,
    ];
  }

  /**
   * Returns all collected steps.
   */
  public function getSteps(): array {
    return $this->steps;
  }

  /**
   * Whether any steps were collected.
   */
  public function hasSteps(): bool {
    return !empty($this->steps);
  }

  /**
   * Returns the workflow result as an array for JSON serialization.
   */
  public function toArray(): array {
    return [
      'status' => $this->hasFailedSteps() ? 'failed' : 'completed',
      'step_count' => count($this->steps),
      'steps' => $this->steps,
    ];
  }

  /**
   * Whether any step has a 'failed' status.
   */
  protected function hasFailedSteps(): bool {
    foreach ($this->steps as $step) {
      if ($step['status'] === 'failed') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
