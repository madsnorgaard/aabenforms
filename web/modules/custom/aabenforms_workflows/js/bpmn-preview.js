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
      if (typeof BpmnJS === 'undefined') {
        console.error('BPMN.io library not loaded');
        return;
      }

      var viewer = new BpmnJS({
        container: $canvas[0],
        height: 600
      });

      viewer.importXML(xml).then(function () {
        var canvas = viewer.get('canvas');
        canvas.zoom('fit-viewport');
      }).catch(function (err) {
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
      if (typeof BpmnJS === 'undefined') {
        $thumbnail.find('.bpmn-preview-loading').html(
          '<span class="info-icon">ℹ</span> BPMN.io not loaded'
        );
        return;
      }

      var viewer = new BpmnJS({
        container: $canvas[0],
        height: 200
      });

      viewer.importXML(xml).then(function () {
        var canvas = viewer.get('canvas');
        canvas.zoom('fit-viewport');

        // Hide loading indicator.
        $thumbnail.find('.bpmn-preview-loading').fadeOut();
      }).catch(function (err) {
        console.error('Failed to load thumbnail:', err);
        $thumbnail.find('.bpmn-preview-loading').html(
          '<span class="error-icon">✗</span> Preview error'
        );
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
