# BPMN.io Visual Editor Integration Guide

## Overview

This document describes the BPMN.io visual editor integration into the ÅbenForms workflow admin UI. The integration enables municipal administrators to create and customize workflows visually without writing XML or YAML.

## Features Implemented

### 1. Visual Workflow Editor (Step 2 in Wizard)

**Location**: `WorkflowTemplateWizardForm.php` - Step 2

The workflow wizard now includes a visual editor step between template selection and action configuration:

1. **Select Template** - Choose a pre-built workflow
2. **Visual Editor** ← NEW STEP
3. **Configure Webform** - Link to webforms
4. **Configure Actions** - Customize action parameters
5. **Data Visibility** - GDPR settings
6. **Preview & Activate** - Final review

#### Features:
- Full BPMN.io modeler embedded in Drupal form
- Custom Danish municipal palette with 16 task types
- Auto-save functionality (2-second debounce)
- Real-time validation
- Client-side and server-side validation
- Visual feedback for validation status

### 2. Danish Municipal Task Palette

**Location**: `js/bpmn-extensions/danish-municipal-palette.js`

Custom palette with 16 specialized task types for Danish government workflows:

#### Authentication
- **MitID Auth** - MitID authentication (Privat/Erhverv)

#### Lookups
- **CPR Lookup** - Person data (Serviceplatformen SF1520)
- **CVR Lookup** - Company data (Serviceplatformen SF1530)
- **DAWA Validation** - Address validation (Danish Address Web API)

#### Transactions
- **Payment** - Payment processing

#### Notifications
- **SMS** - SMS notifications
- **Email** - Email notifications
- **Approval Email** - Approval request emails

#### Documents
- **PDF Generation** - Generate PDF documents
- **Document Upload** - Handle document uploads

#### Scheduling
- **Calendar** - Calendar integration
- **Booking** - Appointment booking
- **Reminder** - Send reminders

#### Municipal Specific
- **Zoning** - Zoning/planning validation
- **Neighbor Notification** - Notify neighbors (building permits)

#### Compliance
- **Audit Log** - GDPR audit logging

### 3. BPMN Validation

**Location**: `Service/BpmnTemplateManager.php`

Enhanced validation with comprehensive checks:

#### Client-Side Validation (JavaScript)
- XML structure validation
- Start event presence
- End event presence
- Process element presence
- Sequence flow reference validation

#### Server-Side Validation (PHP)
- BPMN 2.0 namespace validation
- Start/end event validation
- Valid sequence flow references
- Element ID uniqueness

### 4. Visual Template Previews

**Location**: `Controller/TemplateBrowserController.php`

Template gallery now shows visual BPMN previews:

- **Thumbnail previews** in template cards
- **Full preview page** for detailed viewing
- Lazy-loading with AJAX
- Responsive design

**Twig Template**: `templates/workflow-template-card.html.twig`

### 5. AJAX Endpoints

**Location**: `Controller/BpmnEditorController.php`

Four REST endpoints for editor operations:

1. **Auto-save** (`/wizard/autosave`)
   - Saves BPMN XML to session storage
   - Returns timestamp

2. **Validate** (`/wizard/validate`)
   - Validates BPMN XML structure
   - Returns validation errors

3. **Export** (`/wizard/export`)
   - Downloads BPMN XML as .bpmn file

4. **Generate SVG** (`/wizard/generate-svg`)
   - Generates SVG preview (placeholder for now)

## File Structure

```
web/modules/custom/aabenforms_workflows/
├── src/
│   ├── Controller/
│   │   ├── BpmnEditorController.php        # AJAX endpoints
│   │   └── TemplateBrowserController.php   # Enhanced with previews
│   ├── Form/
│   │   └── WorkflowTemplateWizardForm.php  # Visual editor step added
│   └── Service/
│       └── BpmnTemplateManager.php         # Enhanced validation
├── js/
│   ├── bpmn-extensions/
│   │   └── danish-municipal-palette.js     # Custom palette
│   ├── bpmn-editor.js                      # Editor integration
│   └── bpmn-preview.js                     # Preview functionality
├── css/
│   ├── bpmn-editor.css                     # Editor styles
│   └── template-browser.css                # Card and preview styles
├── templates/
│   └── workflow-template-card.html.twig    # Template card
├── aabenforms_workflows.libraries.yml      # Updated with new libraries
├── aabenforms_workflows.routing.yml        # New routes added
├── aabenforms_workflows.module             # Theme hook added
└── aabenforms_workflows.info.yml           # Dependencies added
```

## Dependencies

Added to `aabenforms_workflows.info.yml`:
- `modeler_api:modeler_api` (v1.0.6)
- `bpmn_io:bpmn_io` (v3.0.4)

## JavaScript Libraries

### bpmn_editor
- `css/bpmn-editor.css`
- `js/bpmn-extensions/danish-municipal-palette.js`
- `js/bpmn-editor.js`
- Dependencies: `core/drupal`, `core/jquery`, `bpmn_io/ui`

### bpmn_preview
- `css/template-browser.css`
- `js/bpmn-preview.js`
- Dependencies: `core/drupal`, `core/jquery`, `bpmn_io/ui`

## Usage

### For Administrators

