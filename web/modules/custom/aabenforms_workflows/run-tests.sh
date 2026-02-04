#!/bin/bash

###############################################################################
# ÅbenForms Workflows - Test Runner Script
#
# Quick test execution script for Phase 5 implementation
#
# Usage:
#   ./run-tests.sh [all|unit|integration|coverage]
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
TEST_DIR="${SCRIPT_DIR}/tests"

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}ÅbenForms Workflows Test Suite${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Parse command
COMMAND=${1:-all}

case $COMMAND in
  unit)
    echo -e "${YELLOW}Running Unit Tests...${NC}"
    echo ""
    phpunit --testdox "${TEST_DIR}/src/Unit"
    ;;

  integration)
    echo -e "${YELLOW}Running Integration Tests...${NC}"
    echo ""
    phpunit --testdox "${TEST_DIR}/src/Kernel"
    ;;

  coverage)
    echo -e "${YELLOW}Running Tests with Coverage...${NC}"
    echo ""
    phpunit --coverage-html coverage --coverage-text "${TEST_DIR}"
    echo ""
    echo -e "${GREEN}Coverage report generated in: coverage/index.html${NC}"
    ;;

  payment)
    echo -e "${YELLOW}Running ProcessPaymentAction Tests...${NC}"
    phpunit --testdox --filter ProcessPaymentActionTest "${TEST_DIR}"
    ;;

  sms)
    echo -e "${YELLOW}Running SendSmsAction Tests...${NC}"
    phpunit --testdox --filter SendSmsActionTest "${TEST_DIR}"
    ;;

  pdf)
    echo -e "${YELLOW}Running GeneratePdfAction Tests...${NC}"
    phpunit --testdox --filter GeneratePdfActionTest "${TEST_DIR}"
    ;;

  slots)
    echo -e "${YELLOW}Running FetchAvailableSlotsAction Tests...${NC}"
    phpunit --testdox --filter FetchAvailableSlotsActionTest "${TEST_DIR}"
    ;;

  booking)
    echo -e "${YELLOW}Running BookAppointmentAction Tests...${NC}"
    phpunit --testdox --filter BookAppointmentActionTest "${TEST_DIR}"
    ;;

  reminder)
    echo -e "${YELLOW}Running SendReminderAction Tests...${NC}"
    phpunit --testdox --filter SendReminderActionTest "${TEST_DIR}"
    ;;

  zoning)
    echo -e "${YELLOW}Running ValidateZoningAction Tests...${NC}"
    phpunit --testdox --filter ValidateZoningActionTest "${TEST_DIR}"
    ;;

  workflows)
    echo -e "${YELLOW}Running Demo Workflows Integration Tests...${NC}"
    phpunit --testdox --filter DemoWorkflowsIntegrationTest "${TEST_DIR}"
    ;;

  all)
    echo -e "${YELLOW}Running All Tests...${NC}"
    echo ""
    phpunit --testdox "${TEST_DIR}"
    ;;

  *)
    echo -e "${RED}Unknown command: $COMMAND${NC}"
    echo ""
    echo "Usage: ./run-tests.sh [command]"
    echo ""
    echo "Commands:"
    echo "  all           Run all tests (default)"
    echo "  unit          Run unit tests only"
    echo "  integration   Run integration tests only"
    echo "  coverage      Run tests with coverage report"
    echo ""
    echo "Specific test suites:"
    echo "  payment       ProcessPaymentAction tests"
    echo "  sms           SendSmsAction tests"
    echo "  pdf           GeneratePdfAction tests"
    echo "  slots         FetchAvailableSlotsAction tests"
    echo "  booking       BookAppointmentAction tests"
    echo "  reminder      SendReminderAction tests"
    echo "  zoning        ValidateZoningAction tests"
    echo "  workflows     Demo workflows integration tests"
    echo ""
    exit 1
    ;;
esac

echo ""
echo -e "${GREEN}Tests completed!${NC}"
echo ""
