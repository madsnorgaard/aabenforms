#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  Parent Approval System - Verification Script               ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

PASS=0
FAIL=0

# Function to check and report
check() {
  if [ $1 -eq 0 ]; then
    echo "✓ $2"
    ((PASS++))
  else
    echo "✗ $2"
    ((FAIL++))
  fi
}

echo "Checking files..."

# Check services
[ -f "web/modules/custom/aabenforms_workflows/src/Service/ApprovalTokenService.php" ]
check $? "ApprovalTokenService exists"

# Check controller
[ -f "web/modules/custom/aabenforms_workflows/src/Controller/ParentApprovalController.php" ]
check $? "ParentApprovalController exists"

# Check form
[ -f "web/modules/custom/aabenforms_workflows/src/Form/ParentApprovalForm.php" ]
check $? "ParentApprovalForm exists"

# Check action
[ -f "web/modules/custom/aabenforms_workflows/src/Plugin/Action/SendApprovalEmailAction.php" ]
check $? "SendApprovalEmailAction exists"

# Check workflows
[ -f "web/modules/custom/aabenforms_workflows/config/install/eca.eca.initial_request_flow.yml" ]
check $? "initial_request_flow workflow exists"

# Check templates
[ -f "web/modules/custom/aabenforms_workflows/templates/parent-approval-login.html.twig" ]
check $? "Login template exists"

[ -f "web/modules/custom/aabenforms_workflows/templates/parent-approval-page.html.twig" ]
check $? "Approval page template exists"

# Check assets
[ -f "web/modules/custom/aabenforms_workflows/css/parent-approval.css" ]
check $? "CSS file exists"

[ -f "web/modules/custom/aabenforms_workflows/js/parent-approval.js" ]
check $? "JS file exists"

# Check libraries
[ -f "web/modules/custom/aabenforms_workflows/aabenforms_workflows.libraries.yml" ]
check $? "Libraries file exists"

# Check documentation
[ -f "web/modules/custom/aabenforms_workflows/PARENT_APPROVAL_SYSTEM.md" ]
check $? "Main documentation exists"

[ -f "web/modules/custom/aabenforms_workflows/QUICK_START.md" ]
check $? "Quick start guide exists"

[ -f "APPROVAL_SYSTEM_IMPLEMENTATION.md" ]
check $? "Implementation summary exists"

# Check test script
[ -f "web/modules/custom/aabenforms_workflows/tests/manual/test_approval_flow.php" ]
check $? "Test script exists"

echo ""
echo "Checking configuration..."

# Check routing
grep -q "aabenforms_workflows.parent_approval:" web/modules/custom/aabenforms_workflows/aabenforms_workflows.routing.yml
check $? "Approval route configured"

grep -q "aabenforms_workflows.parent_approval_complete:" web/modules/custom/aabenforms_workflows/aabenforms_workflows.routing.yml
check $? "Complete route configured"

# Check services
grep -q "aabenforms_workflows.approval_token:" web/modules/custom/aabenforms_workflows/aabenforms_workflows.services.yml
check $? "Token service registered"

# Check module hooks
grep -q "function aabenforms_workflows_mail" web/modules/custom/aabenforms_workflows/aabenforms_workflows.module
check $? "Mail hook implemented"

grep -q "function aabenforms_workflows_theme" web/modules/custom/aabenforms_workflows/aabenforms_workflows.module
check $? "Theme hook implemented"

# Check webform fields
grep -q "parent1_comments:" web/modules/custom/aabenforms_workflows/config/install/webform.webform.parent_request_form.yml
check $? "Parent1 comments field added"

grep -q "parent2_comments:" web/modules/custom/aabenforms_workflows/config/install/webform.webform.parent_request_form.yml
check $? "Parent2 comments field added"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  Results                                                     ║"
echo "╠══════════════════════════════════════════════════════════════╣"
printf "║  ✓ Passed: %-3d                                              ║\n" $PASS
printf "║  ✗ Failed: %-3d                                              ║\n" $FAIL
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

if [ $FAIL -eq 0 ]; then
  echo "🎉 All checks passed! The approval system is properly installed."
  echo ""
  echo "Next steps:"
  echo "1. Run: ddev drush cr"
  echo "2. Test: ddev drush php:script web/modules/custom/aabenforms_workflows/tests/manual/test_approval_flow.php"
  echo "3. Read: web/modules/custom/aabenforms_workflows/QUICK_START.md"
  exit 0
else
  echo "⚠️  Some checks failed. Please review the output above."
  exit 1
fi
