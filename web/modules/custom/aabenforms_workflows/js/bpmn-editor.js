/**
 * @file
 * BPMN.io editor integration for ÅbenForms workflow wizard.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.aabenformsBpmnEditor = {
    modeler: null,
    autoSaveTimeout: null,

    attach: function (context, settings) {
      var $canvas = $('#bpmn-canvas', context);

      if ($canvas.length === 0 || $canvas.data('bpmn-initialized')) {
        return;
      }

      $canvas.data('bpmn-initialized', true);

      var self = this;
      var bpmnSettings = settings.aabenforms_workflows.bpmn;

      // Initialize BPMN.io modeler with Danish municipal palette.
      if (typeof BpmnJS === 'undefined') {
        console.error('BPMN.io library not loaded');
        return;
      }

      // Create modeler with custom palette.
      this.modeler = new BpmnJS({
        container: '#bpmn-canvas',
        keyboard: {
          bindTo: document
        },
        additionalModules: [
          {
            __init__: ['danishMunicipalPalette'],
            danishMunicipalPalette: ['type', window.DanishMunicipalPaletteProvider]
          }
        ]
      });

      // Load initial BPMN XML.
      this.modeler.importXML(bpmnSettings.xml).then(function () {
        var canvas = self.modeler.get('canvas');
        canvas.zoom('fit-viewport');

        // Validate on load.
        self.validateBpmn();
      }).catch(function (err) {
        console.error('Failed to load BPMN diagram:', err);
        self.showError('Failed to load workflow diagram: ' + err.message);
      });

      // Auto-save on changes.
      this.modeler.on('commandStack.changed', function () {
        self.scheduleAutoSave();
      });

      // Attach to form submit to ensure XML is saved.
      var $form = $canvas.closest('form');
      $form.on('submit', function () {
        self.saveBpmnXml();
        return true;
      });

      // Setup validation on blur.
      $canvas.on('blur', function () {
        self.validateBpmn();
      });
    },

    /**
     * Schedules auto-save after a delay.
     */
    scheduleAutoSave: function () {
      var self = this;

      if (this.autoSaveTimeout) {
        clearTimeout(this.autoSaveTimeout);
      }

      this.autoSaveTimeout = setTimeout(function () {
        self.saveBpmnXml();
      }, 2000); // Auto-save after 2 seconds of inactivity.
    },

    /**
     * Saves BPMN XML to hidden form field.
     */
    saveBpmnXml: function () {
      var self = this;

      this.modeler.saveXML({format: true}).then(function (result) {
        var xml = result.xml;

        // Update hidden field.
        $('#bpmn-xml-data').val(xml);

        // Show save indicator.
        self.showSuccess('Workflow auto-saved');

        // Validate after save.
        self.validateBpmn();
      }).catch(function (err) {
        console.error('Failed to save BPMN:', err);
        self.showError('Failed to save workflow: ' + err.message);
      });
    },

    /**
     * Validates BPMN XML structure.
     */
    validateBpmn: function () {
      var self = this;

      this.modeler.saveXML().then(function (result) {
        var xml = result.xml;

        // Perform client-side validation.
        var errors = self.performClientValidation(xml);

        if (errors.length === 0) {
          self.showSuccess('Workflow structure is valid');
          $('#bpmn-validation-status').removeClass('error').addClass('success');
        } else {
          self.showError('Validation errors: ' + errors.join(', '));
          $('#bpmn-validation-status').removeClass('success').addClass('error');
        }
      }).catch(function (err) {
        console.error('Failed to validate BPMN:', err);
      });
    },

    /**
     * Performs client-side validation of BPMN structure.
     */
    performClientValidation: function (xml) {
      var errors = [];
      var parser = new DOMParser();
      var xmlDoc = parser.parseFromString(xml, 'text/xml');

      // Check for parse errors.
      if (xmlDoc.getElementsByTagName('parsererror').length > 0) {
        errors.push('Invalid XML structure');
        return errors;
      }

      // Check for start event.
      var startEvents = xmlDoc.getElementsByTagName('bpmn:startEvent');
      if (startEvents.length === 0) {
        startEvents = xmlDoc.getElementsByTagName('startEvent');
      }
      if (startEvents.length === 0) {
        errors.push('No start event found - every workflow must have a start point');
      }

      // Check for end event.
      var endEvents = xmlDoc.getElementsByTagName('bpmn:endEvent');
      if (endEvents.length === 0) {
        endEvents = xmlDoc.getElementsByTagName('endEvent');
      }
      if (endEvents.length === 0) {
        errors.push('No end event found - every workflow must have an end point');
      }

      // Check for process element.
      var processes = xmlDoc.getElementsByTagName('bpmn:process');
      if (processes.length === 0) {
        processes = xmlDoc.getElementsByTagName('process');
      }
      if (processes.length === 0) {
        errors.push('No BPMN process found');
      }

      // Check that all sequence flows have valid references.
      var sequenceFlows = xmlDoc.getElementsByTagName('bpmn:sequenceFlow');
      if (sequenceFlows.length === 0) {
        sequenceFlows = xmlDoc.getElementsByTagName('sequenceFlow');
      }

      // Collect all element IDs.
      var elementIds = [];
      var allElements = xmlDoc.querySelectorAll('[id]');
      for (var i = 0; i < allElements.length; i++) {
        elementIds.push(allElements[i].getAttribute('id'));
      }

      // Validate sequence flow references.
      for (var i = 0; i < sequenceFlows.length; i++) {
        var flow = sequenceFlows[i];
        var sourceRef = flow.getAttribute('sourceRef');
        var targetRef = flow.getAttribute('targetRef');

        if (sourceRef && elementIds.indexOf(sourceRef) === -1) {
          errors.push('Invalid source reference in sequence flow: ' + sourceRef);
        }

        if (targetRef && elementIds.indexOf(targetRef) === -1) {
          errors.push('Invalid target reference in sequence flow: ' + targetRef);
        }
      }

      return errors;
    },

    /**
     * Shows success message.
     */
    showSuccess: function (message) {
      var $status = $('#bpmn-validation-status .validation-message');
      $status.html('<span class="success-icon">✓</span> ' + message);
      $status.removeClass('error').addClass('success');

      // Clear after 3 seconds.
      setTimeout(function () {
        $status.fadeOut(function () {
          $(this).html('').show();
        });
      }, 3000);
    },

    /**
     * Shows error message.
     */
    showError: function (message) {
      var $status = $('#bpmn-validation-status .validation-message');
      $status.html('<span class="error-icon">✗</span> ' + message);
      $status.removeClass('success').addClass('error');
    }
  };

})(jQuery, Drupal, drupalSettings);
