/**
 * @file
 * Custom BPMN palette for Danish municipal workflows.
 *
 * Provides 16 specialized task types for Danish government services:
 * - MitID authentication
 * - CPR lookup (Serviceplatformen SF1520)
 * - CVR lookup (Serviceplatformen SF1530)
 * - Payment processing
 * - SMS notifications
 * - PDF generation
 * - Calendar/booking
 * - Appointment booking
 * - Reminder notifications
 * - Zoning validation
 * - Neighbor notification
 * - Audit logging (GDPR)
 * - Email notifications
 * - DAWA address validation
 * - Approval email
 * - Document upload
 */

(function (window) {
  'use strict';

  /**
   * Custom palette provider for Danish municipal tasks.
   */
  function DanishMunicipalPaletteProvider(
    palette,
    create,
    elementFactory,
    spaceTool,
    lassoTool,
    handTool,
    globalConnect,
    translate
  ) {
    this._palette = palette;
    this._create = create;
    this._elementFactory = elementFactory;
    this._spaceTool = spaceTool;
    this._lassoTool = lassoTool;
    this._handTool = handTool;
    this._globalConnect = globalConnect;
    this._translate = translate;

    palette.registerProvider(this);
  }

  DanishMunicipalPaletteProvider.$inject = [
    'palette',
    'create',
    'elementFactory',
    'spaceTool',
    'lassoTool',
    'handTool',
    'globalConnect',
    'translate'
  ];

  DanishMunicipalPaletteProvider.prototype.getPaletteEntries = function () {
    var actions = {},
      create = this._create,
      elementFactory = this._elementFactory,
      spaceTool = this._spaceTool,
      lassoTool = this._lassoTool,
      handTool = this._handTool,
      globalConnect = this._globalConnect,
      translate = this._translate;

    function createTask(type, label, icon) {
      return function (event) {
        var shape = elementFactory.createShape({
          type: 'bpmn:Task',
          businessObject: {
            name: label,
            'aabenforms:taskType': type
          }
        });
        create.start(event, shape);
      };
    }

    // Tool actions.
    actions['hand-tool'] = {
      group: 'tools',
      className: 'bpmn-icon-hand-tool',
      title: translate('Activate the hand tool'),
      action: {
        click: function (event) {
          handTool.activateHand(event);
        }
      }
    };

    actions['lasso-tool'] = {
      group: 'tools',
      className: 'bpmn-icon-lasso-tool',
      title: translate('Activate the lasso tool'),
      action: {
        click: function (event) {
          lassoTool.activateSelection(event);
        }
      }
    };

    actions['space-tool'] = {
      group: 'tools',
      className: 'bpmn-icon-space-tool',
      title: translate('Activate the create/remove space tool'),
      action: {
        click: function (event) {
          spaceTool.activateSelection(event);
        }
      }
    };

    actions['global-connect-tool'] = {
      group: 'tools',
      className: 'bpmn-icon-connection-multi',
      title: translate('Activate the global connect tool'),
      action: {
        click: function (event) {
          globalConnect.toggle(event);
        }
      }
    };

    actions['tool-separator'] = {
      group: 'tools',
      separator: true
    };

    // Start/End events.
    actions['create.start-event'] = {
      group: 'event',
      className: 'bpmn-icon-start-event-none',
      title: translate('Create StartEvent'),
      action: {
        dragstart: function (event) {
          var shape = elementFactory.createShape({type: 'bpmn:StartEvent'});
          create.start(event, shape);
        },
        click: function (event) {
          var shape = elementFactory.createShape({type: 'bpmn:StartEvent'});
          create.start(event, shape);
        }
      }
    };

    actions['create.end-event'] = {
      group: 'event',
      className: 'bpmn-icon-end-event-none',
      title: translate('Create EndEvent'),
      action: {
        dragstart: function (event) {
          var shape = elementFactory.createShape({type: 'bpmn:EndEvent'});
          create.start(event, shape);
        },
        click: function (event) {
          var shape = elementFactory.createShape({type: 'bpmn:EndEvent'});
          create.start(event, shape);
        }
      }
    };

    actions['event-separator'] = {
      group: 'event',
      separator: true
    };

    // Gateway.
    actions['create.exclusive-gateway'] = {
      group: 'gateway',
      className: 'bpmn-icon-gateway-xor',
      title: translate('Create Exclusive Gateway (Decision Point)'),
      action: {
        dragstart: function (event) {
          var shape = elementFactory.createShape({type: 'bpmn:ExclusiveGateway'});
          create.start(event, shape);
        },
        click: function (event) {
          var shape = elementFactory.createShape({type: 'bpmn:ExclusiveGateway'});
          create.start(event, shape);
        }
      }
    };

    actions['gateway-separator'] = {
      group: 'gateway',
      separator: true
    };

    // Danish municipal tasks - Authentication.
    actions['create.mitid-auth'] = {
      group: 'danish-auth',
      className: 'danish-icon-mitid',
      title: translate('MitID Authentication'),
      action: {
        dragstart: createTask('mitid_auth', 'MitID Login', 'mitid'),
        click: createTask('mitid_auth', 'MitID Login', 'mitid')
      }
    };

    // Danish municipal tasks - Lookups.
    actions['create.cpr-lookup'] = {
      group: 'danish-lookup',
      className: 'danish-icon-cpr',
      title: translate('CPR Lookup (Person Data)'),
      action: {
        dragstart: createTask('cpr_lookup', 'CPR Opslag', 'cpr'),
        click: createTask('cpr_lookup', 'CPR Opslag', 'cpr')
      }
    };

    actions['create.cvr-lookup'] = {
      group: 'danish-lookup',
      className: 'danish-icon-cvr',
      title: translate('CVR Lookup (Company Data)'),
      action: {
        dragstart: createTask('cvr_lookup', 'CVR Opslag', 'cvr'),
        click: createTask('cvr_lookup', 'CVR Opslag', 'cvr')
      }
    };

    actions['create.dawa-validation'] = {
      group: 'danish-lookup',
      className: 'danish-icon-dawa',
      title: translate('DAWA Address Validation'),
      action: {
        dragstart: createTask('dawa_validation', 'Adressevalidering', 'dawa'),
        click: createTask('dawa_validation', 'Adressevalidering', 'dawa')
      }
    };

    // Danish municipal tasks - Payments & Notifications.
    actions['create.payment'] = {
      group: 'danish-transaction',
      className: 'danish-icon-payment',
      title: translate('Payment Processing'),
      action: {
        dragstart: createTask('payment', 'Betaling', 'payment'),
        click: createTask('payment', 'Betaling', 'payment')
      }
    };

    actions['create.sms'] = {
      group: 'danish-notification',
      className: 'danish-icon-sms',
      title: translate('Send SMS Notification'),
      action: {
        dragstart: createTask('sms', 'Send SMS', 'sms'),
        click: createTask('sms', 'Send SMS', 'sms')
      }
    };

    actions['create.email'] = {
      group: 'danish-notification',
      className: 'danish-icon-email',
      title: translate('Send Email'),
      action: {
        dragstart: createTask('email', 'Send E-mail', 'email'),
        click: createTask('email', 'Send E-mail', 'email')
      }
    };

    actions['create.approval-email'] = {
      group: 'danish-notification',
      className: 'danish-icon-approval',
      title: translate('Send Approval Email'),
      action: {
        dragstart: createTask('approval_email', 'Godkendelsesmail', 'approval'),
        click: createTask('approval_email', 'Godkendelsesmail', 'approval')
      }
    };

    // Danish municipal tasks - Documents.
    actions['create.pdf'] = {
      group: 'danish-document',
      className: 'danish-icon-pdf',
      title: translate('Generate PDF'),
      action: {
        dragstart: createTask('pdf', 'Generer PDF', 'pdf'),
        click: createTask('pdf', 'Generer PDF', 'pdf')
      }
    };

    actions['create.document-upload'] = {
      group: 'danish-document',
      className: 'danish-icon-upload',
      title: translate('Document Upload'),
      action: {
        dragstart: createTask('document_upload', 'Upload Dokument', 'upload'),
        click: createTask('document_upload', 'Upload Dokument', 'upload')
      }
    };

    // Danish municipal tasks - Scheduling.
    actions['create.calendar'] = {
      group: 'danish-scheduling',
      className: 'danish-icon-calendar',
      title: translate('Calendar/Booking'),
      action: {
        dragstart: createTask('calendar', 'Kalender', 'calendar'),
        click: createTask('calendar', 'Kalender', 'calendar')
      }
    };

    actions['create.booking'] = {
      group: 'danish-scheduling',
      className: 'danish-icon-booking',
      title: translate('Appointment Booking'),
      action: {
        dragstart: createTask('booking', 'Tidsbestilling', 'booking'),
        click: createTask('booking', 'Tidsbestilling', 'booking')
      }
    };

    actions['create.reminder'] = {
      group: 'danish-scheduling',
      className: 'danish-icon-reminder',
      title: translate('Send Reminder'),
      action: {
        dragstart: createTask('reminder', 'Send Påmindelse', 'reminder'),
        click: createTask('reminder', 'Send Påmindelse', 'reminder')
      }
    };

    // Danish municipal tasks - Municipal specific.
    actions['create.zoning'] = {
      group: 'danish-municipal',
      className: 'danish-icon-zoning',
      title: translate('Zoning Validation'),
      action: {
        dragstart: createTask('zoning', 'Lokalplancheck', 'zoning'),
        click: createTask('zoning', 'Lokalplancheck', 'zoning')
      }
    };

    actions['create.neighbor-notification'] = {
      group: 'danish-municipal',
      className: 'danish-icon-neighbor',
      title: translate('Neighbor Notification'),
      action: {
        dragstart: createTask('neighbor_notification', 'Naboorientering', 'neighbor'),
        click: createTask('neighbor_notification', 'Naboorientering', 'neighbor')
      }
    };

    // Danish municipal tasks - Compliance.
    actions['create.audit-log'] = {
      group: 'danish-compliance',
      className: 'danish-icon-audit',
      title: translate('Audit Log (GDPR)'),
      action: {
        dragstart: createTask('audit_log', 'Log Handling', 'audit'),
        click: createTask('audit_log', 'Log Handling', 'audit')
      }
    };

    return actions;
  };

  // Export for use in BPMN modeler.
  window.DanishMunicipalPaletteProvider = DanishMunicipalPaletteProvider;

})(window);