1. **Navigate** to `/admin/aabenforms/workflow-templates`
2. **Browse** visual template previews in cards
3. **Click** "Use This Template" to start wizard
4. **Select** a template (Step 1)
5. **Customize** workflow visually (Step 2)
   - Add/remove tasks from palette
   - Connect tasks with sequence flows
   - Configure task properties
   - Auto-save tracks changes
   - Validation provides instant feedback
6. **Continue** to configure webform, actions, etc.

### For Developers

#### Extending the Palette

Add new task types in `danish-municipal-palette.js`:

```javascript
actions['create.my-task'] = {
  group: 'danish-custom',
  className: 'danish-icon-mytask',
  title: translate('My Custom Task'),
  action: {
    dragstart: createTask('my_task', 'My Task Label', 'mytask'),
    click: createTask('my_task', 'My Task Label', 'mytask')
  }
};
```

#### Adding Validation Rules

Extend `BpmnTemplateManager::validateTemplate()`:

```php
// Check for custom requirement
$custom_elements = $xml->xpath('//bpmn:myCustomElement');
if (count($custom_elements) < 1) {
  $this->validationErrors[] = 'At least one custom element required';
}
```

#### Customizing Editor Appearance

Override styles in `css/bpmn-editor.css`:

```css
/* Color-code specific task types */
[data-element-id*="mytask"] .djs-visual > rect {
  fill: #e0f7fa !important;
  stroke: #00bcd4 !important;
}
```

## API Reference

### BpmnTemplateManager

```php
// Load template as string
$xml = $templateManager->loadTemplate($template_id, TRUE);

// Validate BPMN XML
$is_valid = $templateManager->validateTemplate($xml);
$errors = $templateManager->getValidationErrors();
```

### BpmnEditorController

```php
// Auto-save endpoint
POST /admin/aabenforms/workflow-templates/wizard/autosave
Body: { bpmn_xml: "<?xml..." }
Response: { success: true, timestamp: 1234567890 }

// Validate endpoint
POST /admin/aabenforms/workflow-templates/wizard/validate
Body: { bpmn_xml: "<?xml..." }
Response: { valid: true, errors: [] }
```

## Testing

### Manual Testing Checklist

- [ ] Template browser shows visual previews
- [ ] Clicking template card opens wizard
- [ ] Visual editor loads with template BPMN
- [ ] Can drag tasks from palette to canvas
- [ ] Can connect tasks with sequence flows
- [ ] Can edit task properties
- [ ] Auto-save works after changes
- [ ] Validation shows errors for invalid BPMN
- [ ] Next button proceeds to webform config
- [ ] BPMN XML saved in form state
- [ ] Final workflow creation includes customizations

### Testing Commands

```bash
# Clear cache after changes
ddev drush cr

# Check for JavaScript errors
ddev drush watchdog:show --filter=javascript

# Test template loading
ddev drush php-eval "echo \Drupal::service('aabenforms_workflows.bpmn_template_manager')->loadTemplate('building_permit', TRUE);"

# Test validation
ddev drush php-eval "$xml = file_get_contents('/path/to/test.bpmn'); \$mgr = \Drupal::service('aabenforms_workflows.bpmn_template_manager'); var_dump(\$mgr->validateTemplate(\$xml));"
```

## Known Limitations

1. **SVG Export**: Server-side SVG generation not yet implemented (uses client-side only)
2. **Browser Support**: Requires modern browser with ES6 support
3. **File Size**: Large BPMN files may impact performance
4. **Permissions**: Requires 'administer workflows' permission

## Troubleshooting

### Editor Doesn't Load

**Symptom**: Blank canvas or JavaScript errors

**Solutions**:
1. Check BPMN.io library loaded: `console.log(typeof BpmnJS)`
2. Clear Drupal cache: `ddev drush cr`
3. Check browser console for errors
4. Verify modeler_api and bpmn_io modules enabled

### Validation Always Fails

**Symptom**: Red validation errors even for valid BPMN

**Solutions**:
1. Check XML has proper BPMN 2.0 namespace
2. Ensure start and end events present
3. Check sequence flow references valid element IDs
4. View detailed errors in validation status

### Auto-Save Not Working

**Symptom**: Changes lost when navigating away

**Solutions**:
1. Check AJAX endpoint accessible: `/wizard/autosave`
2. Verify session storage working
3. Check network tab for failed requests
4. Ensure CSRF token valid

## Future Enhancements

- [ ] Server-side SVG generation
- [ ] BPMN diff viewer for comparing versions
- [ ] Collaboration features (multi-user editing)
- [ ] Template versioning
- [ ] Import from external BPMN tools
- [ ] Export to Camunda/Flowable formats
- [ ] Simulation mode (dry-run workflows)
- [ ] Performance metrics dashboard

## Related Documentation

- [Workflow Templates Reference](docs/WORKFLOW_TEMPLATES.md)
- [Municipal Admin Guide](docs/MUNICIPAL_ADMIN_GUIDE.md)
- [BPMN 2.0 Specification](https://www.omg.org/spec/BPMN/2.0/)
- [BPMN.io Documentation](https://bpmn.io/toolkit/bpmn-js/)

## Support

For issues or questions:
- GitHub Issues: https://github.com/madsnorgaard/aabenforms/issues
- BPMN.io Forum: https://forum.bpmn.io/
- Drupal Modeler API: https://www.drupal.org/project/modeler_api

---

**Last Updated**: February 2, 2026
**Version**: 1.0.0
**Author**: Claude Sonnet 4.5 + Mads Nørgaard
