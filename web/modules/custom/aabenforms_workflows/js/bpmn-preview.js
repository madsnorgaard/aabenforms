/**
 * @file
 * BPMN preview functionality for template browser.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.aabenformsBpmnPreview = {
    viewers: {},

    attach: function (context, settings) {
      var self = this;

      // Full preview page.
      var $previewCanvas = $('#bpmn-preview-canvas', context);
      if ($previewCanvas.length > 0 && !$previewCanvas.data('bpmn-initialized')) {
        $previewCanvas.data('bpmn-initialized', true);
        var previewSettings = settings.aabenforms_workflows.preview;
        this.renderFullPreview($previewCanvas, previewSettings.xml);
      }

      // Thumbnail previews in template cards.
      $('.bpmn-preview-thumbnail', context).each(function () {
        var $thumbnail = $(this);
        if ($thumbnail.data('bpmn-initialized')) {
          return;
        }
        $thumbnail.data('bpmn-initialized', true);

        var templateId = $thumbnail.closest('.template-card').data('template-id');
        var $canvas = $thumbnail.find('.bpmn-preview-canvas');

        // Load preview via AJAX.
        self.loadThumbnailPreview($thumbnail, $canvas, templateId);
      });
    },

    /**
     * Renders full BPMN preview.
     */
    renderFullPreview: function ($canvas, xml) {
      if (!window.modeler || typeof window.modeler.constructor !== 'function') {
        console.error('bpmn_io library did not initialize a window.modeler');
        return;
      }
      var BpmnJSCtor = window.modeler.constructor;

      var viewer = new BpmnJSCtor({
        container: $canvas[0],
        height: 600
      });

      var importer = function (theXml) {
        return viewer.importXML(theXml).then(function () {
          var canvas = viewer.get('canvas');
          canvas.zoom('fit-viewport');
        });
      };
      var loaded;
      if (typeof window.layoutProcess === 'function' && xml.indexOf('BPMNDiagram') === -1) {
        loaded = window.layoutProcess(xml).then(importer);
      } else {
        loaded = importer(xml);
      }
      loaded.catch(function (err) {
        console.error('Failed to load BPMN preview:', err);
        $canvas.html('<div class="error">Failed to load preview: ' + err.message + '</div>');
      });
    },

    /**
     * Loads thumbnail preview for a template.
     */
    loadThumbnailPreview: function ($thumbnail, $canvas, templateId) {
      var self = this;

      // Get template XML from server.
      $.ajax({
        url: '/admin/aabenforms/workflow-templates/preview/' + templateId,
        method: 'GET',
        dataType: 'html',
        success: function (response) {
          // Extract XML from response.
          var $response = $(response);
          var xml = $response.find('#bpmn-preview-canvas').data('xml');

          if (!xml && drupalSettings.aabenforms_workflows.preview) {
            xml = drupalSettings.aabenforms_workflows.preview.xml;
          }

          if (xml) {
            self.renderThumbnail($thumbnail, $canvas, xml);
          } else {
            $thumbnail.find('.bpmn-preview-loading').html(
              '<span class="error-icon">✗</span> Preview unavailable'
            );
          }
        },
        error: function () {
          $thumbnail.find('.bpmn-preview-loading').html(
            '<span class="error-icon">✗</span> Failed to load preview'
          );
        }
      });
    },

    /**
     * Renders a thumbnail preview.
     */
    renderThumbnail: function ($thumbnail, $canvas, xml) {
      if (!window.modeler || typeof window.modeler.constructor !== 'function') {
        $thumbnail.find('.bpmn-preview-loading').html(
          '<span class="info-icon">ℹ</span> BPMN.io not loaded'
        );
        return;
      }
      var BpmnJSCtor = window.modeler.constructor;

      var viewer = new BpmnJSCtor({
        container: $canvas[0],
        height: 200
      });

      var importer = function (theXml) {
        return viewer.importXML(theXml).then(function () {
          var canvas = viewer.get('canvas');
          canvas.zoom('fit-viewport');
          $thumbnail.find('.bpmn-preview-loading').fadeOut();
        });
      };
      var loaded;
      if (typeof window.layoutProcess === 'function' && xml.indexOf('BPMNDiagram') === -1) {
        loaded = window.layoutProcess(xml).then(importer);
      } else {
        loaded = importer(xml);
      }
      loaded.catch(function (err) {
        console.error('Failed to load thumbnail:', err);
        $thumbnail.find('.bpmn-preview-loading').html(
          '<span class="error-icon">✗</span> Preview error'
        );
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
