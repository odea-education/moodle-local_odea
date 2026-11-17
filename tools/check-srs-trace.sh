#!/bin/sh
# Keeps docs/SRS.md and the tests in step. Fails when:
# - the SRS names a test that does not exist;
# - a PHPUnit test or Behat scenario is named by no requirement;
# - a test's "Verifies" line differs from the requirements the SRS maps to it;
# - a PHPUnit test's name does not start with one of those requirements.
set -eu
cd "$(dirname "$0")/.."
srs=docs/SRS.md
status=0

# The requirement ids whose matrix row (SRS 4.2) names $1, as "ODEA-MOODLE-1, ODEA-MOODLE-2".
required_by() {
    grep '^| ' "$srs" | grep -F "$1" | sed -n 's/^| *\(ODEA-MOODLE-[0-9][0-9]*\) .*/\1/p' | paste -sd, - | sed 's/,/, /g'
}

for ref in $(grep -oE '[a-z_]+_test::test_[a-z0-9_]+' "$srs" | sort -u); do
    file=$(find tests -name "${ref%%::*}.php")
    if [ -z "$file" ] || ! grep -q "function ${ref##*::}(" "$file"; then
        echo "SRS names a test that does not exist: $ref"
        status=1
    fi
done

for file in $(find tests -name '*_test.php'); do
    class=$(basename "$file" .php)
    for method in $(grep -oE 'function test_[a-z0-9_]+' "$file" | sed 's/function //'); do
        if ! grep -q "$class::$method\`" "$srs"; then
            echo "Test is named by no requirement: $class::$method"
            status=1
            continue
        fi
        expected=$(required_by "\`$class::$method\`")
        stated=$(awk -v m="function $method(" '
            /\* Verifies ODEA-MOODLE-/ { sub(/^.*Verifies /, ""); sub(/\.$/, ""); line = $0 }
            index($0, m) { print line; exit }
            /^    }/ { line = "" }' "$file")
        if [ "$stated" != "$expected" ]; then
            echo "$class::$method verifies \"$stated\", the SRS maps it to \"$expected\""
            status=1
        fi
        named=$(echo "$method" | sed -n 's/^test_odea_moodle_\([0-9][0-9]*\)_.*/ODEA-MOODLE-\1/p')
        if [ -n "$expected" ] && ! echo ", $expected," | grep -qF ", $named,"; then
            echo "$class::$method is not named after a requirement it verifies ($expected)"
            status=1
        fi
    done
done

for feature in tests/behat/*.feature; do
    name=$(basename "$feature")
    while IFS= read -r title; do
        if ! grep -qF "\`$name\`: $title" "$srs"; then
            echo "Scenario is named by no requirement: $name: $title"
            status=1
            continue
        fi
        expected=$(required_by "\`$name\`: $title")
        stated=$(grep -B1 -F "Scenario: $title" "$feature" | sed -n 's/^ *# Verifies \(.*\)\.$/\1/p')
        if [ "$stated" != "$expected" ]; then
            echo "$name: $title verifies \"$stated\", the SRS maps it to \"$expected\""
            status=1
        fi
    done <<SCENARIOS
$(sed -n 's/^ *Scenario: //p' "$feature")
SCENARIOS
done

[ "$status" -eq 0 ] && echo "SRS traceability: the SRS and the tests name each other consistently."
exit "$status"
