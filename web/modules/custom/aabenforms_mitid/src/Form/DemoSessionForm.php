<?php

namespace Drupal\aabenforms_mitid\Form;

use Drupal\aabenforms_mitid\DemoPersonas;
use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Activates a demo MitID identity for testing gated flows in the modeler.
 *
 * MitID-gated flows fail closed: with no verified session the gate routes to
 * the deny terminal, so the modeler's "Test this event" replay always denies
 * for a gated flow until an identity is established. This form mints a session
 * for one of the demo personas and binds it to the current browser session
 * (the same `mitid_workflow_id` key a real login sets), so the very next
 * "Test this event" - running in this browser - validates and follows the
 * happy path.
 */
class DemoSessionForm extends FormBase {

  /**
   * Browser-session key bridging login (or this form) to the flow gate.
   */
  private const SESSION_WORKFLOW_KEY = 'mitid_workflow_id';

  /**
   * The MitID session manager.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected MitIdSessionManager $sessionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->sessionManager = $container->get('aabenforms_mitid.session_manager');
    // FormBase already declares $requestStack (untyped); assign it here rather
    // than redeclaring a typed property, which would clash with the parent.
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'aabenforms_mitid_demo_session';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $session = $this->requestStack->getCurrentRequest()?->getSession();
    $activeId = $session?->get(self::SESSION_WORKFLOW_KEY);
    $activeData = is_string($activeId) ? $this->sessionManager->getSession($activeId) : NULL;

    $form['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t("<p>MitID-gated flows deny by default when no verified identity is present (fail-closed). To run a gated flow through its happy path in the modeler's <em>Test this event</em>, activate a demo citizen below. The identity is bound to your browser for 15 minutes and used by the MitID validation step exactly like a real login.</p>"),
    ];

    if ($activeData && !empty($activeData['demo_seeded'])) {
      $form['active'] = [
        '#type' => 'item',
        '#markup' => $this->t('<p><strong>Active demo identity:</strong> @name (CPR @cpr). Gated flows tested in this browser will run as this citizen.</p>', [
          '@name' => $activeData['name'] ?? $activeData['persona'] ?? 'unknown',
          '@cpr' => $activeData['cpr'] ?? '?',
        ]),
      ];
    }

    $options = [];
    foreach (DemoPersonas::all() as $slug => $p) {
      $options[$slug] = $this->t('@name - CPR @cpr (@assurance)', [
        '@name' => $p['name'],
        '@cpr' => $p['cpr'],
        '@assurance' => $p['assurance_level'],
      ]);
    }

    $form['persona'] = [
      '#type' => 'radios',
      '#title' => $this->t('Demo citizen'),
      '#options' => $options,
      '#default_value' => DemoPersonas::keys()[0],
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Activate demo identity'),
    ];
    if ($activeData) {
      $form['actions']['clear'] = [
        '#type' => 'submit',
        '#value' => $this->t('Clear demo identity'),
        '#submit' => ['::clearIdentity'],
        '#limit_validation_errors' => [],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $persona = (string) $form_state->getValue('persona');
    $workflowId = $this->sessionManager->seedDemoSession($persona);
    if ($workflowId === NULL) {
      $this->messenger()->addError($this->t('Could not activate demo identity "@p".', ['@p' => $persona]));
      return;
    }

    $this->requestStack->getCurrentRequest()?->getSession()?->set(self::SESSION_WORKFLOW_KEY, $workflowId);
    $data = DemoPersonas::get($persona);
    $this->messenger()->addStatus($this->t('Demo identity active: @name (CPR @cpr). Run a gated flow Test this event now - it will validate as this citizen for 15 minutes.', [
      '@name' => $data['name'],
      '@cpr' => $data['cpr'],
    ]));
  }

  /**
   * Clears the active demo identity from this browser session.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function clearIdentity(array &$form, FormStateInterface $form_state): void {
    $session = $this->requestStack->getCurrentRequest()?->getSession();
    $activeId = $session?->get(self::SESSION_WORKFLOW_KEY);
    if (is_string($activeId) && $activeId !== '') {
      $this->sessionManager->deleteSession($activeId);
    }
    $session?->remove(self::SESSION_WORKFLOW_KEY);
    $this->messenger()->addStatus($this->t('Demo identity cleared. Gated flows will now fail closed again.'));
  }

}
