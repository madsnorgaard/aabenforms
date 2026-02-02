#!/bin/bash

echo "Running full test suite with coverage..."

# Run tests and capture output
OUTPUT=$(cd /var/www/html && phpunit --coverage-text 2>&1)

# Extract coverage percentage
COVERAGE=$(echo "$OUTPUT" | grep -A 1 "Code Coverage Report:" | grep "Lines:" | awk '{print $2}' | tr -d '%' | head -1)

echo "Current test coverage: ${COVERAGE}%"

if [ -z "$COVERAGE" ]; then
  echo "Could not determine coverage percentage"
  echo "Test output:"
  echo "$OUTPUT"
  exit 1
fi

# Use bc for comparison if available, otherwise use awk
if command -v bc &> /dev/null; then
  if (( $(echo "$COVERAGE >= 60" | bc -l) )); then
    echo "✅ Coverage target achieved (60%+)"
    exit 0
  else
    echo "❌ Coverage below target (${COVERAGE}% < 60%)"
    echo "Modules needing more tests:"
    echo "$OUTPUT" | grep -A 20 "Code Coverage Report:"
    exit 1
  fi
else
  # Use awk as fallback
  if awk -v cov="$COVERAGE" 'BEGIN { exit (cov >= 60) ? 0 : 1 }'; then
    echo "✅ Coverage target achieved (60%+)"
    exit 0
  else
    echo "❌ Coverage below target (${COVERAGE}% < 60%)"
    echo "Modules needing more tests:"
    echo "$OUTPUT" | grep -A 20 "Code Coverage Report:"
    exit 1
  fi
fi
