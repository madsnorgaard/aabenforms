/**
 * @file
 * BPMN.io editor integration for ÅbenForms workflow wizard.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.aabenformsBpmnEditor = {
    modeler: NULL,
    autoSaveTimeout: NULL,

    attach: function (context, settings) {
      var $canvas = $('#bpmn-canvas', context);

      if ($canvas.length === 0 || $canvas.data('bpmn-initialized')) {
        return;
      }

      $canvas.data('bpmn-initialized', TRUE);

      var self = this;
      var bpmnSettings = settings.aabenforms_workflows.bpmn;

      // bpmn_io contrib exposes the bpmn-js library by auto-instantiating
      // window.modeler — it does NOT expose a window.BpmnJS class. Grab
      // the constructor off the existing instance to spin up our own.
      if (!window.modeler || typeof window.modeler.constructor !== 'function') {
        console.error('bpmn_io library did not initialize a window.modeler');
        return;
      }
      var BpmnJSCtor = window.modeler.constructor;

      // Create modeler with custom palette. bpmn-js 13 removed the
      // explicit keyboard.bindTo option (it's implicit now).
      var additionalModules = [];
      if (window.DanishMunicipalPaletteProvider) {
        additionalModules.push({
          __init__: ['danishMunicipalPalette'],
          danishMunicipalPalette: ['type', window.DanishMunicipalPaletteProvider]
        });
      }
      this.modeler = new BpmnJSCtor({
        container: '#bpmn-canvas',
        additionalModules: additionalModules
      });

      // Our hand-authored .bpmn templates don't include bpmndi:BPMNDiagram
      // (visual layout) - bpmn-js refuses to render those with "no
      // diagram to display". window.layoutProcess() (from bpmn_io contrib)
      // computes the layout server-side and returns enriched XML.
      var loadXml = bpmnSettings.xml;
      var importPromise;
      if (typeof window.layoutProcess === 'function' && loadXml.indexOf('BPMNDiagram') === -1) {
        importPromise = window.layoutProcess(loadXml).then(function (laidOutXml) {
          return self.modeler.importXML(laidOutXml);
        });
      } else {
        importPromise = self.modeler.importXML(loadXml);
      }
      importPromise.then(function () {
        var canvas = self.modeler.get('canvas');
        canvas.zoom('fit-viewport');
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
        return TRUE;
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

      this.modeler.saveXML({format: TRUE}).then(function (result) {
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
